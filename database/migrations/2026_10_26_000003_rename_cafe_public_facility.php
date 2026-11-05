<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('facilities')
            ->where('scope', 'public')
            ->where('icon', 'cafe')
            ->update(['name' => 'Coffee Shop & Lobby Lounge']);
    }

    public function down(): void
    {
        DB::table('facilities')
            ->where('scope', 'public')
            ->where('icon', 'cafe')
            ->update(['name' => 'Kafe']);
    }
};
