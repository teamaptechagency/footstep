<?php
namespace App\Http\Controllers;

use App\Models\{User,Product,ProductStock,Order,Cart,Category};
use App\Services\{StorefrontV3Catalog as Catalog,BackendModules,OnePageCheckout,StoreOperations};
use App\Services\Checkout\{ProductQuote,VariantSelection};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,RateLimiter,Crypt,Notification};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class NativeMobileController extends Controller
{
    private function output($data,$status=200){return response()->json($data,$status)->header('Cache-Control','private, no-store')->header('X-Content-Type-Options','nosniff');}
    private function admin(Request $r,$module=null){abort_unless($r->user()&&$r->user()->user_type==='admin'&&!$r->user()->banned,403,'Administrator access required.');if($module)abort_unless(BackendModules::enabled($module),403,'This module is disabled by store settings.');}
    private function customer(Request $r){abort_unless($r->user()&&$r->user()->user_type==='customer',403,'Use a customer account to place orders.');}
    private function profile($u){return ['id'=>(int)$u->id,'name'=>$u->name,'email'=>$u->email,'phone'=>$u->phone,'role'=>$u->user_type];}
    private function token($u){$raw=bin2hex(random_bytes(32));$expires=now()->addDays(14);DB::table('footstep_mobile_tokens')->insert(['user_id'=>$u->id,'token_hash'=>hash('sha256',$raw),'expires_at'=>$expires,'created_at'=>now()]);return $this->output(['user'=>$this->profile($u),'token'=>$raw,'expires_at'=>$expires->toIso8601String()]);}
    public function privacyPage(){return response()->view('mobile.privacy',['email'=>(string)get_setting('contact_email'),'phone'=>(string)get_setting('contact_phone')])->header('Cache-Control','no-store')->header('X-Frame-Options','DENY');}
    public function support(){return $this->output(['phone'=>(string)get_setting('contact_phone'),'email'=>(string)get_setting('contact_email')]);}
    public function deletionPage(){return response()->view('mobile.account_deletion',['email'=>(string)get_setting('contact_email'),'phone'=>(string)get_setting('contact_phone')])->header('Cache-Control','no-store')->header('X-Content-Type-Options','nosniff')->header('Referrer-Policy','no-referrer')->header('X-Frame-Options','DENY');}
    public function deleteAccount(Request $r){$d=$r->validate(['password'=>'required|string|max:255']);\App\Services\NativeAccountDeletion::delete($r->user(),$d['password']);return $this->output(['ok'=>true,'message'=>'Your account and personal profile have been deleted. Completed order totals remain as anonymous business records.']);}
    public function health(){return $this->output(['service'=>'Foot Step native API','version'=>1,'checkout'=>'cash_on_delivery']);}
    public function login(Request $r){
        $d=$r->validate(['identity'=>'required|string|max:191','password'=>'required|string|max:255']);
        $identity=trim($d['identity']);$phone=OnePageCheckout::phone($identity);
        $key='native-login:'.hash('sha256',mb_strtolower($identity));
        abort_if(RateLimiter::tooManyAttempts($key,8),429,'Too many sign-in attempts. Try again later.');RateLimiter::hit($key,900);
        $u=User::where(function($q)use($identity,$phone){$q->where('email',$identity);if(preg_match('/^01[3-9][0-9]{8}$/D',$phone))$q->orWhere('phone',$phone)->orWhere('phone','+88'.$phone)->orWhere('phone','88'.$phone);})->first();
        // Perform a password check even for unknown accounts. Same failure for missing/blocked accounts.
        $valid=Hash::check($d['password'],$u&&$u->password?$u->password:'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi');
        abort_unless($u&&$valid&&!$u->banned&&in_array($u->user_type,['admin','customer'],true),401,'Email/phone or password is incorrect.');
        abort_if($u->user_type==='customer'&&get_setting('email_verification')==1&&!$u->email_verified_at,403,'Verify your email from the store website before signing in.');
        RateLimiter::clear($key);DB::table('footstep_mobile_tokens')->where('user_id',$u->id)->where('expires_at','<',now())->delete();return $this->token($u);
    }
    public function register(Request $r){
        $d=$r->validate(['name'=>'required|string|max:191','email'=>'required|email|max:191|unique:users,email','phone'=>'required|regex:/^01[3-9][0-9]{8}$/D|unique:users,phone','password'=>'required|string|min:10|max:128|confirmed']);
        abort_if(get_setting('email_verification')==1,409,'Please register and verify your account on the store website, then sign in here.');
        $u=DB::transaction(function()use($d){$u=new User();$u->name=$d['name'];$u->email=mb_strtolower($d['email']);$u->phone=$d['phone'];$u->password=Hash::make($d['password']);$u->user_type='customer';$u->email_verified_at=now();$u->save();DB::table('customers')->insert(['user_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]);return $u;});return $this->token($u);
    }
    public function me(Request $r){return $this->output(['user'=>$this->profile($r->user()),'delivery'=>OnePageCheckout::defaults($r->user())]);}
    public function logout(Request $r){DB::table('footstep_mobile_tokens')->where('id',$r->attributes->get('native_mobile_token_id'))->delete();return $this->output(['ok'=>true]);}
    public function home(){
        $home=Catalog::home();$site=$home['site'];return $this->output(['site'=>['logo'=>$site['logo'],'about'=>$site['about'],'phone'=>$site['contactPhone'],'categories'=>$site['categories']],'latest'=>array_slice($home['latest'],0,12),'best'=>array_slice($home['best'],0,8),'today'=>array_slice($home['today'],0,12),'sliders'=>$home['sliders'],'fees'=>OnePageCheckout::FEES]);
    }
    public function products(Request $r){return (new StorefrontV3Controller())->products($r);}
    public function product($id){$p=Catalog::query()->whereKey($id)->firstOrFail();abort_if($p->external_link,422,'This external product cannot be ordered in the app.');$detail=Catalog::detail($p);foreach($detail['variants'] as $i=>$variant)$detail['variants'][$i]['stock_id']=(int)$p->stocks->firstWhere('variant',$variant['variant'])->id;return $this->output(['product'=>$detail]);}
    private function ordersQuery(Request $r){$q=Order::query();if($r->user()->user_type==='admin')$this->admin($r,'sales.orders');else $q->where('user_id',$r->user()->id);return $q;}
    private function orderDto($o,$detail=false){
        $a=json_decode($o->shipping_address,true)?:[];$d=['id'=>(int)$o->id,'code'=>(string)$o->code,'customer'=>$a['name']??'Customer','total'=>(float)$o->grand_total,'status'=>$o->delivery_status,'payment_status'=>$o->payment_status,'payment_type'=>$o->payment_type,'created_at'=>optional($o->created_at)->toIso8601String(),'revision'=>$this->orderRevision($o)];
        if($detail){$d['address']=array_intersect_key($a,array_flip(['name','phone','address','city','email']));$d['lines']=$o->orderDetails->map(fn($line)=>['id'=>(int)$line->id,'product_id'=>(int)$line->product_id,'name'=>($line->product_name_snapshot ?: (optional($line->product)->name??'Unavailable product')),'variant'=>$line->variation,'quantity'=>(int)$line->quantity,'total'=>(float)$line->price+(float)$line->tax,'shipping'=>(float)$line->shipping_cost])->values()->all();}
        return $d;
    }
    private function orderRevision($o){return hash('sha256',json_encode([$o->id,(string)$o->updated_at,$o->delivery_status,$o->payment_status,$o->grand_total,$o->shipping_address]));}
    public function orders(Request $r){$d=$r->validate(['page'=>'nullable|integer|min:1|max:10000','status'=>'nullable|in:pending,confirmed,picked_up,on_the_way,on_delivery,delivered,cancelled','search'=>'nullable|string|max:100']);$q=$this->ordersQuery($r);if(!empty($d['status']))$q->where('delivery_status',$d['status']);if(!empty($d['search']))$q->where('code','like','%'.str_replace(['%','_'],['\\%','\\_'],$d['search']).'%');$p=$q->latest('id')->paginate(20);return $this->output(['data'=>$p->map(fn($o)=>$this->orderDto($o))->all(),'meta'=>['page'=>$p->currentPage(),'last_page'=>$p->lastPage(),'total'=>$p->total()]]);}
    public function order(Request $r,$id){$o=$this->ordersQuery($r)->with('orderDetails.product')->whereKey($id)->firstOrFail();return $this->output(['order'=>$this->orderDto($o,true)]);}
    private function selections(Request $r,$locked=false){
        $d=$r->validate(['items'=>'required|array|min:1|max:20','items.*.stock_id'=>'required|integer|distinct','items.*.product_id'=>'required|integer','items.*.quantity'=>'required|integer|min:1|max:99','city'=>'required|in:In Dhaka City,Out Of Dhaka City']);
                usort($d['items'],fn($a,$b)=>[$a['product_id'],$a['stock_id']]<=>[$b['product_id'],$b['stock_id']]);
        $lines=[];$subtotal=0;foreach($d['items'] as $item){
            if($locked){
                $p=filter_products(Product::where('published',1)->whereKey($item['product_id']))->lockForUpdate()->firstOrFail();
                abort_if($p->external_link,422,'External products cannot be ordered in the app.');
                $stock=ProductStock::where('product_id',$p->id)->whereKey($item['stock_id'])->lockForUpdate()->first();
                abort_unless($stock&&$stock->qty>=$item['quantity']&&$item['quantity']>=max(1,(int)$p->min_qty),422,'Selected size or quantity is unavailable.');
                $s=ProductQuote::selection($p,$stock,$item['quantity']);
            }else{$fake=Request::create('/','POST',$item);$s=VariantSelection::fromRequest($fake);}$unit=round($s['price']+$s['tax'],2);$lineTotal=round($unit*$s['qty'],2);$subtotal+=$lineTotal;$lines[]=['product_id'=>(int)$s['product']->id,'stock_id'=>(int)$s['stock']->id,'name'=>$s['product']->name,'variant'=>$s['stock']->variant,'quantity'=>$s['qty'],'unit_price'=>$unit,'total'=>$lineTotal,'_selection'=>$s];}
        usort($lines,fn($a,$b)=>$a['stock_id']<=>$b['stock_id']);$shipping=OnePageCheckout::fee($d['city'],array_sum(array_column($lines,'quantity')));return ['lines'=>$lines,'subtotal'=>round($subtotal,2),'shipping'=>$shipping,'total'=>round($subtotal+$shipping,2),'city'=>$d['city']];
    }
    private function quotePayload($q,$user){$lines=array_map(fn($l)=>array_diff_key($l,['_selection'=>true]),$q['lines']);return ['user_id'=>(int)$user->id,'lines'=>$lines,'city'=>$q['city'],'total'=>$q['total']];}
    public function quote(Request $r){$this->customer($r);$q=$this->selections($r);$payload=$this->quotePayload($q,$r->user());return $this->output($payload+['subtotal'=>$q['subtotal'],'shipping'=>$q['shipping'],'quote'=>Crypt::encryptString(json_encode($payload+['expires'=>time()+600]))]);}
    public function checkout(Request $r){
        $this->customer($r);$d=$r->validate(['request_key'=>'required|uuid','quote'=>'required|string|max:16000']);OnePageCheckout::delivery($r);
        $bodyHash=hash('sha256',json_encode($r->only(['items','city','name','phone','address','email'])));
        $result=DB::transaction(function()use($r,$d,$bodyHash){
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            $prior=DB::table('footstep_mobile_checkouts')->where('user_id',$r->user()->id)->where('request_key',$d['request_key'])->first();
            if($prior){abort_unless(hash_equals($prior->payload_hash,$bodyHash)&&$prior->combined_order_id,409,'This checkout key was already used. Review your orders.');return Order::where('combined_order_id',$prior->combined_order_id)->where('user_id',$r->user()->id)->get()->map(fn($o)=>$this->orderDto($o))->all();}
            try{$signed=json_decode(Crypt::decryptString($d['quote']),true,512,JSON_THROW_ON_ERROR);}catch(\Throwable $e){abort(422,'Quote is invalid. Review the total again.');}
            abort_unless(is_array($signed)&&($signed['expires']??0)>=time(),409,'Quote expired. Review the total again.');unset($signed['expires']);
            $q=$this->selections($r,true);abort_unless(hash_equals(hash('sha256',json_encode($signed)),hash('sha256',json_encode($this->quotePayload($q,$r->user())))),409,'Prices or selection changed. Review the total again.');
            $marker=DB::table('footstep_mobile_checkouts')->insertGetId(['user_id'=>$r->user()->id,'request_key'=>$d['request_key'],'payload_hash'=>$bodyHash,'created_at'=>now()]);
            $ids=[];foreach($q['lines'] as $line){$s=$line['_selection'];$c=new Cart();$c->user_id=$r->user()->id;$c->owner_id=\App\Services\SingleVendor::ownerId();$c->product_id=$s['product']->id;$c->variation=$s['stock']->variant;$c->quantity=$s['qty'];$c->price=$s['price'];$c->tax=$s['tax'];$c->shipping_cost=0;$c->shipping_type='home_delivery';$c->discount=0;$c->addon_ids='[]';$c->save();$ids[]=$c->id;}
            $session=app('session')->driver('array');$session->start();$r->setLaravelSession($session);$r->attributes->set('footstep_cart_ids',$ids);
            try{(new OnePageCheckoutController())->store($r);$combined=$session->get('combined_order_id');abort_unless($combined,422,'Order could not be placed.');DB::table('footstep_mobile_checkouts')->where('id',$marker)->update(['combined_order_id'=>$combined]);return Order::where('combined_order_id',$combined)->where('user_id',$r->user()->id)->get()->map(fn($o)=>$this->orderDto($o))->all();}finally{Cart::whereIn('id',$ids)->delete();}
        },3);return $this->output(['orders'=>$result],201);
    }
    public function dashboard(Request $r){$this->admin($r,'dashboard.overview');return $this->output(['orders'=>Order::count(),'pending'=>Order::where('delivery_status','pending')->count(),'products'=>Product::count(),'revenue'=>(float)Order::where('payment_status','paid')->sum('grand_total'),'customers'=>User::where('user_type','customer')->count(),'recent'=>Order::latest('id')->limit(6)->get()->map(fn($o)=>$this->orderDto($o))->all()]);}
    public function menus(Request $r){$this->admin($r);$items=[];foreach(BackendModules::catalog() as $key=>$group){if(!BackendModules::enabled($key))continue;$features=[];foreach($group['features'] as $feature=>$def)if(BackendModules::enabled($key.'.'.$feature))$features[]=['key'=>$key.'.'.$feature,'name'=>$def['label']];$items[]=['key'=>$key,'name'=>$group['label'],'features'=>$features];}return $this->output(['data'=>$items]);}
    private function productRevision($p){return hash('sha256',json_encode([$p->getAttributes(),$p->stocks->map(fn($s)=>$s->getAttributes())->all()]));}
    private function adminProductDto($p){return ['id'=>(int)$p->id,'name'=>$p->name,'price'=>(float)$p->unit_price,'published'=>(bool)$p->published,'category_id'=>(int)$p->category_id,'thumbnail_image'=>\App\Services\OptimizedImages::asset($p->thumbnail_img),'thumbnail_id'=>$p->thumbnail_img,'revision'=>$this->productRevision($p),'stocks'=>$p->stocks->map(fn($s)=>['id'=>(int)$s->id,'variant'=>$s->variant,'price'=>(float)$s->price,'quantity'=>(int)$s->qty])->values()->all()];}
    public function adminProducts(Request $r){$this->admin($r,'products.manage');$d=$r->validate(['page'=>'nullable|integer|min:1|max:10000','search'=>'nullable|string|max:100']);$q=Product::with('stocks');if(!empty($d['search']))$q->where('name','like','%'.str_replace(['%','_'],['\\%','\\_'],$d['search']).'%');$p=$q->latest('id')->paginate(20);return $this->output(['data'=>$p->map(fn($p)=>$this->adminProductDto($p))->all(),'meta'=>['page'=>$p->currentPage(),'last_page'=>$p->lastPage(),'total'=>$p->total()]]);}
    public function adminProduct(Request $r,$id){$this->admin($r,'products.manage');return $this->output(['product'=>$this->adminProductDto(Product::with('stocks')->findOrFail($id)),'categories'=>Catalog::categories()]);}
    public function updateProduct(Request $r,$id){
        $this->admin($r,'products.manage');$d=$r->validate(['revision'=>'required|string|size:64','name'=>'required|string|max:200','published'=>'required|boolean','stocks'=>'required|array|min:1|max:100','stocks.*.id'=>'required|integer|distinct','stocks.*.price'=>'required|numeric|min:0|max:1000000','stocks.*.quantity'=>'required|integer|min:0|max:1000000']);
        $p=DB::transaction(function()use($id,$d){$p=Product::whereKey($id)->lockForUpdate()->firstOrFail();$p->setRelation('stocks',ProductStock::where('product_id',$id)->orderBy('id')->lockForUpdate()->get());abort_unless(hash_equals($this->productRevision($p),$d['revision']),409,'Product changed. Reload before editing.');abort_unless(count($d['stocks'])===$p->stocks->count(),422,'Include every existing size.');foreach($d['stocks'] as $input){$s=$p->stocks->firstWhere('id',$input['id']);abort_unless($s,422,'Size does not belong to this product.');$s->price=$input['price'];$s->qty=$input['quantity'];$s->save();}$p->name=$d['name'];$p->published=$d['published'];$p->unit_price=$p->stocks->min('price');$p->current_stock=$p->stocks->sum('qty');$p->save();DB::table('product_translations')->where('product_id',$id)->where('lang',config('app.locale','en'))->update(['name'=>$d['name'],'updated_at'=>now()]);return $p;});return $this->output(['product'=>$this->adminProductDto($p->fresh('stocks'))]);
    }
    public function createProduct(Request $r){
        $this->admin($r,'products.create');$d=$r->validate(['name'=>'required|string|max:200','category_id'=>'required|integer|exists:categories,id','price'=>'required|numeric|min:1|max:1000000','sizes'=>'required|array|min:1|max:20','sizes.*'=>'required|string|max:20|distinct|regex:/^[a-zA-Z0-9 .-]+$/D','stock'=>'required|integer|min:0|max:1000000']);
        $p=DB::transaction(function()use($d){$attribute=DB::table('attributes')->where('name','like','%size%')->first();abort_unless($attribute,422,'A Size attribute must be configured in the store first.');$p=new Product();$p->name=$d['name'];$p->added_by='admin';$p->user_id=\App\Services\SingleVendor::ownerId();$p->category_id=$d['category_id'];$p->unit_price=$d['price'];$p->published=0;$p->slug=Str::slug($d['name']).'-'.Str::lower(Str::random(8));$p->unit='pair';$p->attributes=json_encode([$attribute->id]);$p->choice_options=json_encode([['attribute_id'=>(string)$attribute->id,'values'=>$d['sizes']]]);$p->colors='[]';$p->variant_product=1;$p->current_stock=count($d['sizes'])*$d['stock'];$p->save();foreach($d['sizes'] as $size){$s=new ProductStock();$s->product_id=$p->id;$s->variant=str_replace(' ','',$size);$s->price=$d['price'];$s->qty=$d['stock'];$s->save();}DB::table('product_translations')->insert(['product_id'=>$p->id,'name'=>$p->name,'unit'=>'pair','lang'=>config('app.locale','en'),'created_at'=>now(),'updated_at'=>now()]);return $p;});return $this->output(['product'=>$this->adminProductDto($p->fresh('stocks'))],201);
    }
    public function orderAction(Request $r,$id){
        $this->admin($r,'sales.manage');$d=$r->validate(['revision'=>'required|string|size:64','action'=>'required|in:confirm,cancel']);$o=DB::transaction(function()use($r,$id,$d){$o=Order::whereKey($id)->lockForUpdate()->firstOrFail();abort_unless(hash_equals($this->orderRevision($o),$d['revision']),409,'Order changed. Reload before updating.');if($d['action']==='cancel'){abort_if($o->payment_status==='paid',409,'Paid orders require a refund review before cancellation.');return StoreOperations::cancel($id);}abort_unless($o->delivery_status==='pending',409,'Only pending orders can be confirmed.');$o->delivery_status='confirmed';$o->save();$o->orderDetails()->update(['delivery_status'=>'confirmed']);\App\Utility\NotificationUtility::sendNotification($o,'confirmed');StoreOperations::audit('mobile_order_confirmed',$id);return $o;});return $this->output(['order'=>$this->orderDto($o)]);
    }
    public function resource(Request $r,$key){
        $module=explode('.',$key)[0];$this->admin($r,$key);abort_unless(isset(BackendModules::catalog()[$module]),404);
        $rows=[];$title=BackendModules::catalog()[$module]['label'];
        if($module==='analytics'){$d=$r->validate(['period'=>'nullable|in:day,yesterday,week,month,year']);return $this->output(\App\Services\NativeAnalytics::resource($key,$d['period']??'day'));}
        if(in_array($key,['products.categories','products.brands','products.attributes','products.colors'],true)){$tables=['products.categories'=>'categories','products.brands'=>'brands','products.attributes'=>'attributes','products.colors'=>'colors'];$rows=DB::table($tables[$key])->orderBy('id','desc')->limit(100)->get(['id','name'])->map(fn($v)=>['title'=>$v->name,'detail'=>'#'.$v->id])->all();}
        elseif($module==='customers'){$rows=User::where('user_type','customer')->latest('id')->limit(50)->get(['id','name','phone','banned'])->map(fn($v)=>['title'=>$v->name,'detail'=>($v->phone?:'No phone').($v->banned?' · Blocked':'')])->all();}
        elseif($module==='sales'){$rows=Order::latest('id')->limit(30)->get()->map(fn($v)=>['title'=>$v->code,'detail'=>$v->delivery_status.' · '.format_price($v->grand_total)])->all();}
        elseif($module==='reports'||$module==='analytics'){$rows=[['title'=>'Paid order value','detail'=>format_price(Order::where('payment_status','paid')->sum('grand_total'))],['title'=>'Total orders','detail'=>(string)Order::count()],['title'=>'Delivered orders','detail'=>(string)Order::where('delivery_status','delivered')->count()],['title'=>'Cancelled orders','detail'=>(string)Order::where('delivery_status','cancelled')->count()]];}
        elseif($module==='products'){$rows=Product::withSum('stocks','qty')->latest('id')->limit(30)->get()->map(fn($v)=>['title'=>$v->name,'detail'=>($v->published?'Published':'Draft').' · '.(int)$v->stocks_sum_qty.' in stock'])->all();}
        elseif(strpos($key,'coupon')!==false){$rows=\App\Models\Coupon::latest('id')->limit(50)->get()->map(fn($v)=>['title'=>$v->code,'detail'=>$v->discount.' '.$v->discount_type])->all();}
        elseif(strpos($key,'flash')!==false){$rows=\App\Models\FlashDeal::latest('id')->limit(50)->get()->map(fn($v)=>['title'=>$v->title,'detail'=>$v->status?'Enabled':'Disabled'])->all();}
        return $this->output(['title'=>$title,'data'=>$rows,'read_only'=>true,'message'=>$rows?'Native overview. Advanced editing is not yet available for this section.':'This section has no native editor yet. Existing website settings remain unchanged.']);
    }
}
