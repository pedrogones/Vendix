<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('buyer_name')->nullable()->after('client_id');
            $table->string('buyer_phone', 32)->nullable()->after('buyer_name');
            $table->string('fulfillment_type', 20)->nullable()->after('payment_status');
            $table->string('delivery_address')->nullable()->after('fulfillment_type');
            $table->string('fulfillment_status', 20)->nullable()->after('delivery_address');
            $table->boolean('needs_human_help')->default(false)->after('fulfillment_status');
            $table->string('human_help_note')->nullable()->after('needs_human_help');
            $table->string('public_token', 32)->nullable()->unique()->after('human_help_note');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn([
                'buyer_name',
                'buyer_phone',
                'fulfillment_type',
                'delivery_address',
                'fulfillment_status',
                'needs_human_help',
                'human_help_note',
                'public_token',
            ]);
        });
    }
};
