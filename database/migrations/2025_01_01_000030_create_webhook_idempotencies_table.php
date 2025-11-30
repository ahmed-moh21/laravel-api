<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhookIdempotenciesTable extends Migration
{
    public function up()
    {
        Schema::create('webhook_idempotencies', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id','processed']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_idempotencies');
    }
}
