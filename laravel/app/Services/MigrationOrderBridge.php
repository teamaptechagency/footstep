<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** One-way order mirror. Query-builder writes deliberately bypass notification/stock observers. */
class MigrationOrderBridge
{
    private const ORDER = ['shipping_address','delivery_status','payment_type','payment_status','grand_total','coupon_discount','code','tracking_code','date','shipping_type','created_at','updated_at'];
    private const DETAIL = ['variation','price','tax','shipping_cost','quantity','payment_status','delivery_status','shipping_type','created_at','updated_at'];
    private const ADDON = ['addon_id','name','regular_price','unit_price','quantity','total','created_at','updated_at'];

    public function install()
    {
        if (!Schema::hasTable('migration_order_bridge')) Schema::create('migration_order_bridge',function($t){
            $t->bigIncrements('id'); $t->string('source',64); $t->unsignedBigInteger('source_id');
            $t->unsignedBigInteger('order_id')->nullable(); $t->string('source_hash',64)->nullable();
            $t->string('local_hash',64)->nullable(); $t->string('status',32); $t->string('reason',64)->nullable();
            $t->timestamp('synced_at')->nullable(); $t->unique(['source','source_id']);
        });
    }
    private function only(array $row,array $fields) { return array_intersect_key($row,array_flip($fields)); }
    private function digest($value) { return hash('sha256',json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
    private function numericRows($rows) {
        foreach($rows as &$row) { ksort($row); foreach($row as &$v) if(is_numeric($v)) $v=(string)(0+$v); }
        return $rows;
    }
    public function fingerprint($id)
    {
        $o=(array)DB::table('orders')->where('id',$id)->first();
        if(!$o) return null;
        $details=[];$addons=[];
        foreach(DB::table('order_details')->where('order_id',$id)->orderBy('id')->get() as $r) $details[]=$this->only((array)$r,array_merge(['product_id'],self::DETAIL));
        if(Schema::hasTable('order_addons')) foreach(DB::table('order_addons')->where('order_id',$id)->orderBy('id')->get() as $r) $addons[]=$this->only((array)$r,self::ADDON);
        // Read/unread flags and operational metadata are deliberately not synchronized.
        return $this->digest($this->numericRows([$this->only($o,self::ORDER)])[0]).':'.$this->digest($this->numericRows($details)).':'.$this->digest($this->numericRows($addons));
    }
    private function hashLocal($id) { return hash('sha256',(string)$this->fingerprint($id)); }
    private function sourceFingerprint(array $item)
    {
        return $this->digest($this->numericRows([$this->only($item['order'],self::ORDER)])[0]).':'.$this->digest($this->numericRows(array_map(function($d){return $this->only($d,array_merge(['product_id'],self::DETAIL));},$item['details']))).':'.$this->digest($this->numericRows(array_map(function($a){return $this->only($a,self::ADDON);},$item['addons']??[])));
    }
    private function validate(array $item, $identicalExisting=false)
    {
        $o=$item['order']??[];$details=$item['details']??[];
        if(empty($o['id']) || !is_numeric($o['id']) || empty($o['code']) || strlen($o['code'])>191 || !$details || count($details)>100) throw new \RuntimeException('invalid_order');
        if(!is_array(json_decode($o['shipping_address']??'',true)) || !is_numeric($o['grand_total']??null) || $o['grand_total']<0) throw new \RuntimeException('invalid_order');
        $seen=[];
        foreach($details as $d){
            if(empty($d['id']) || isset($seen[$d['id']]) || !is_numeric($d['quantity']??null) || $d['quantity']<1 || !is_numeric($d['price']??null) || $d['price']<0) throw new \RuntimeException('invalid_detail');
            $seen[$d['id']]=true;
            $p=DB::table('products')->where('id',$d['product_id']??0)->first(['slug']);
            // A deleted historical product may occur in an identical, already restored invoice.
            // Enrolling that invoice changes no order data; a NEW/different invoice still fails.
            if(!$p && !$d['product_slug'] && $identicalExisting) continue;
            if(!$p || !$d['product_slug'] || $p->slug!==$d['product_slug']) throw new \RuntimeException('product_mapping_mismatch');
        }
        foreach($item['addons']??[] as $a) if(!isset($seen[$a['order_detail_id']??0]) || !is_numeric($a['total']??null) || $a['total']<0) throw new \RuntimeException('invalid_addon');
        if(!empty($item['addons'])&&!Schema::hasTable('order_addons')) throw new \RuntimeException('addon_schema_missing');
    }
    public function import($source,array $item)
    {
        return DB::transaction(function()use($source,$item){
            $sameCode=DB::table('orders')->where('code',$item['order']['code']??'')->get(['id']);
            $identicalExisting=$sameCode->count()===1 && $this->fingerprint($sameCode->first()->id)===$this->sourceFingerprint($item);
            $this->validate($item,$identicalExisting);
            $o=$item['order'];$sid=(int)$o['id'];$sh=$this->digest($item);
            $map=DB::table('migration_order_bridge')->where('source',$source)->where('source_id',$sid)->lockForUpdate()->first();
            if($map && $map->order_id){
                $id=(int)$map->order_id;
                if($this->hashLocal($id)!==$map->local_hash) return $this->conflict($source,$sid,'changed_on_vps',$map);
                if($map->source_hash===$sh) return 'unchanged';
            }else{
                $existing=DB::table('orders')->where('code',$o['code'])->get();
                if($existing->count()>1) return $this->conflict($source,$sid,'duplicate_order_code',$map);
                $id=$existing->count()?(int)$existing->first()->id:null;
                if($id){
                    // Baseline enrollment must be identical; never overwrite a divergent restored/local order.
                    $expected=$this->sourceFingerprint($item);
                    if($this->fingerprint($id)!==$expected) return $this->conflict($source,$sid,'baseline_differs',$map);
                    $this->saveMap($source,$sid,$id,$sh);return 'enrolled';
                }
            }
            $values=$this->only($o,self::ORDER);
            if(!$id){
                $values['seller_id']=SingleVendor::ownerId();$values['user_id']=null;
                // Keep imported customer data on the invoice, never import source logins/passwords.
                $combined=$item['combined']??null;
                $values['combined_order_id']=$combined ? DB::table('combined_orders')->insertGetId(['user_id'=>0,'shipping_address'=>$o['shipping_address'],'grand_total'=>$o['grand_total'],'created_at'=>$o['created_at'],'updated_at'=>$o['updated_at']]) : null;
                $id=DB::table('orders')->insertGetId($values);$result='created';
            }else{
                DB::table('orders')->where('id',$id)->update($values);$result='updated';
                // Preserve local detail IDs when possible (courier/invoice references).
            }
            $local=DB::table('order_details')->where('order_id',$id)->orderBy('id')->get();
            if($local->count() && $local->count()!==count($item['details'])) throw new \RuntimeException('line_count_changed_requires_review');
            $detailIds=[];
            foreach($item['details'] as $index=>$d){
                $v=$this->only($d,self::DETAIL);$v['product_id']=(int)$d['product_id'];$v['order_id']=$id;$v['seller_id']=SingleVendor::ownerId();
                if(isset($local[$index])){$did=$local[$index]->id;DB::table('order_details')->where('id',$did)->update($v);}else $did=DB::table('order_details')->insertGetId($v);
                $detailIds[$d['id']]=$did;
            }
            if(Schema::hasTable('order_addons')){
                DB::table('order_addons')->where('order_id',$id)->delete();
                foreach($item['addons']??[] as $a){$v=$this->only($a,self::ADDON);$v['order_id']=$id;$v['order_detail_id']=$detailIds[$a['order_detail_id']];DB::table('order_addons')->insert($v);}
            }
            $combinedId=DB::table('orders')->where('id',$id)->value('combined_order_id');
            if($combinedId) DB::table('combined_orders')->where('id',$combinedId)->update(['grand_total'=>DB::table('orders')->where('combined_order_id',$combinedId)->sum('grand_total'),'shipping_address'=>$o['shipping_address'],'updated_at'=>$o['updated_at']]);
            $this->saveMap($source,$sid,$id,$sh);return $result;
        });
    }
    private function saveMap($source,$sid,$id,$hash){DB::table('migration_order_bridge')->updateOrInsert(['source'=>$source,'source_id'=>$sid],['order_id'=>$id,'source_hash'=>$hash,'local_hash'=>$this->hashLocal($id),'status'=>'synced','reason'=>null,'synced_at'=>now()]);}
    private function conflict($source,$sid,$reason,$map){DB::table('migration_order_bridge')->updateOrInsert(['source'=>$source,'source_id'=>$sid],['status'=>'conflict','reason'=>$reason,'synced_at'=>now()]);return 'conflict';}
}
