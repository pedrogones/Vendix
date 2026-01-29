<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
//            $table->unsignedBigInteger('reference_type');
            $table->unsignedBigInteger('quantity');
            $table->enum('reason', ['sale', 'purchase', 'loss', 'manual', 'return']);
            $table->string('reference')->nullable();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->string('reference_type');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
