<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
            $table->json('room_numbers')->nullable()->after('room_count');
            $table->string('gateway_order_id')->nullable()->unique()->after('status');
            $table->text('payment_url')->nullable()->after('gateway_order_id');
            $table->timestamp('expires_at')->nullable()->after('payment_url');
            $table->string('voucher_code', 32)->nullable()->unique()->after('expires_at');
            $table->timestamp('paid_at')->nullable()->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['gateway_order_id']);
            $table->dropUnique(['voucher_code']);
            $table->dropColumn([
                'user_id',
                'room_numbers',
                'gateway_order_id',
                'payment_url',
                'expires_at',
                'voucher_code',
                'paid_at',
            ]);
        });
    }
};
