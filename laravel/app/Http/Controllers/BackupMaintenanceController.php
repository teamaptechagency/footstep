<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\{BackupStorageManager,WebsiteStorageUsage,ReleaseRollbackPreview,SecureReleaseManager};

class BackupMaintenanceController extends Controller
{
    private function admin()
    {
        abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
    }
    private function storage(){return new BackupStorageManager(base_path(),dirname(base_path()).'/footstep-site-backups',config('app.key'));}
    private function scanner(){return new WebsiteStorageUsage(base_path(),dirname(base_path()).'/footstep-site-backups');}
    private function json($data){return response()->json($data)->header('Cache-Control','private, no-store');}
    public function inventory()
    {
        $this->admin();return $this->json($this->storage()->listing());
    }
    public function deleteFailed(Request $request)
    {
        $this->admin();$data=$request->validate(['ids'=>'required|array|min:1|max:100','ids.*'=>'required|string|regex:/^[a-f0-9]{32}$/D','password'=>'required|string|max:200','confirm'=>'required|in:DELETE FAILED']);
        if(!Hash::check($data['password'],auth()->user()->password))return $this->json(['message'=>'Current admin password is incorrect.'])->setStatusCode(422);
        try{return $this->json($this->storage()->deleteFailed($data['ids']));}
        catch(\RuntimeException $e){return $this->json(['message'=>$e->getMessage()])->setStatusCode(409);}
    }
    public function deleteSelected(Request $request)
    {
        $this->admin();$data=$request->validate(['ids'=>'required|array|min:1|max:100','ids.*'=>'required|string|regex:/^[a-f0-9]{32}$/D','password'=>'required|string|max:200','confirm'=>'required|in:DELETE BACKUPS']);
        if(!Hash::check($data['password'],auth()->user()->password))return $this->json(['message'=>'Current admin password is incorrect.'])->setStatusCode(422);
        try{return $this->json($this->storage()->deleteSelected($data['ids']));}
        catch(\RuntimeException $e){return $this->json(['message'=>$e->getMessage()])->setStatusCode(409);}
    }
    public function scanStart()
    {
        $this->admin();return $this->json($this->scanner()->start());
    }
    public function scanStep($id)
    {
        $this->admin();return $this->json($this->scanner()->step($id));
    }
    public function rollbackPreview()
    {
        $this->admin();$manager=new SecureReleaseManager(base_path(),dirname(base_path()).'/footstep-update-data',config('app.key'));
        return $this->json(ReleaseRollbackPreview::current($manager->state(),true));
    }
}
