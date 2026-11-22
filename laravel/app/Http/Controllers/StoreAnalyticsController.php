<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\AnalyticsStorage;
use App\Services\StoreAnalytics;
use App\Services\AdminDashboardAppearance;
class StoreAnalyticsController extends Controller {
    private function authorizeReport(){
        $user=auth()->user();$permissions=$user&&$user->user_type==='staff'?json_decode($user->staff->role->permissions??'[]',true):[];
        abort_unless($user&&($user->user_type==='admin'||in_array('1',$permissions?:[])),403);
    }
    public function index(Request $request){
        $this->authorizeReport();$request->validate(['period'=>'nullable|in:day,yesterday,week,month,year','source'=>['nullable','string','max:191','regex:/^[a-zA-Z0-9.-]+$/D'],'product_id'=>'nullable|integer|min:1']);
        return view('backend.analytics.index',['report'=>StoreAnalytics::report($request->input('period','day')),'acquisition'=>\App\Services\AnalyticsAcquisition::report($request->input('period','day'),$request->input('source'),$request->input('product_id'))]);
    }
    public function install(){
        abort_unless(auth()->user()->user_type==='admin',403);AnalyticsStorage::install();
        return redirect()->route('admin.analytics')->with('platform_success','Analytics storage enabled. New visits will appear as they arrive. / Analytics চালু হয়েছে; নতুন visit থেকে তথ্য জমবে।');
    }
    public function appearance(Request $request){
        abort_unless(auth()->user()->user_type==='admin',403);$data=$request->validate(['dashboard_layout'=>'required|in:classic,modern']);AdminDashboardAppearance::save($data['dashboard_layout']);
        return redirect()->route('platform.appearance')->with('platform_success','Dashboard design saved. / Dashboard design সেভ হয়েছে।');
    }
}
