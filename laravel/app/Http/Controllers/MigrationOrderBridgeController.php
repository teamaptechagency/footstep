<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class MigrationOrderBridgeController extends Controller
{
    public function index(){
        abort_unless(auth()->user()->user_type==='admin',403);
        $dir=storage_path('app/migration-order-bridge');
        $cfg=is_file($dir.'/connection.json')?json_decode(file_get_contents($dir.'/connection.json'),true):[];
        $status=is_file($dir.'/status.json')?json_decode(file_get_contents($dir.'/status.json'),true):[];
        $enabled=!empty($cfg['enabled']);$expires=$cfg['expires_at']??0;
        $counts=[];$issues=collect();
        if(Schema::hasTable('migration_order_bridge')){
            $counts=DB::table('migration_order_bridge')->select('status',DB::raw('COUNT(*) AS total'))->groupBy('status')->pluck('total','status');
            $issues=DB::table('migration_order_bridge')->whereIn('status',['error','conflict'])->orderByDesc('synced_at')->limit(50)->get(['source_id','order_id','status','reason','synced_at']);
        }
        return response()->view('backend.platform.migration_order_bridge',compact('enabled','expires','status','counts','issues'))->header('Cache-Control','private, no-store');
    }
    public function toggle(Request $request){
        abort_unless(auth()->user()->user_type==='admin',403);
        $request->validate(['enabled'=>'required|boolean']);
        $path=storage_path('app/migration-order-bridge/connection.json');abort_unless(is_file($path),422,'Bridge has not been provisioned.');
        $cfg=json_decode(file_get_contents($path),true);
        abort_if($request->boolean('enabled')&&time()>(int)$cfg['expires_at'],422,'Migration bridge expired.');
        $cfg['enabled']=$request->boolean('enabled');
        $tmp=$path.'.admin.tmp';abort_if(file_put_contents($tmp,json_encode($cfg),LOCK_EX)===false,500);chmod($tmp,0600);abort_unless(rename($tmp,$path),500);
        return redirect()->route('platform.migration-orders')->with('bridge_notice',$cfg['enabled']?'Bridge চালু করা হয়েছে; পরের minute-এ source পরীক্ষা করবে।':'Bridge বন্ধ। বর্তমানে চলা batch শেষ হতে পারে।');
    }
}
