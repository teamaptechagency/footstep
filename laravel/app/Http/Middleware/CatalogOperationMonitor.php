<?php
namespace App\Http\Middleware;

use Closure;
use App\Services\CatalogOperationLog;

class CatalogOperationMonitor
{
    public function handle($request,Closure $next)
    {
        $route=$request->route();$action=is_object($route)?$route->getActionName():'';
        if($request->isMethod('GET')||!preg_match('/(?:ProductController@(?:store|update|updatePublished|updateFeatured|updateTodaysDeal)|FlashDealController@(?:store|update|reorder|update_status|update_featured)|AizUploadController@(?:upload|store))$/D',$action))return $next($request);
        $id=bin2hex(random_bytes(8));$start=microtime(true);$error=null;$response=null;
        try{$response=$next($request);$response->headers->set('X-Catalog-Request-ID',$id);return $response;}
        catch(\Throwable $e){$error=$e;throw $e;}
        finally{
            try{
                $status=$response?$response->getStatusCode():($error instanceof \Illuminate\Validation\ValidationException?422:($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface?$error->getStatusCode():500));
                $explain=CatalogOperationLog::explain($error,$status);
                $fields=$error instanceof \Illuminate\Validation\ValidationException?array_values(array_filter(array_keys($error->errors()),function($v){return preg_match('/^(?:aiz_file|lang|name|title|category_id|unit_price|tags(?:\.\d+)?|date_range|photos|thumbnail_img|products(?:\.\d+)?|ordered_products|made_in|comfort_note|discount_\d+|discount_type_\d+)$/D',$v);})):[];
                $source=$error&&strpos($error->getFile(),app_path().DIRECTORY_SEPARATOR)===0?basename($error->getFile()).':'.$error->getLine():null;
                $entity=$route->parameter('id');
                CatalogOperationLog::append(['at'=>gmdate('c'),'request_id'=>$id,'operation'=>class_basename(explode('@',$action)[0]).'@'.(explode('@',$action)[1]??''),'entity_id'=>ctype_digit((string)$entity)?(int)$entity:null,'http'=>$status,'duration_ms'=>round((microtime(true)-$start)*1000,1),'code'=>$explain[0],'problem'=>$explain[1],'solution'=>$explain[2],'invalid_fields'=>$fields,'exception'=>$error?class_basename($error):null,'source'=>$source]);
            }catch(\Throwable $ignored){}
        }
    }
}
