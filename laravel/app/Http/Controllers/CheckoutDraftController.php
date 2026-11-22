<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use App\Services\CheckoutDrafts as Drafts;
use App\Services\AnalyticsTracker;
use App\Services\OnePageCheckout;
class CheckoutDraftController extends Controller {
    private function authorizeSales(){
        $user=auth()->user();$permissions=$user&&$user->user_type==='staff'?json_decode($user->staff->role->permissions??'[]',true):[];
        abort_unless($user&&($user->user_type==='admin'||in_array('3',$permissions?:[])),403);
    }
    public function capture(Request $request){
        if(!Drafts::ready()||AnalyticsTracker::staff($request->user())||AnalyticsTracker::bot($request->userAgent())||$request->header('DNT')==='1'||$request->header('Sec-GPC')==='1')return response('',204);
        $data=$request->validate(['checkout_draft_token'=>'required|regex:/^[a-f0-9]{32}$/D','channel'=>'required|in:cart,direct','name'=>'nullable|string|max:191','phone'=>'nullable|string|max:25','address'=>'nullable|string|max:255','city'=>'nullable|string|max:100','whatsapp_phone'=>'nullable|string|max:25','whatsapp_same'=>'nullable|boolean','product_id'=>'required_if:channel,direct|integer','stock_id'=>'required_if:channel,direct|integer','quantity'=>'nullable|integer|min:1|max:99']);
        $hash=Drafts::hash($data['checkout_draft_token']);$owner=Drafts::owner($request);$existing=DB::table(Drafts::TABLE)->where('token_hash',$hash)->first();
        if($existing){abort_unless(hash_equals($existing->owner_hash,$owner),403);if($existing->status==='completed')return response('',204);}
        if(!Drafts::hasPhone($data['phone']??'')){
            // Clearing a number hides an existing open draft too. Completed orders are untouched.
            if($existing){DB::table(Drafts::TABLE)->where('token_hash',$hash)->where('owner_hash',$owner)->where('status','open')->update(['has_details'=>false,'details'=>null]);\Illuminate\Support\Facades\Cache::forget('checkout_drafts_phone_ids_v1');}
            return response()->json(['ok'=>true,'skipped'=>'phone_required']);
        }
        $items=[];
        if($data['channel']==='cart'){
            foreach(OnePageCheckout::carts($request) as $cart)$items[]=['id'=>$cart->product_id,'name'=>$cart->product->name,'variant'=>$cart->variation,'quantity'=>$cart->quantity];
        }else{
            $product=filter_products(\App\Models\Product::where('published',1)->where('id',$data['product_id']))->with('stocks')->first();
            abort_unless($product,422);$stock=$product->stocks->firstWhere('id',(int)$data['stock_id']);abort_unless($stock,422);
            $items[]=['id'=>$product->id,'name'=>$product->name,'variant'=>$stock->variant,'quantity'=>(int)($data['quantity']??1)];
        }
        if(!count($items))return response('',204);
        $details=[];foreach(['name','phone','address','city','whatsapp_phone'] as $key)$details[$key]=trim((string)($data[$key]??''));$details['whatsapp_same']=$request->boolean('whatsapp_same');
        $hasDetails=(bool)array_filter(array_intersect_key($details,array_flip(['name','phone','address','city','whatsapp_phone'])));
        $now=Carbon::now('UTC');$row=['channel'=>$data['channel'],'has_details'=>$hasDetails,'details'=>$hasDetails?Crypt::encryptString(json_encode($details,JSON_UNESCAPED_UNICODE)):null,'items'=>json_encode($items,JSON_UNESCAPED_UNICODE),'last_seen'=>$now];
        DB::table(Drafts::TABLE)->insertOrIgnore($row+['token_hash'=>$hash,'owner_hash'=>$owner,'status'=>'open','first_seen'=>$now]);
        DB::table(Drafts::TABLE)->where('token_hash',$hash)->where('owner_hash',$owner)->where('status','open')->update($row);
        \Illuminate\Support\Facades\Cache::forget('checkout_drafts_phone_ids_v1');
        return response()->json(['ok'=>true]);
    }
    public function index(Request $request){
        $this->authorizeSales();$data=$request->validate(['state'=>'nullable|in:incomplete,active,completed,all','details'=>'nullable|in:yes,no','channel'=>'nullable|in:cart,direct','period'=>'nullable|in:all,day,yesterday,week,month,year']);
        $ready=Drafts::ready();$rows=null;$counts=['incomplete'=>0,'direct'=>0,'active'=>0,'completed'=>0];$productMap=collect();
        if($ready){
            $query=DB::table(Drafts::TABLE)->whereIn('id',Drafts::contactIds());$cutoff=Carbon::now('UTC')->subMinutes(15);
            $incomplete=(clone $query)->where('status','open')->where('last_seen','<=',$cutoff);$counts['incomplete']=(clone $incomplete)->count();$counts['direct']=(clone $incomplete)->where('channel','direct')->count();$counts['active']=(clone $query)->where('status','open')->where('last_seen','>',$cutoff)->count();$counts['completed']=(clone $query)->where('status','completed')->count();
            $state=$data['state']??'incomplete';if($state==='incomplete')$query=$incomplete;elseif($state==='active')$query->where('status','open')->where('last_seen','>',$cutoff);elseif($state==='completed')$query->where('status','completed');
            if(!empty($data['channel']))$query->where('channel',$data['channel']);
            if(!empty($data['period'])&&$data['period']!=='all'){$window=\App\Services\StoreAnalytics::window($data['period']);$query->where('last_seen','>=',$window['start'])->where('last_seen','<',$window['end']);}
            $rows=$query->orderByDesc('last_seen')->paginate(20)->appends($request->query());
            $ids=collect($rows->items())->flatMap(function($row){return collect(json_decode($row->items?:'[]',true)?:[])->pluck('id');})->filter()->unique();
            $productMap=\App\Models\Product::whereIn('id',$ids)->setEagerLoads([])->get(['id','name','slug','thumbnail_img','published'])->keyBy('id');
        }
        return view('backend.sales.incomplete.index',compact('ready','rows','counts','productMap'));
    }
}
