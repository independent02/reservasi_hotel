<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cancellation_policies')) {
            Schema::create('cancellation_policies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('hotel_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description');
                $table->unsignedSmallInteger('min_hours');
                $table->unsignedSmallInteger('max_hours')->nullable();
                $table->unsignedTinyInteger('refund_percent');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['hotel_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
    }
};
