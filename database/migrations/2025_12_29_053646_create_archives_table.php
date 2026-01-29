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
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('disk')->default('public');
            $table->string('type')->nullable();
            $table->string('category')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->string('original_name');
            $table->string('extension', 10);
            $table->string('mime_type');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->softDeletes();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
