<?php
namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Support\Facades\URL;

class WhatsAppInvoiceController extends Controller
{
    public static function phone($value)
    {
        $value=preg_replace('/[^0-9]/','',(string)$value);
        if(substr($value,0,2)==='00')$value=substr($value,2);
        if(preg_match('/^01[3-9][0-9]{8}$/D',$value))$value='88'.$value;
        return preg_match('/^[1-9][0-9]{6,14}$/D',$value)?$value:null;
    }
    public function preview($id)
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $order=Order::with('orderDetails.product')->findOrFail($id);$address=json_decode($order->shipping_address,true)?:[];
        $phone=self::phone($address['phone']??'');
        $link=URL::temporarySignedRoute('invoice.shared',now()->addDays(7),['id'=>$order->id]);
        $message="আপনার Footstep order #".\App\Services\OrderDisplay::label($order)." গ্রহণ করা হয়েছে।\nমোট: ".number_format((float)$order->grand_total,2).' '.\App\Services\MetaPixel::currency()."\n";
        if($order->payment_type==='cash_on_delivery')$message.="Cash on Delivery — ডেলিভারির সময় বকেয়া পরিশোধ করবেন।\n";
        $message.="Invoice (৭ দিন কার্যকর): ".$link;
        $whatsapp=$phone?'https://wa.me/'.$phone.'?text='.rawurlencode($message):null;
        $sms=$phone?'sms:+'.$phone.'?body='.rawurlencode($message):null;
        $invoiceShare=\App\Services\InvoiceShare::data($order);
        return response()->view('backend.platform.whatsapp_invoice',compact('order','phone','link','message','whatsapp','invoiceShare','sms'))->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer');
    }
    public function invoice($id)
    {
        // The route verifies the signature and seven-day expiry before this action.
        $order=Order::with('orderDetails.product')->findOrFail($id);
        $address=json_decode($order->shipping_address,true)?:[];
        $currency=\App\Services\MetaPixel::currency();
        return response()->view('frontend.shared_invoice',compact('order','address','currency'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow')->header('Referrer-Policy','no-referrer');
    }
}
