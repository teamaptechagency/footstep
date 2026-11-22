<?php
namespace App\Services;

/** Pure event normalization shared by browser, server and tests. */
class MetaEventData
{
    public const EVENTS=['PageView','ViewContent','Search','AddToCart','AddToWishlist','InitiateCheckout','AddPaymentInfo','Purchase','Lead','CompleteRegistration','Contact','Subscribe'];

    public static function matching(array $input)
    {
        $out=[];
        $email=mb_strtolower(trim((string)($input['email']??'')));
        if(filter_var($email,FILTER_VALIDATE_EMAIL))$out['em']=[hash('sha256',$email)];
        $phone=preg_replace('/\D/','',(string)($input['phone']??''));
        if(preg_match('/^01\d{9}$/D',$phone))$phone='88'.$phone;
        if(strpos($phone,'00')===0)$phone=substr($phone,2);
        if(preg_match('/^[1-9]\d{7,14}$/D',$phone))$out['ph']=[hash('sha256',$phone)];
        if(!empty($input['external_id']))$out['external_id']=[hash('sha256',(string)$input['external_id'])];
        $names=preg_split('/\s+/u',trim((string)($input['name']??'')),2);
        foreach(['fn'=>$names[0]??'','ln'=>$names[1]??'','ct'=>$input['city']??'','st'=>$input['state']??''] as $field=>$raw){$value=mb_strtolower(preg_replace('/[^\p{L}]/u','',(string)$raw));if($value!==''&&!in_array($value,['null','undefined'],true))$out[$field]=[hash('sha256',$value)];}
        $country=mb_strtolower(trim((string)($input['country']??'')));if($country==='bangladesh')$country='bd';if(preg_match('/^[a-z]{2}$/D',$country))$out['country']=[hash('sha256',$country)];
        $zip=preg_replace('/\s+/','',mb_strtolower((string)($input['postal_code']??'')));if($zip!==''&&!in_array($zip,['null','undefined'],true))$out['zp']=[hash('sha256',$zip)];
        return $out;
    }

    public static function currency($input,$fallback='BDT')
    {
        $normalize=function($value){return is_string($value)?strtoupper(trim($value)):'';};
        $code=$normalize($input);if(preg_match('/^[A-Z]{3}$/D',$code))return $code;
        $code=$normalize($fallback);return preg_match('/^[A-Z]{3}$/D',$code)?$code:'BDT';
    }
    public static function money(array $data,$fallback='BDT',$purchase=false)
    {
        if(!$purchase&&!array_key_exists('value',$data)&&!array_key_exists('currency',$data))return $data;
        if($purchase&&!array_key_exists('value',$data))throw new \InvalidArgumentException('Purchase value is required.');
        if(array_key_exists('value',$data)){$v=$data['value'];if(is_bool($v)||(!is_int($v)&&!is_float($v)&&!is_string($v))||!is_numeric($v)||!is_finite((float)$v)||(float)$v<0)throw new \InvalidArgumentException('Invalid monetary event value.');$data['value']=round((float)$v,2);}
        $data['currency']=self::currency($data['currency']??null,$fallback);return $data;
    }

    public static function commerce(array $rows,$currency)
    {
        $contents=[];$value=0;$quantity=0;
        foreach($rows as $row){
            $qty=(int)($row['quantity']??0);$price=(float)($row['price']??0);$id=(string)($row['id']??'');
            if($qty<1||$id===''||$price<0||!is_finite($price))continue;
            if(count($contents)<50)$contents[]=['id'=>$id,'quantity'=>$qty,'item_price'=>round($price,2)];$quantity+=$qty;$value+=$price*$qty;
        }
        return ['content_type'=>'product','content_ids'=>array_column($contents,'id'),'contents'=>$contents,'num_items'=>$quantity,'value'=>round($value,2),'currency'=>self::currency($currency)];
    }

    public static function safeUrl($url)
    {
        $p=parse_url($url);
        if(!$p||!isset($p['scheme'],$p['host'])||!in_array($p['scheme'],['http','https'],true))return '';
        // Never forward query strings (emails, reset tokens, search terms) or fragments.
        return $p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'').($p['path']??'/');
    }

    public static function cookie($value)
    {
        return is_string($value)&&preg_match('/^fb\.\d{1,2}\.\d{13}\.[A-Za-z0-9_-]{1,200}$/D',$value)?$value:null;
    }

    public static function searchTerm($term)
    {
        $term=mb_substr(trim(strip_tags((string)$term)),0,100);
        return preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\d{7,}/i',$term)?'[redacted]':$term;
    }
}
