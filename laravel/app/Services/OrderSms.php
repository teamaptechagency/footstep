<?php
namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/** Transactional SMS only. Secrets and customer payloads never enter diagnostic logs. */
class OrderSms
{
    const KEY = 'order_sms_net_bd_v1';
    const SECRET = 'order_sms_net_bd_key_v1';
    const ONECODE_SECRET = 'order_sms_onecodesoft_key_v1';
    const TABLE = 'order_sms_outbox';
    const TOKENS = ['customer_name', 'order_number', 'total', 'invoice_url'];

    public static function liveAllowed()
    {
        return (bool) (config('server_operations.live_integrations', false)
            || config('server_operations.order_sms_enabled', false));
    }

    public static function defaults()
    {
        return ['provider'=>'smsnet','masking'=>true,'enabled'=>false, 'mode'=>'mock', 'trigger'=>'confirmed', 'sender_id'=>'',
            'message'=>"You're order has been confirmed\n\nআপনার অর্ডার আমরা পেয়েছি এবং এটা দ্রুতই আপনার লোকেশনে পাঠানোর ব্যবস্থা করা হচ্ছে\n\nআপনার Whatsapp এবং মোবাইল নাম্বারটি সচল রাখবেন\n\nআপনার অর্ডার এর বিবরণ নিচে দেয়া হল\n\nOrder: {order_number}\nTotal: BDT {total}\n{invoice_url}"];
    }

