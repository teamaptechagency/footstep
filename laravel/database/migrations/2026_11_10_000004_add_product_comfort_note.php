<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class AddProductComfortNote extends Migration
{
 public function up(){if(!Schema::hasColumn('products','comfort_note'))Schema::table('products',function(Blueprint $table){$table->string('comfort_note',160)->nullable()->default('Super lightweight and Super comfortable');});}
 public function down(){if(Schema::hasColumn('products','comfort_note'))Schema::table('products',function(Blueprint $table){$table->dropColumn('comfort_note');});}
}
