<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateProductAddons extends Migration
{
 public function up(){
  Schema::create('product_addons',function(Blueprint $t){$t->bigIncrements('id');$t->string('name');$t->text('description')->nullable();$t->decimal('regular_price',12,2);$t->decimal('bundle_price',12,2);$t->string('discount_type',10)->default('none');$t->decimal('discount_value',12,2)->default(0);$t->text('product_ids')->nullable();$t->boolean('active')->default(true);$t->timestamps();});
  Schema::create('order_addons',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('order_id')->index();$t->unsignedBigInteger('order_detail_id')->index();$t->unsignedBigInteger('addon_id');$t->string('name');$t->decimal('regular_price',12,2);$t->decimal('unit_price',12,2);$t->unsignedInteger('quantity');$t->decimal('total',12,2);$t->timestamps();});
  Schema::table('carts',function(Blueprint $t){$t->text('addon_ids')->nullable();});
  DB::table('product_addons')->insert(['name'=>'মোজা / Socks','description'=>'জুতার সঙ্গে এক জোড়া মোজা যোগ করুন।','regular_price'=>200,'bundle_price'=>200,'discount_type'=>'none','discount_value'=>0,'product_ids'=>'[]','active'=>1,'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down(){Schema::table('carts',function(Blueprint $t){$t->dropColumn('addon_ids');});Schema::dropIfExists('order_addons');Schema::dropIfExists('product_addons');}
}
