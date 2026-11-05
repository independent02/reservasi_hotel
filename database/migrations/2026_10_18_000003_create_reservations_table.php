<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone', 32)->nullable();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guest_count');
            $table->unsignedTinyInteger('room_count')->default(1);
            $table->decimal('total_price', 12, 2);
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->index(['room_id', 'status', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
