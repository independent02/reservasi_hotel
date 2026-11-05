<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table): void {
            if (! Schema::hasColumn('hotels', 'address')) {
                $table->text('address')->nullable();
            }
            if (! Schema::hasColumn('hotels', 'maps_url')) {
                $table->text('maps_url')->nullable();
            }
            if (! Schema::hasColumn('hotels', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (! Schema::hasColumn('hotels', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
        });

        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('reservations', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable();
            }
            if (! Schema::hasColumn('reservations', 'refund_percent')) {
                $table->unsignedTinyInteger('refund_percent')->nullable();
            }
            if (! Schema::hasColumn('reservations', 'refund_amount')) {
                $table->decimal('refund_amount', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
    }
};
