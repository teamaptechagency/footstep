<?php
namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;

/** Request-local account context. Shipment account IDs are immutable after real booking. */
class SteadfastAccounts
{
    private static $account='legacy';
    private static $menu=null;
    public static function current(){return self::$account;}
    public static function valid($id){return $id==='legacy'||(is_string($id)&&preg_match('/^[a-f0-9]{32}$/D',$id));}
    public static function using($id,callable $fn){
        if(!self::valid($id))throw new \RuntimeException('Invalid courier account.');self::credentials($id);$prior=self::$account;self::$account=$id;
        try{return $fn();}finally{self::$account=$prior;}
    }
    public static function storageKey($key){
        $keys=['steadfast_connection_test','steadfast_sync_health','steadfast_worker_health','steadfast_reconcile','steadfast_sync_cursor'];
        return self::$account!=='legacy'&&in_array($key,$keys,true)?$key.'_'.self::$account:$key;
    }
    public static function credentials($id){
        if(!self::valid($id))throw new \RuntimeException('Invalid courier account.');
        if($id==='legacy'){$value=StoreOperations::get('steadfast',[]);$label=StoreOperations::get('steadfast_legacy_label',['label'=>'Main account']);$value['label']=$label['label']??'Main account';}
        else{$value=StoreOperations::get('steadfast_account_'.$id,[]);if(!$value)throw new \RuntimeException('Courier account not found.');}
        return array_replace(['id'=>$id,'label'=>'Steadfast','mode'=>'mock','active'=>true,'api_key'=>'','secret_key'=>'','webhook_token'=>'','old_invoice_pattern'=>'code'],$value,['id'=>$id]);
    }
    public static function listing(){
        if(self::$menu!==null)return self::$menu;
        $ids=StoreOperations::get('steadfast_accounts_index',['ids'=>[]]);$out=[];
        foreach(array_merge(['legacy'],array_slice($ids['ids']??[],0,9))as $id){$s=self::credentials($id);$out[]=['id'=>$id,'label'=>$s['label'],'mode'=>$s['mode'],'active'=>(bool)$s['active'],'api_key_stored'=>!empty($s['api_key']),'secret_key_stored'=>!empty($s['secret_key'])];}return self::$menu=$out;
    }
    public static function save($id,array $data){
        return DB::transaction(function()use($id,$data){
            // Serialize additions and renames through the index row; existing secrets never return to HTML.
            BusinessSetting::where('type','ops_steadfast')->lockForUpdate()->first();
            $index=StoreOperations::get('steadfast_accounts_index',['ids'=>[]]);$new=$id===null;
            if($new){if(count($index['ids'])>=9)throw new \RuntimeException('Maximum 10 courier accounts.');$id=bin2hex(random_bytes(16));$s=['mode'=>'mock','api_key'=>'','secret_key'=>'','webhook_token'=>''];}else $s=self::credentials($id);
            self::$menu=null;$label=trim($data['label']);if($label===''||preg_match('/[\x00-\x1F\x7F]/u',$label))throw new \RuntimeException('Use a visible account label.');foreach(self::listing()as $other)if($other['id']!==$id&&mb_strtolower($other['label'])===mb_strtolower($label))throw new \RuntimeException('Use a different account label.');
            $s['label']=$label;$s['mode']=$data['mode'];$s['active']=(bool)$data['active'];$s['old_invoice_pattern']=$data['old_invoice_pattern'];
            foreach(['api_key','secret_key','webhook_token']as $field)if(isset($data[$field])&&trim($data[$field])!=='')$s[$field]=trim($data[$field]);
            if($s['api_key']!=='')foreach(self::listing()as $other)if($other['id']!==$id&&hash_equals((string)self::credentials($other['id'])['api_key'],$s['api_key']))throw new \RuntimeException('This API Key is already saved under another label.');
            if($s['mode']==='live'&&(!$s['api_key']||!$s['secret_key']))throw new \RuntimeException('Live account requires API Key and Secret Key.');
            if(!$new&&$s['api_key']!==self::credentials($id)['api_key']){
                // Renaming is safe; switching the underlying courier identity would break old parcels.
                foreach(BusinessSetting::whereRaw("type LIKE 'ops!_shipment!_%' ESCAPE '!'")->cursor()as $row){$orderId=(int)substr($row->type,13);$parcel=StoreOperations::shipment($orderId);if(($parcel['account_id']??'legacy')===$id&&!($parcel['mock']??false)&&!empty($parcel['state']))throw new \RuntimeException('This account already owns parcels. Add a new account instead of changing its API Key. Secret rotation is allowed.');}
            }
            if($id==='legacy'){StoreOperations::put('steadfast',$s);StoreOperations::put('steadfast_legacy_label',['label'=>$label]);}else{StoreOperations::put('steadfast_account_'.$id,$s);if($new){$index['ids'][]=$id;StoreOperations::put('steadfast_accounts_index',$index);}}
            self::$menu=null;return $id;
        });
    }
}
