<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description');
            $table->string('image')->nullable();
            $table->decimal('price_per_night', 12, 2);
            $table->unsignedTinyInteger('max_guests')->default(2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['hotel_id', 'is_active', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
