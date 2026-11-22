<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourierParcelEvents extends Migration
{
    public function up(){if(!Schema::hasTable('courier_parcel_events'))Schema::create('courier_parcel_events',function(Blueprint $t){
        $t->bigIncrements('id');$t->unsignedBigInteger('order_id');$t->string('kind',16);$t->string('account_id',40);
        $t->dateTime('event_at')->nullable();$t->dateTime('observed_at');$t->string('source',32);
        $t->unique(['order_id','kind']);$t->index(['event_at','kind']);
    });}
    public function down(){Schema::dropIfExists('courier_parcel_events');}
}
