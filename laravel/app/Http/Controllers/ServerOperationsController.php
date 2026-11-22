<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class ServerOperationsController extends Controller {
 public function index(){
  abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
  $path='/var/lib/footstep-status/status.json';$status=is_readable($path)&&filesize($path)<32768?(json_decode(file_get_contents($path),true)?:[]):[];
  $heartbeat=storage_path('app/queue-worker-heartbeat.json');$queue=is_readable($heartbeat)&&filesize($heartbeat)<4096?(json_decode(file_get_contents($heartbeat),true)?:[]):[];
  return response()->view('backend.platform.server_operations',['status'=>$status,'queue'=>$queue,'pending'=>DB::table('jobs')->count(),'failed'=>DB::table('failed_jobs')->count()])->header('Cache-Control','private, no-store');
 }
}
