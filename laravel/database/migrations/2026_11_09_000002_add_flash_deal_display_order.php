<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddFlashDealDisplayOrder extends Migration {
 public function up(){if(!Schema::hasColumn('flash_deals','sort_order'))Schema::table('flash_deals',function(Blueprint $t){$t->unsignedInteger('sort_order')->nullable();});}
 public function down(){if(Schema::hasColumn('flash_deals','sort_order'))Schema::table('flash_deals',function(Blueprint $t){$t->dropColumn('sort_order');});}
}
