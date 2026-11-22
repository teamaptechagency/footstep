<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PrepareUploadedImage implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public $uploadId;
    public $tries=3;
    public $backoff=30;
    public $timeout=50;
    public function __construct($id){$this->uploadId=(int)$id;$this->onConnection('database');}
    public function handle(){\App\Services\OptimizedImages::buildUpload($this->uploadId);}
    public function failed($error){
        try{\App\Services\CatalogOperationLog::append(['at'=>gmdate('c'),'request_id'=>bin2hex(random_bytes(8)),'operation'=>'PrepareUploadedImage@failed','entity_id'=>$this->uploadId,'http'=>500,'duration_ms'=>0,'code'=>'image_prepare_failed','problem'=>'Image upload হয়েছে, কিন্তু background optimization শেষ হয়নি।','solution'=>'Original image ব্যবহার করা যাবে। Image Tools থেকে retry করুন; worker, image decoder ও storage quota পরীক্ষা করুন।','invalid_fields'=>[],'exception'=>'BackgroundJobFailed','source'=>null]);}catch(\Throwable $ignored){}
    }
}
