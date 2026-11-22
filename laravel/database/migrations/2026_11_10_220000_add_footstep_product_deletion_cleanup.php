<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
class AddFootstepProductDeletionCleanup extends Migration {
 public function up(){
  if(!Schema::hasColumn('order_details','product_name_snapshot'))Schema::table('order_details',function(Blueprint $t){$t->text('product_name_snapshot')->nullable();});
  if(!Schema::hasTable('footstep_product_media_cleanup'))Schema::create('footstep_product_media_cleanup',function(Blueprint $t){$t->bigIncrements('id');$t->string('cleanup_key',64)->unique();$t->unsignedBigInteger('upload_id')->nullable();$t->text('file_path');$t->longText('variant_paths')->nullable();$t->unsignedInteger('attempts')->default(0);$t->text('last_error')->nullable();$t->timestamp('available_at')->nullable()->index();$t->timestamp('locked_until')->nullable();$t->timestamps();});
  if(!DB::table('business_settings')->where('type','mobile_catalog_revision')->exists())DB::table('business_settings')->insert(['type'=>'mobile_catalog_revision','value'=>'0','created_at'=>now(),'updated_at'=>now()]);
 }
 public function down(){Schema::dropIfExists('footstep_product_media_cleanup');if(Schema::hasColumn('order_details','product_name_snapshot'))Schema::table('order_details',function(Blueprint $t){$t->dropColumn('product_name_snapshot');});}
}
