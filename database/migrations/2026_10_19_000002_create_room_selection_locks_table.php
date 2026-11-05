<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_selection_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('room_number');
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['room_id', 'room_number', 'expires_at']);
            $table->index(['user_id', 'room_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_selection_locks');
    }
};
