<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\{Product,Order,User,Brand,Category,FlashDeal,Upload};
use App\Services\BackendModules;

class AdminSearchController extends Controller
{
    private function normalize($value)
    {
        return trim(preg_replace('/\s+/u',' ',strtr($value,array_combine(
            preg_split('//u','০১২৩৪৫৬৭৮৯',-1,PREG_SPLIT_NO_EMPTY),range(0,9)
        ))));
    }

    private function like($value)
    {
        return '%'.str_replace(['!','%','_'],['!!','!%','!_'],$value).'%';
    }

    private function allowed($name)
    {
        return Route::has($name) && BackendModules::routeNameAllowed($name);
    }

    private function matches($text,$tokens)
    {
        foreach($tokens as $token)if(mb_stripos($text,$token)===false)return false;
        return true;
    }

    private function menus($tokens)
    {
        $items=[];$seen=[];
        $html=view('backend.inc.admin_sidenav')->render();
        $dom=new \DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        try{
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);
            $xp=new \DOMXPath($dom);
            foreach($xp->query('//*[@id="main-menu"]//a[@href]') as $link){
                $url=$link->getAttribute('href');$path=parse_url($url,PHP_URL_PATH);
                if(!$path || strpos($path,'/admin')!==0 || $url[0]==='#' || !BackendModules::urlAllowed($url))continue;
                $label=$xp->query('.//*[contains(concat(" ",normalize-space(@class)," ")," aiz-side-nav-text ")]',$link)->item(0);
                $title=trim($label?$label->textContent:$link->textContent);
                $parents=[];
                foreach($xp->query('ancestor::li/a/*[contains(concat(" ",normalize-space(@class)," ")," aiz-side-nav-text ")]',$link) as $parent){$parents[]=trim($parent->textContent);}
                $haystack=implode(' ',$parents).' '.$title.' '.str_replace(['/','-','_'],' ',$path);
                $aliases=['product'=>'প্রোডাক্ট পণ্য জুতা','order'=>'অর্ডার','customer'=>'কাস্টমার গ্রাহক','meta'=>'মেটা পিক্সেল','backup'=>'ব্যাকআপ','setting'=>'সেটিং','media'=>'ছবি ভিডিও','steadfast'=>'কুরিয়ার','cache'=>'ক্যাশ','analytics'=>'রিপোর্ট','flash'=>'ফ্ল্যাশ ডিল'];
                foreach($aliases as $word=>$alias)if(stripos($haystack,$word)!==false)$haystack.=' '.$alias;
                if(!$this->matches($haystack,$tokens)||isset($seen[$url]))continue;
                $seen[$url]=true;
                $items[]=['group'=>'menus','type'=>'Menu / মেনু','title'=>$title,'subtitle'=>implode(' / ',$parents),'url'=>$url];
            }
        }finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
        foreach(config('tracking_providers',[]) as $key=>$details){
            if(!$this->allowed('platform.tracking.provider'))continue;
            $title=\App\Services\TrackingIntegrations::definitions()[$key]['label'].' settings';
            if($this->matches($title.' '.$key.' '.$details['summary'],$tokens))$items[]=['group'=>'menus','type'=>'Integration settings','title'=>$title,'subtitle'=>'Marketing integrations','url'=>route('platform.tracking.provider',$key)];
        }
        return array_slice($items,0,20);
    }

    public function index(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->user_type==='admin' && !auth()->user()->banned,403);
        $data=$request->validate(['q'=>'required|string|max:100']);
        $q=$this->normalize($data['q']);
        abort_unless(ctype_digit($q)||mb_strlen($q)>=2,422,'কমপক্ষে ২ অক্ষর অথবা একটি ID লিখুন।');
        $phoneDigits=preg_replace('/[^0-9]/','',$q);
        if(preg_match('/^[+0-9()\s-]+$/',$q) && strlen($phoneDigits)>=10 && strlen($phoneDigits)<=14)$q=$phoneDigits;
        $tokens=array_slice(preg_split('/\s+/u',$q),0,8);
        $items=$this->menus($tokens);

        if($this->allowed('products.admin.edit')){
            $products=Product::query();
            foreach($tokens as $token){$pattern=$this->like($token);$products->where(function($w)use($token,$pattern){
                $w->whereRaw("name LIKE ? ESCAPE '!'",[$pattern])->orWhereRaw("slug LIKE ? ESCAPE '!'",[$pattern])
                  ->orWhereHas('stocks',function($stock)use($pattern){$stock->whereRaw("sku LIKE ? ESCAPE '!'",[$pattern]);});
                if(ctype_digit($token))$w->orWhere('id',$token);
            });}
            foreach($products->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END',[ctype_digit($q)?$q:0])->orderByDesc('id')->limit(10)->get(['id','name','slug']) as $p){
                $items[]=['group'=>'products','type'=>'Product #'.$p->id,'title'=>$p->name,'subtitle'=>'Edit product / প্রোডাক্ট খুলুন','url'=>route('products.admin.edit',$p->id)];
            }
        }

        // Match only known customer/order fields, never raw JSON or integration secrets.
        $safeAddress="IF(JSON_VALID(shipping_address),shipping_address,'{}')";
        $jsonPhone="JSON_UNQUOTE(JSON_EXTRACT($safeAddress,'$.phone'))";
        $cleanPhone="REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($jsonPhone,'+',''),' ',''),'-',''),'(',''),')','')";
        if($this->allowed('all_orders.show')){
            $orders=Order::query();
            foreach($tokens as $token){$pattern=$this->like($token);$orders->where(function($w)use($token,$pattern,$safeAddress,$cleanPhone){
                $w->whereRaw("code LIKE ? ESCAPE '!'",[$pattern])->orWhereRaw("tracking_code LIKE ? ESCAPE '!'",[$pattern]);
                foreach(['name','phone','email','address'] as $field)$w->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT($safeAddress,'$.$field')) LIKE ? ESCAPE '!'",[$pattern]);
                $digits=preg_replace('/[^0-9]/','',$token);
                if(strlen($digits)>=4 && preg_match('/^[+0-9()\-]+$/',$token))$w->orWhereRaw("$cleanPhone LIKE ? ESCAPE '!'",[$this->like(strlen($digits)>=10?substr($digits,-10):$digits)]);
                if(ctype_digit($token))$w->orWhere('id',$token);
            });}
            foreach($orders->orderByDesc('id')->limit(12)->get(['id','code','shipping_address','delivery_status','payment_status']) as $order){
                $address=json_decode($order->shipping_address,true)?:[];
                $items[]=['group'=>'orders','type'=>'Order / অর্ডার','title'=>$order->code,'subtitle'=>implode(' · ',array_filter([$address['name']??'', $address['phone']??'', $order->delivery_status,$order->payment_status])),'url'=>route('all_orders.show',encrypt($order->id))];
            }
        }

        if($this->allowed('customers.index')){
            $customers=User::where('user_type','customer');
            foreach($tokens as $token){$pattern=$this->like($token);$customers->where(function($w)use($token,$pattern){
                $w->whereRaw("name LIKE ? ESCAPE '!'",[$pattern])->orWhereRaw("email LIKE ? ESCAPE '!'",[$pattern])->orWhereRaw("phone LIKE ? ESCAPE '!'",[$pattern]);
                $digits=preg_replace('/[^0-9]/','',$token);
                if(strlen($digits)>=4)$w->orWhereRaw("REPLACE(REPLACE(REPLACE(phone,'+',''),' ',''),'-','') LIKE ? ESCAPE '!'",[$this->like(strlen($digits)>=10?substr($digits,-10):$digits)]);
                if(ctype_digit($token))$w->orWhere('id',$token);
            });}
            foreach($customers->orderByDesc('id')->limit(8)->get(['id','name','phone','email']) as $customer){
                $items[]=['group'=>'customers','type'=>'Customer / কাস্টমার','title'=>$customer->name,'subtitle'=>implode(' · ',array_filter([$customer->phone,$customer->email])),'url'=>route('customers.index',['customer_id'=>$customer->id])];
            }
        }
        foreach([[Brand::class,'brands.edit','brands','Brand / ব্র্যান্ড'],[Category::class,'categories.edit','categories','Category / ক্যাটাগরি'],[FlashDeal::class,'flash_deals.edit','deals','Flash deal / ফ্ল্যাশ ডিল']] as [$model,$route,$group,$type]){
            if(!$this->allowed($route))continue;
            $column=$group==='deals'?'title':'name';$query=$model::query();
            foreach($tokens as $token){$pattern=$this->like($token);$query->where(function($w)use($column,$token,$pattern){$w->whereRaw("$column LIKE ? ESCAPE '!'",[$pattern])->orWhereRaw("slug LIKE ? ESCAPE '!'",[$pattern]);if(ctype_digit($token))$w->orWhere('id',$token);});}
            foreach($query->orderByDesc('id')->limit(5)->get(['id',$column]) as $row)$items[]=['group'=>$group,'type'=>$type,'title'=>$row->$column,'subtitle'=>'#'.$row->id,'url'=>route($route,['id'=>$row->id,'lang'=>config('app.locale')])];
        }
        if($this->allowed('admin.media-center')){
            $uploads=Upload::query()->whereNotIn('id',\App\Services\UnusedImageCleaner::quarantinedIds());
            foreach($tokens as $token){$pattern=$this->like($token);$uploads->where(function($w)use($pattern,$token){$w->whereRaw("file_original_name LIKE ? ESCAPE '!'",[$pattern]);if(ctype_digit($token))$w->orWhere('id',$token);});}
            foreach($uploads->orderByDesc('id')->limit(5)->get(['id','file_original_name','type']) as $file)$items[]=['group'=>'media','type'=>'Media / ফাইল','title'=>$file->file_original_name,'subtitle'=>'#'.$file->id.' · '.$file->type,'url'=>route('admin.media-center',['q'=>$file->file_original_name])];
        }
        return response()->json(['items'=>$items,'limited'=>true])->header('Cache-Control','private, no-store')->header('Pragma','no-cache');
    }
}
