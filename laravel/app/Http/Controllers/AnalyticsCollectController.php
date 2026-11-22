<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AnalyticsStorage as Storage;
use App\Services\AnalyticsTracker as Tracker;
class AnalyticsCollectController extends Controller {
    public function collect(Request $request){
        if(!Storage::ready()||Tracker::staff($request->user())||Tracker::bot($request->userAgent())||$request->header('DNT')==='1'||$request->header('Sec-GPC')==='1')return response('',204);
        $data=$request->validate(['kind'=>'required|in:view,pulse','view'=>'required|regex:/^[a-f0-9]{32}$/D','payload'=>'required_if:kind,view|nullable|string|max:6000','active_seconds'=>'nullable|integer|min:0|max:86400','referrer'=>'nullable|string|max:2048']);
        $visitor=Tracker::visitor($request->cookie('footstep_visitor'));$now=Carbon::now('UTC');
        if($data['kind']==='view'){
            $page=Tracker::decode($data['payload']??'');abort_unless($page,422,'Invalid page token.');
            $host=strtolower((string)parse_url($data['referrer']??'',PHP_URL_HOST));
            if(!$host||$host===strtolower($request->getHost())||!preg_match('/^[a-z0-9.-]{1,190}$/D',$host))$host='direct';
            $ua=strtolower((string)$request->userAgent());$device=preg_match('/ipad|tablet/',$ua)?'tablet':(preg_match('/mobile|android|iphone/',$ua)?'mobile':'desktop');
            try{
                DB::table(Storage::TABLE)->insertOrIgnore(['view_token'=>$data['view'],'visitor_hash'=>$visitor['hash'],'path_hash'=>hash('sha256',$page['path']),'path'=>$page['path'],'title'=>$page['title'],'product_id'=>$page['product']?:null,'device'=>$device,'referrer'=>$host,'customer'=>$request->user()&&$request->user()->user_type==='customer','active_seconds'=>0,'created_at'=>$now,'updated_at'=>$now]);
            }catch(\Throwable $e){return response('',204);}
            return response()->json(['ok'=>true])->cookie('footstep_visitor',$visitor['cookie'],525600,'/',null,$request->isSecure(),true,false,'Lax');
        }
        $row=DB::table(Storage::TABLE)->where('view_token',$data['view'])->where('visitor_hash',$visitor['hash'])->first(['id','active_seconds','created_at','updated_at']);
        if(!$row)return response('',204);
        $elapsed=max(0,$now->diffInSeconds(Carbon::parse($row->created_at,'UTC')));
        $seconds=min((int)($data['active_seconds']??0),$elapsed,(int)$row->active_seconds+45,86400);
        if($seconds>$row->active_seconds&&$now->diffInSeconds(Carbon::parse($row->updated_at,'UTC'))>=5)DB::table(Storage::TABLE)->where('id',$row->id)->where('active_seconds','<',$seconds)->update(['active_seconds'=>$seconds,'updated_at'=>$now]);
        return response('',204);
    }
}
