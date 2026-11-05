<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('cleanliness')->nullable();
            $table->unsignedTinyInteger('comfort')->nullable();
            $table->unsignedTinyInteger('location')->nullable();
            $table->unsignedTinyInteger('service')->nullable();
            $table->json('tags')->nullable();
            $table->text('comment')->nullable();
            $table->json('media')->nullable();
            $table->text('hotel_response')->nullable();
            $table->timestamp('hotel_responded_at')->nullable();
            $table->timestamps();
            $table->index(['hotel_id', 'rating']);
            $table->index(['hotel_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
