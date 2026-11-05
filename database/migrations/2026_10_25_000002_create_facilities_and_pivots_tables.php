<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facilities')) {
            Schema::create('facilities', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->string('icon', 32);
                $table->string('scope', 16)->index();
                $table->string('badge')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['scope', 'icon']);
            });
        }

        if (! Schema::hasTable('facility_room')) {
            Schema::create('facility_room', function (Blueprint $table): void {
                $table->foreignId('room_id')->constrained()->cascadeOnDelete();
                $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
                $table->primary(['room_id', 'facility_id']);
            });
        }

        if (! Schema::hasTable('facility_hotel')) {
            Schema::create('facility_hotel', function (Blueprint $table): void {
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
                $table->primary(['hotel_id', 'facility_id']);
            });
        }
    }

    public function down(): void
    {
    }
};
