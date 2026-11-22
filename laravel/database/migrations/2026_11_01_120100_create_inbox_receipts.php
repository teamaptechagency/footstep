<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateInboxReceipts extends Migration {
 public function up(){Schema::create('inbox_receipts',function(Blueprint $t){$t->engine='InnoDB';$t->bigIncrements('id');$t->unsignedBigInteger('channel_id')->index();$t->string('external_id',191);$t->string('status',20);$t->integer('error')->default(0);$t->string('dedupe',64)->unique();$t->timestamps();});}
 public function down(){Schema::dropIfExists('inbox_receipts');}
}
