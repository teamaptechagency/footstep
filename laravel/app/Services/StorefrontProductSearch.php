<?php
namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class StorefrontProductSearch
{
    public static function normalize($value)
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$value)),0,100);
    }

    public static function words($value)
    {
        $value=mb_strtolower(self::normalize($value));
        $value=strtr($value,['নিউ ব্যালেন্স'=>'new balance','অ্যাডিডাস'=>'adidas','এডিডাস'=>'adidas','নাইকি'=>'nike','নাইক'=>'nike','হোকা'=>'hoka','স্কেচারস'=>'skechers','রানিং'=>'running','স্নিকার্স'=>'sneakers']);
        $words=preg_split('/[^\p{L}\p{N}]+/u',$value,-1,PREG_SPLIT_NO_EMPTY);
        if(count($words)>1)$words=array_values(array_diff($words,['shoe','shoes','জুতা','জুতো','and','the','for']));
        return array_slice(array_values(array_unique($words)),0,8);
    }

    public static function apply($products,$query)
    {
        $words=self::words($query);
        if(!$words&&self::normalize($query)!=='')return $products->whereRaw('1=0');
        foreach($words as $word){
            $like='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$word).'%';
            $products->where(function($match)use($like,$word){
                $match->where('products.name','like',$like)->orWhere('tags','like',$like)->orWhere('slug','like',$like)
                    ->orWhereHas('product_translations',function($q)use($like){$q->where('name','like',$like);})
                    ->orWhereHas('brand',function($q)use($like){$q->where('name','like',$like);})
                    ->orWhereHas('category',function($q)use($like){$q->where('name','like',$like);})
                    ->orWhereHas('stocks',function($q)use($like){$q->where('sku','like',$like);});
                if(ctype_digit($word))$match->orWhere('products.id',(int)$word);
            });
        }
        return $products;
    }

    private static function vocabulary()
    {
        return Cache::remember('storefront_search_vocabulary_v2',300,function(){
            $counts=[];
            // Public catalogue only. Bound the fallback work, never load images or descriptions.
            $products=filter_products(Product::where('published',1))->select('products.id','name','tags','slug','brand_id','category_id')
                ->with(['brand:id,name','category:id,name'])->orderByDesc('products.id')->limit(3000)->get();
            foreach($products as $product){
                $text=mb_strtolower($product->name.' '.$product->tags.' '.$product->slug.' '.optional($product->brand)->name.' '.optional($product->category)->name);
                foreach(array_unique(preg_split('/[^a-z0-9]+/',$text,-1,PREG_SPLIT_NO_EMPTY)) as $word){
                    if(preg_match('/^[a-z][a-z0-9]{2,31}$/D',$word))$counts[$word]=($counts[$word]??0)+1;
                }
            }
            arsort($counts);return array_slice($counts,0,8000,true);
        });
    }

    private static function correctWord($word,array $vocabulary)
    {
        if(!preg_match('/^[a-z][a-z0-9]{3,31}$/D',$word))return $word;
        $length=strlen($word);$best=$word;$bestScore=PHP_INT_MAX;
        foreach($vocabulary as $candidate=>$frequency){
            if(strpos($candidate,$word)===0)return $word; // Preserve valid partial names.
            if($candidate[0]!==$word[0])continue;
            $prefix=substr($candidate,0,$length);
            $distance=min(levenshtein($word,$candidate),levenshtein($word,$prefix));
            for($i=0;$i<$length-1;$i++){
                $swap=$word;$swap[$i]=$word[$i+1];$swap[$i+1]=$word[$i];
                if($swap===$candidate||$swap===$prefix)$distance=min($distance,1);
            }
            if($distance>($length<6?1:2))continue;
            $score=$distance*1000+abs(strlen($candidate)-$length)*10-min(9,$frequency);
            if($score<$bestScore){$best=$candidate;$bestScore=$score;}
        }
        return $best;
    }

    public static function resolve($base,$raw)
    {
        $original=self::normalize($raw);$strict=self::apply(clone $base,$original);
        $info=['original'=>$original,'matched_query'=>$original,'corrected'=>false,'suggestions'=>[]];
        if($original===''||(clone $strict)->exists())return ['query'=>$strict,'info'=>$info];
        $words=self::words($original);$vocabulary=self::vocabulary();
        $corrected=array_map(function($word)use($vocabulary){return self::correctWord($word,$vocabulary);},$words);
        if($corrected!==$words){
            $phrase=implode(' ',$corrected);$info['suggestions']=[$phrase];$fallback=self::apply(clone $base,$phrase);
            if((clone $fallback)->exists()){
                $info['matched_query']=$phrase;$info['corrected']=true;
                return ['query'=>$fallback,'info'=>$info];
            }
        }
        return ['query'=>$strict,'info'=>$info];
    }
}
