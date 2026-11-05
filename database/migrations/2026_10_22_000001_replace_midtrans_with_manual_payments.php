<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('payment_status', 32)->default('pending');
            $table->string('payment_proof')->nullable();
            $table->string('destination_bank', 40)->nullable();
            $table->string('bank_name', 80)->nullable();
            $table->string('account_name', 100)->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->index(['payment_status', 'payment_submitted_at']);
        });

        DB::table('reservations')
            ->where('status', 'confirmed')
            ->update(['payment_status' => 'paid']);

        DB::table('reservations')
            ->whereIn('status', ['cancelled', 'expired', 'refunded'])
            ->update(['payment_status' => 'cancelled']);

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropUnique(['gateway_order_id']);
            $table->dropColumn(['gateway_order_id', 'payment_url', 'payment_details']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('gateway_order_id')->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->json('payment_details')->nullable();
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['payment_status', 'payment_submitted_at']);
            $table->dropColumn([
                'payment_status',
                'payment_proof',
                'destination_bank',
                'bank_name',
                'account_name',
                'payment_submitted_at',
            ]);
        });
    }
};
