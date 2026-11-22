<?php
namespace App\Http\Controllers;

use App\Services\OrderSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrderSmsSettingsController extends Controller
{
    public function index()
    {
        $settings = OrderSms::settings(); $keySaved = OrderSms::hasKey();
        $liveAllowed = OrderSms::liveAllowed();
        $worker = Cache::get('order_sms_worker_seen');
        $counts = DB::table(OrderSms::TABLE)->select('state', DB::raw('COUNT(*) AS total'))->groupBy('state')->pluck('total','state');
        $logs = DB::table(OrderSms::TABLE)->leftJoin('orders','orders.id','=',OrderSms::TABLE.'.order_id')
            ->select(OrderSms::TABLE.'.id', 'order_id','event','state','mode','recipient_hint','provider_id','notice',OrderSms::TABLE.'.created_at','orders.code')
            ->orderByDesc(OrderSms::TABLE.'.id')->paginate(20);
        return response()->view('backend.platform.order_sms', compact('settings','keySaved','liveAllowed','worker','counts','logs'))
            ->header('Cache-Control','private, no-store');
    }
    public function save(Request $request)
    {
        // Do not flash credentials back into session/HTML after validation errors.
        $input = $request->all();
        if ($request->input('provider') === 'onecodesoft' && !$request->boolean('masking')) {
            unset($input['sender_id']);
        }

        $validator = Validator::make($input, [
            'provider'=>['required','in:smsnet,onecodesoft'],'masking'=>['nullable','boolean'],'enabled'=>['nullable','boolean'], 'mode'=>['required','in:mock,sandbox,live'],
            'trigger'=>['required','in:confirmed,placed'], 'sender_id'=>['nullable','string','max:80','regex:/^[A-Za-z0-9 _.-]*$/D'],
            'message'=>['required','string','max:1200'], 'api_key'=>['nullable','string','max:4096','regex:/^\S+$/D'],
            'remove_key'=>['nullable','boolean'],
        ]);
        if ($validator->fails()) return back()->withErrors($validator)->withInput($request->except(['api_key','password']));
        $data = $validator->validated();
        if(strtolower((string)($data['api_key']??''))==='<redacted>')return back()->withErrors(['api_key'=>'আসল provider API key সরাসরি paste করুন; hidden placeholder key নয়।']);
        try { OrderSms::validateTemplate($data['message']); }
        catch (\Illuminate\Validation\ValidationException $e) { return back()->withErrors($e->errors())->withInput($request->except(['api_key','password'])); }
        if($data['mode']==='sandbox'&&$data['provider']!=='onecodesoft')return back()->withErrors(['mode'=>'Sandbox শুধু OneCodeSoft provider-এর জন্য।'])->withInput($request->except(['api_key','password']));
        $old = OrderSms::settings();
        $sensitive = $request->filled('api_key') || $request->boolean('remove_key') ||
            ($request->boolean('enabled') && $data['mode']==='live') || $old['mode']==='live';
        if ($sensitive && !Hash::check((string)$request->input('password'), auth()->user()->password)) {
            return back()->withErrors(['password'=>'API key/live settings বদলাতে বর্তমান admin password দিন।'])->withInput($request->except(['api_key','password']));
        }
        if ($request->boolean('enabled') && $data['mode']==='live' &&
            ($request->boolean('remove_key') || (!$request->filled('api_key') && !OrderSms::hasKey($data['provider'])))) {
            return back()->withErrors(['api_key'=>'Live auto SMS চালু করতে API key দিন।'])->withInput($request->except(['api_key','password']));
        }
        if($request->boolean('enabled')&&$data['provider']==='onecodesoft'&&$data['mode']==='live'&&$request->boolean('masking')&&empty($data['sender_id']))return back()->withErrors(['sender_id'=>'Masking-এর জন্য provider-approved Sender ID দিন।'])->withInput($request->except(['api_key','password']));
        OrderSms::store(['provider'=>$data['provider'],'masking'=>$request->boolean('masking'),'enabled'=>$request->boolean('enabled'), 'mode'=>$data['mode'], 'trigger'=>$data['trigger'],
            'sender_id'=>$data['sender_id'] ?? '', 'message'=>$data['message']], $data['api_key'] ?? null, $request->boolean('remove_key'));
        return redirect()->route('platform.sms')->with('sms_success','SMS settings saved. No test SMS was sent. / সেটিংস সংরক্ষিত; কোনো test SMS পাঠানো হয়নি।');
    }
    public function test()
    {
        try { $result = OrderSms::balance(); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) { return back()->with('sms_error',$e->getMessage()); }
        return back()->with($result['ok'] ? 'sms_success' : 'sms_error', $result['ok']
            ? 'Connection OK · Balance: '.$result['balance'].' BDT. No SMS sent.'
            : 'Connection test failed: '.$result['code'].'. API key/provider network পরীক্ষা করুন।');
    }
}
