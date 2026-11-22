<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddProductMadeIn extends Migration
{
    public function up(){if(!Schema::hasColumn('products','made_in'))Schema::table('products',function(Blueprint $table){$table->string('made_in',20)->default('Vietnam');});}
    public function down(){if(Schema::hasColumn('products','made_in'))Schema::table('products',function(Blueprint $table){$table->dropColumn('made_in');});}
}