    public static function settings()
    {
        $stored = json_decode(BusinessSetting::where('type', self::KEY)->value('value') ?: '{}', true);
        return array_merge(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function hasKey($provider=null) { return (bool) BusinessSetting::where('type', self::secretName($provider))->value('value'); }
    private static function secretName($provider=null) { return ($provider??self::settings()['provider'])==='onecodesoft'?self::ONECODE_SECRET:self::SECRET; }
    private static function apiKey($provider=null)
    {
        $value = BusinessSetting::where('type', self::secretName($provider))->value('value');
        return $value ? Crypt::decryptString($value) : '';
    }
    public static function store(array $settings, $key = null, $removeKey = false)
    {
        DB::transaction(function () use ($settings, $key, $removeKey) {
            self::put(self::KEY, json_encode($settings));
            if ($removeKey) self::put(self::secretName($settings['provider']??'smsnet'), null);
            elseif ($key !== null && $key !== '') self::put(self::secretName($settings['provider']??'smsnet'), Crypt::encryptString($key));
        });
        \Illuminate\Support\Facades\Cache::forget('business_settings');
    }
    private static function put($key, $value)
    {
        $record = BusinessSetting::where('type', $key)->first() ?: new BusinessSetting;
        $record->type = $key; $record->value = $value; $record->save();
    }
    public static function validateTemplate($message)
    {
        preg_match_all('/\{([^{}]+)\}/u', $message, $matches);
        if (array_diff($matches[1], self::TOKENS)) {
            throw ValidationException::withMessages(['message'=>'শুধু {customer_name}, {order_number}, {total}, {invoice_url} ব্যবহার করুন।']);
        }
    }
    public static function phone($value)
    {
        $value = strtr((string)$value, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
        $value = preg_replace('/[^0-9]/', '', $value);
        if (substr($value, 0, 2) === '00') $value = substr($value, 2);
        if (preg_match('/^01[3-9][0-9]{8}$/D', $value)) $value = '88'.$value;
        return preg_match('/^8801[3-9][0-9]{8}$/D', $value) ? $value : null;
    }
    public static function render($message, Order $order)
    {
        $address = json_decode($order->shipping_address, true) ?: [];
        $tokens = ['{customer_name}'=>preg_replace('/[\r\n]+/u', ' ', $address['name'] ?? 'Customer'),
            '{order_number}'=>(string)$order->code, '{total}'=>number_format((float)$order->grand_total, 2, '.', '')];
        if (strpos($message, '{invoice_url}') !== false) {
            $tokens['{invoice_url}'] = URL::temporarySignedRoute('invoice.shared', now()->addDays(7), ['id'=>$order->id]);
        }
        return strtr($message, $tokens);
    }

    // The unique key is the durable duplicate guard. Inserts participate in the order's transaction.
    public static function enqueue(Order $order, $event)
    {
        $settings = self::settings();
        if (!$settings['enabled'] || $settings['trigger'] !== $event) return;
        DB::table(self::TABLE)->insertOrIgnore(['order_id'=>$order->id, 'event'=>$event, 'state'=>'pending',
            'mode'=>$settings['mode'], 'snapshot'=>Crypt::encryptString(json_encode([
                'message'=>$settings['message'], 'provider'=>$settings['provider'],'masking'=>$settings['masking'],'sender_id'=>$settings['sender_id'] ?? ''])),
            'attempts'=>0, 'created_at'=>now(), 'updated_at'=>now()]);
    }

    public static function process($limit = 20)
    {
        // A killed request may already have reached the provider. Never resend it automatically.
        DB::table(self::TABLE)->where('state', 'sending')->where('updated_at', '<', now()->subMinutes(10))
            ->update(['state'=>'uncertain','notice'=>'worker_interrupted_check_provider','updated_at'=>now()]);
        $ids = DB::table(self::TABLE)->where('state', 'pending')->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) self::processOne($id);
        return count($ids);
    }

    private static function processOne($id,$approvedTest=false)
    {
        $settings = self::settings();
        if (!$settings['enabled'] && !$approvedTest) return;
        $row = DB::transaction(function () use ($id,$approvedTest) {
            $row = DB::table(self::TABLE)->where('id', $id)->lockForUpdate()->first();
            if (!$row || $row->state !== 'pending') return null;
            // Keep live requests pending while staging's external-integration guard is off.
            if ($row->mode === 'live' && !self::liveAllowed() && !$approvedTest) return null;
            DB::table(self::TABLE)->where('id', $id)->update(['state'=>'sending','attempts'=>$row->attempts+1,'updated_at'=>now()]);
            return $row;
        });
        if (!$row) return;
        $transmitting = false;
        try {
            $order = Order::find($row->order_id);
            if (!$order || in_array($order->delivery_status, ['cancelled','canceled'])) {
                self::finish($id, 'skipped', 'order_missing_or_cancelled'); return;
            }
            $address = json_decode($order->shipping_address, true) ?: [];
            $phone = self::phone($address['phone'] ?? '');
            if (!$phone) { self::finish($id, 'failed', 'invalid_customer_mobile'); return; }
            $snapshot = json_decode(Crypt::decryptString($row->snapshot), true);
            // Freeze the rendered message on first attempt so retries remain the same notification.
            $payload = $row->payload ? json_decode(Crypt::decryptString($row->payload), true) : [
                'to'=>$phone, 'msg'=>self::render($snapshot['message'], $order)];
            DB::table(self::TABLE)->where('id', $id)->update(['payload'=>Crypt::encryptString(json_encode($payload)),
                'recipient_hint'=>substr($payload['to'], 0, 5).'******'.substr($payload['to'], -3)]);
            if (!in_array($row->mode,['live','sandbox'],true)) { self::finish($id, 'mocked', 'no_sms_sent'); return; }
            $key = self::apiKey($snapshot['provider']??'smsnet');
            if (!$key) { self::finish($id, 'failed', 'api_key_missing'); return; }
            if (($snapshot['provider']??'smsnet')==='onecodesoft') {
                $result=OneCodeSoftSms::send($key,$payload['to'],$payload['msg'],$snapshot['sender_id']??'',$row->mode==='sandbox',$snapshot['masking']??true);
                self::finish($id,$result['state'],$result['notice'],isset($result['provider_id'])?['provider_id'=>$result['provider_id']]:[]);return;
            }
            $request = ['api_key'=>$key] + $payload;
            if (!empty($snapshot['sender_id'])) $request['sender_id'] = $snapshot['sender_id'];
            $transmitting = true;
            $response = Http::asForm()->acceptJson()->timeout(12)->post('https://api.sms.net.bd/sendsms', $request);
            $data = $response->json();
            if ($response->successful() && is_array($data) && isset($data['error']) && (string)$data['error'] === '0' && isset($data['data']['request_id'])) {
                $providerId = (string)$data['data']['request_id'];
                if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $providerId)) { self::finish($id, 'uncertain', 'invalid_provider_reference'); return; }
                self::finish($id, 'accepted', 'provider_accepted_not_delivery_confirmation', ['provider_id'=>$providerId]);
            } elseif (is_array($data) && isset($data['error']) && preg_match('/^[0-9]{3}$/D', (string)$data['error']) && (int)$data['error'] !== 409) {
                self::finish($id, 'failed', 'provider_error_'.(int)$data['error']);
            } else self::finish($id, 'uncertain', 'ambiguous_provider_response_check_portal');
        } catch (\Throwable $e) {
            // Exception text can contain request data; never persist it.
            self::finish($id, $transmitting ? 'uncertain' : 'failed', $transmitting ? 'network_or_provider_error_check_portal' : 'local_processing_error');
        }
    }
    private static function finish($id, $state, $notice, array $extra = [])
    {
        DB::table(self::TABLE)->where('id', $id)->update($extra + ['state'=>$state,'notice'=>$notice,'updated_at'=>now()]);
    }
    // CLI/admin-approved single test: never drains unrelated pending notifications.
    public static function approvedTest($order,$recipient,$sandbox=false) {
        abort_unless(auth()->check()&&auth()->user()->user_type==='admin',403);
        $phone=self::phone($recipient);$address=json_decode($order->shipping_address,true)?:[];
        abort_unless($phone&&$phone===self::phone($address['phone']??''),422);
        $settings=self::settings();abort_unless($settings['provider']==='onecodesoft',422);
        DB::table(self::TABLE)->insertOrIgnore(['order_id'=>$order->id,'event'=>'manual_test','state'=>'pending','mode'=>$sandbox?'sandbox':'live',
            'snapshot'=>Crypt::encryptString(json_encode(['message'=>$settings['message'],'provider'=>'onecodesoft','masking'=>false,'sender_id'=>''])),
            'attempts'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $id=DB::table(self::TABLE)->where('order_id',$order->id)->where('event','manual_test')->value('id');
        self::processOne($id,true);
        return DB::table(self::TABLE)->where('id',$id)->first(['id','state','notice','provider_id']);
    }
    public static function deliveryStatus($reference) { return OneCodeSoftSms::status(self::apiKey('onecodesoft'),$reference); }

    public static function balance()
    {
        if(self::settings()['provider']==='onecodesoft')return OneCodeSoftSms::balance(self::apiKey('onecodesoft'));
        abort_unless(config('server_operations.live_integrations', false), 422, 'এই test VPS-এ external calls বন্ধ আছে।');
        $key = self::apiKey();
        abort_unless($key, 422, 'API key save করুন।');
        try {
            $response = Http::asForm()->acceptJson()->timeout(10)->post('https://api.sms.net.bd/user/balance/', ['api_key'=>$key]);
            $data = $response->json();
            if ($response->successful() && isset($data['error']) && (string)$data['error'] === '0' && is_numeric($data['data']['balance'] ?? null)) {
                return ['ok'=>true,'balance'=>(string)$data['data']['balance']];
            }
            return ['ok'=>false,'code'=>'connection_or_credentials_rejected'];
        } catch (\Throwable $e) { return ['ok'=>false,'code'=>'network_or_provider_error']; }
    }
}
