<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderSmsOutbox extends Migration
{
    public function up()
    {
        Schema::create('order_sms_outbox', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->string('event', 20);
            $table->string('state', 20)->default('pending');
            $table->string('mode', 10);
            $table->text('snapshot');
            $table->text('payload')->nullable();
            $table->string('recipient_hint', 20)->nullable();
            $table->string('provider_id', 80)->nullable();
            $table->string('notice', 80)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
            $table->unique(['order_id', 'event']);
            $table->index(['state', 'id']);
        });
    }
    public function down() { Schema::dropIfExists('order_sms_outbox'); }
}
