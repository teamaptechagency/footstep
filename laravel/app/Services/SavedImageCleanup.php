<?php
namespace App\Services;

use App\Models\{Upload,Product,BusinessSetting};
use Illuminate\Support\Facades\DB;

/** Discard only explicitly selected restore history; active database references block removal. */
class SavedImageCleanup
{
    public static function codeReferenced($relative,array $additionalFiles=[]){
        $needles=[];foreach(array_merge([$relative],$additionalFiles)as $v){$needles=array_merge($needles,[$v,str_replace('/','\\/',$v),rawurlencode($v)]);};$total=0;$until=microtime(true)+6;
        foreach(['app','config','resources','routes','public/assets']as $folder){
            $root=base_path($folder);if(!is_dir($root))continue;if(is_link($root))throw new \RuntimeException('Code reference folder is a symlink; cleanup retained.');
            $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root,\FilesystemIterator::SKIP_DOTS));
            foreach($it as $file){
                if($file->isLink())throw new \RuntimeException('Code reference symlink found; cleanup retained.');
                if(!$file->isFile()||!preg_match('/\.(php|html?|css|js|json)$/iD',$file->getFilename()))continue;
                $size=$file->getSize();$total+=$size;if($size>8388608||$total>67108864||microtime(true)>$until)throw new \RuntimeException('Code reference check exceeded safe limit; cleanup retained.');
                $text=file_get_contents($file->getPathname());if($text===false)throw new \RuntimeException('Code reference could not be read; cleanup retained.');foreach($needles as $needle)if(strpos($text,$needle)!==false)return true;
            }
        }return false;
    }
    private static function path($upload){$relative=$upload->file_name;if(!is_string($relative)||strpos($relative,'uploads/')!==0||strpos($relative,'..')!==false||strpos($relative,'\\')!==false||!preg_match('/\.(jpe?g|png|webp|gif|bmp|avif)$/iD',$relative))throw new \RuntimeException('Unsafe local image path.');$path=public_path();foreach(explode('/',$relative)as $part){$path.='/'.$part;if(is_link($path))throw new \RuntimeException('Image symlinks are protected.');}return UnusedImageCleaner::path($upload);}
    private static function records(){return BusinessSetting::whereRaw("type LIKE 'product!_image!_versions!_v1!_%' ESCAPE '!'");}
    private static function metadata($row){$data=json_decode($row->value,true);if(!is_array($data)||!isset($data['original'],$data['entries'])||!is_array($data['entries']))throw new \RuntimeException('Saved image history is unreadable. Cleanup blocked.');return $data;}
    private static function stamp($entry,$upload,$path){return max(filemtime($path),strtotime($entry['at']??'')?:0,strtotime((string)$upload->created_at)?:0);}
    public static function listing($after,$days){
        $settings=self::records()->where('id','>',$after)->orderBy('id')->limit(10)->get();$rows=[];$seen=[];$cutoff=time()-$days*86400;
        foreach($settings as $setting){
            try{$data=self::metadata($setting);}catch(\RuntimeException $e){continue;}
            $entries=$data['entries'];if($data['original']&&!isset($entries[(string)$data['original']]))$entries[(string)$data['original']]=['id'=>(int)$data['original'],'label'=>'Original / মূল ছবি'];
            $productId=(int)substr($setting->type,strlen('product_image_versions_v1_'));$product=Product::find($productId);
            foreach($entries as $entry){
                $id=(int)($entry['id']??0);if(!$id||isset($seen[$id]))continue;$seen[$id]=true;$u=Upload::find($id);if(!$u)continue;
                try{$path=self::path($u);}catch(\Throwable $e){continue;}
                $stamp=self::stamp($entry,$u,$path);if($stamp>=$cutoff)continue;
                $active=Product::where('thumbnail_img',(string)$id)->orWhereRaw('FIND_IN_SET(?, photos)>0',[(string)$id])->exists();
                $rows[]=['id'=>$id,'name'=>$u->file_original_name,'product'=>$product->name??('Product #'.$productId),'label'=>$entry['label']??'Saved version','original'=>(int)$data['original']===$id,'url'=>my_asset($u->file_name),'bytes'=>filesize($path),'sha256'=>hash_file('sha256',$path),'age_days'=>(int)floor((time()-$stamp)/86400),'selectable'=>!$active,'reason'=>$active?'Currently used in product thumbnail/gallery; retained.':'Other references are checked again on deletion.'];
            }
        }
        $next=$settings->last()->id??$after;return ['rows'=>$rows,'cursor'=>$next,'done'=>!self::records()->where('id','>',$next)->exists()];
    }
    public static function delete($id,$expectedHash,$days){
        $moved=null;$source=null;$bytes=0;$historyChanges=[];
        try{
            $reference=DB::transaction(function()use($id,$expectedHash,$days,&$moved,&$source,&$bytes,&$historyChanges){
                $u=Upload::where('id',$id)->lockForUpdate()->firstOrFail();$source=self::path($u);$bytes=filesize($source);
                if(self::codeReferenced($u->file_name))throw new \RuntimeException('Image is referenced in website code; retained.');
                if(!hash_equals($expectedHash,hash_file('sha256',$source)))throw new \RuntimeException('Image changed. Reload the list.');
                if(Upload::withTrashed()->where('file_name',$u->file_name)->where('id','!=',$id)->exists())throw new \RuntimeException('File is shared with another media record; retained.');
                $found=false;$rows=self::records()->orderBy('id')->lockForUpdate()->get();if(count($rows)>10000)throw new \RuntimeException('Too many history records for safe cleanup.');
                foreach($rows as $row){
                    $data=self::metadata($row);$entry=$data['entries'][(string)$id]??null;$original=(int)$data['original']===(int)$id;if(!$entry&&!$original)continue;
                    if(self::stamp($entry?:[],$u,$source)>=time()-$days*86400)throw new \RuntimeException('A product saved this version recently. Choose an older image.');
                    $found=true;unset($data['entries'][(string)$id]);if($original)$data['original']=0;$after=json_encode($data);$historyChanges[$row->id]=['before'=>$row->value,'after'=>$after];$row->value=$after;$row->save();
                }
                if(!$found)throw new \RuntimeException('This upload is not saved product image history.');
                // Only the selected history references are removed. All other table/setting/chat references still block deletion.
                if(UnusedImageCleaner::referenced($u))throw new \RuntimeException('Image is used in another product, page, message or setting; retained.');
                $directory=storage_path('app/image-quarantine');if(!is_dir($directory)&&!@mkdir($directory,0700,true))throw new \RuntimeException('Cannot create private cleanup staging.');if(is_link($directory))throw new \RuntimeException('Unsafe staging folder.');
                $reference=$id.'-'.bin2hex(random_bytes(8));$moved=$directory.'/'.$reference;
                $meta=['id'=>$id,'file'=>$u->file_name,'sha256'=>$expectedHash,'at'=>gmdate('c')];
                if(file_put_contents($moved.'.json',json_encode($meta),LOCK_EX)===false)throw new \RuntimeException('Cannot save cleanup recovery record.');@chmod($moved.'.json',0600);
                if(!@rename($source,$moved.'.image'))throw new \RuntimeException('Image could not be staged. No permanent deletion completed.');@chmod($moved.'.image',0600);return $reference;
            });
        }catch(\Throwable $e){
            if($moved&&is_file($moved.'.image')){if(!file_exists($source)&&@rename($moved.'.image',$source)){@chmod($source,0644);@unlink($moved.'.json');}else throw new \RuntimeException('Cleanup interrupted. Original image retained in private quarantine; hosting review required.');}
            elseif($moved&&is_file($moved.'.json'))@unlink($moved.'.json');
            throw $e;
        }
        // Existing permanent cleaner rechecks references before deleting the file and media row.
        try{UnusedImageCleaner::permanent($reference);}catch(\Throwable $e){
            if(is_file($moved.'.image')&&Upload::find($id)){
                $restored=false;
                try{
                    UnusedImageCleaner::restore($reference);
                    $restored=true;
                    DB::transaction(function()use($historyChanges){foreach($historyChanges as $id=>$values){$row=BusinessSetting::where('id',$id)->lockForUpdate()->first();if($row&&$row->value===$values['after']){$row->value=$values['before'];$row->save();}}});
                }catch(\Throwable $ignored){}
                if($restored)throw new \RuntimeException('Permanent cleanup was blocked. The original image has been restored; reload the list.');
            }
            throw new \RuntimeException('Permanent cleanup could not finish. Refresh Media Center / Unused Image Cleaner trash. Quarantine reference: '.$reference);
        }
        return ['deleted'=>1,'freed_bytes'=>$bytes,'id'=>(int)$id];
    }
}
