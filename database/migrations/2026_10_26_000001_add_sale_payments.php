<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->string('payment_status', 20)->default('unpaid')->after('status');
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('method', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_tendered', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        $confirmed = DB::table('sales')->where('status', 'confirmed')->get(['id', 'total', 'user_id']);
        foreach ($confirmed as $sale) {
            DB::table('sales')->where('id', $sale->id)->update(['payment_status' => 'paid']);
            DB::table('sale_payments')->insert([
                'sale_id' => $sale->id,
                'type' => 'payment',
                'method' => 'other',
                'amount' => $sale->total,
                'amount_tendered' => null,
                'change_amount' => 0,
                'recorded_by' => $sale->user_id,
                'notes' => 'legado pré-recebimento',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('payment_status');
        });
    }
};
