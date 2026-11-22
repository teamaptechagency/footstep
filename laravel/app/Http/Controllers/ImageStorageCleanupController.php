<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Upload;
use App\Services\{OptimizedCopyCleanup,SavedImageCleanup,UnusedImageCleaner};

class ImageStorageCleanupController extends Controller
{
    private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
    private function store(){return new OptimizedCopyCleanup(public_path(),storage_path('app/image-copy-cleanup'),config('app.key'),storage_path('app/image-manifests'),Upload::withTrashed()->where('file_name','like','uploads/optimized/%')->pluck('file_name')->all(),function($relative){$info=\App\Services\OptimizedImages::inspect((object)['file_name'=>$relative]);return $info['source_key']??null;});}
    private function json($data){return response()->json($data)->header('Cache-Control','private, no-store');}
    private function run($callback){try{return $this->json($callback());}catch(\Illuminate\Database\QueryException $e){return $this->json(['message'=>'Database reference check failed; cleanup stopped. No automatic retry.'])->setStatusCode(503);}catch(\RuntimeException $e){return $this->json(['message'=>$e->getMessage()])->setStatusCode(409);}catch(\Throwable $e){return $this->json(['message'=>'Image cleanup could not finish. Refresh the list and check hosting storage/permissions.'])->setStatusCode(500);}}
    public function index(){$this->admin();return response()->view('backend.platform.image_storage_cleanup')->header('Cache-Control','private, no-store');}
    public function versions(Request $r){$this->admin();$d=$r->validate(['cursor'=>'required|integer|min:0','days'=>'required|integer|in:0,7,30,90,180']);return $this->run(function()use($d){return SavedImageCleanup::listing($d['cursor'],$d['days']);});}
    public function start(Request $r){$this->admin();$d=$r->validate(['days'=>'required|integer|in:0,7,30,90,180']);return $this->run(function()use($d){return $this->store()->start($d['days']);});}
    public function step($id){$this->admin();return $this->run(function()use($id){$data=$this->store()->step($id);foreach($data['rows'] as &$row)$row['url']=my_asset('uploads/optimized/'.$row['name']);return $data;});}
    public function delete(Request $r){
        $this->admin();$d=$r->validate(['kind'=>'required|in:optimized,version','password'=>'required|string|max:200','confirm'=>'required|in:DELETE OLD IMAGES','scan'=>'required_if:kind,optimized|nullable|string|regex:/^[a-f0-9]{32}$/D','names'=>'required_if:kind,optimized|array|max:3','names.*'=>['string','regex:/^[a-f0-9]{32}-[0-9]{1,5}\.(jpg|webp)$/D'],'upload_id'=>'required_if:kind,version|integer|min:1','sha256'=>'required_if:kind,version|string|regex:/^[a-f0-9]{64}$/D','days'=>'required|integer|in:0,7,30,90,180']);
        if(!Hash::check($d['password'],auth()->user()->password))return $this->json(['message'=>'Current admin password is incorrect.'])->setStatusCode(422);
        return $this->run(function()use($d){
            if($d['kind']==='version')return SavedImageCleanup::delete($d['upload_id'],$d['sha256'],$d['days']);
            return $this->store()->delete($d['scan'],$d['names'],function($relative){return Upload::withTrashed()->where('file_name',$relative)->exists()||SavedImageCleanup::codeReferenced($relative)||UnusedImageCleaner::referenced((object)['id'=>0,'file_name'=>$relative],false);});
        });
    }
}
