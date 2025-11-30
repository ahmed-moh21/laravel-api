<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoldsTable extends Migration
{
    public function up()
    {
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('qty');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->enum('status', ['active','used','expired','cancelled'])->default('active');
            $table->timestamps();
            $table->index(['product_id','expires_at','status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holds');
    }
}
