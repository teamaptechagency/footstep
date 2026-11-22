<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFootstepNativeMobileTables extends Migration
{
    public function up()
    {
        Schema::create('footstep_mobile_tokens', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('user_id')->index();
            $t->string('token_hash',64)->unique(); $t->timestamp('expires_at')->index();
            $t->timestamp('created_at');
        });
        Schema::create('footstep_mobile_checkouts', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedBigInteger('user_id');
            $t->string('request_key',64); $t->string('payload_hash',64);
            $t->unsignedBigInteger('combined_order_id')->nullable(); $t->timestamp('created_at');
            $t->unique(['user_id','request_key']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('footstep_mobile_checkouts');
        Schema::dropIfExists('footstep_mobile_tokens');
    }
}
