<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('identity_type', 20)->nullable()->after('guest_phone');
            $table->text('identity_number')->nullable()->after('identity_type');
            $table->text('special_request')->nullable()->after('identity_number');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['identity_type', 'identity_number', 'special_request']);
        });
    }
};
