<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->uuid('checkout_key')->nullable()->unique();
            $table->string('cancellation_reason')->nullable();
            $table->foreignId('canceled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable();
        });
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->foreignId('compensates_id')->nullable()->unique()->constrained('stock_movements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['compensates_id']);
            $table->dropUnique(['compensates_id']);
            $table->dropColumn('compensates_id');
        });
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('canceled_by');
            $table->dropUnique(['checkout_key']);
            $table->dropColumn(['checkout_key', 'cancellation_reason', 'canceled_at']);
        });
    }
};
