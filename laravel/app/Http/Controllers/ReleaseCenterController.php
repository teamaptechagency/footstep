<?php
namespace App\Http\Controllers;

use App\Services\SecureReleaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ReleaseCenterController extends Controller
{
    public function manager()
    {
        return new SecureReleaseManager(base_path(), dirname(base_path()).'/footstep-update-data', config('app.key'));
    }

    private function authorizeAdmin()
    {
        abort_unless(auth()->check() && auth()->user()->user_type === 'admin',403);
    }

    private function confirmPassword(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate(['password'=>'required|string|max:200']);
        if(!Hash::check($request->password,auth()->user()->password))throw ValidationException::withMessages(['password'=>'Admin password does not match.']);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $manager=$this->manager();$preview=null;$problem=null;
        try{if($request->filled('package'))$preview=['id'=>$request->query('package'),'manifest'=>$manager->preview($request->query('package'))];}
        catch(\RuntimeException $e){$problem=$e->getMessage();}
        return view('backend.platform.releases',['releaseState'=>$manager->state(),'keyReady'=>$manager->keyReady(),'preview'=>$preview,'problem'=>$problem,'pending'=>$manager->pending(),'auditEvents'=>$manager->events()]);
    }

    public function upload(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate(['package'=>'required|file|max:25600']);
        try{$staged=$this->manager()->stage($request->file('package')->getRealPath());}
        catch(\RuntimeException $e){throw ValidationException::withMessages(['package'=>$e->getMessage()]);}
        return redirect()->route('platform.releases',['package'=>$staged['id']]);
    }

    public function install(Request $request)
    {
        $this->confirmPassword($request);
        $request->validate(['package'=>'required|regex:/^[a-f0-9]{32}$/']);
        try{$this->manager()->install($request->package,auth()->id());}
        catch(\RuntimeException $e){throw ValidationException::withMessages(['package'=>$e->getMessage()]);}
        return redirect()->route('platform.releases')->with('platform_success','Upgrade installed. File checksums verified and rollback backup retained.');
    }

    public function rollback(Request $request)
    {
        $this->confirmPassword($request);
        try{$this->manager()->rollback(auth()->id());}
        catch(\RuntimeException $e){throw ValidationException::withMessages(['rollback'=>$e->getMessage()]);}
        return redirect()->route('platform.releases')->with('platform_success','Previous version restored.');
    }
}
