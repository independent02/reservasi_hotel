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
            ->update([
                'name' => 'The Grand Resto & Coffee Lounge',
                'description' => 'Sajian lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.',
                'badge' => null,
                'sort_order' => 2,
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('facilities')
            ->where('scope', 'public')
            ->where('icon', 'cafe')
            ->update([
                'name' => 'Coffee Shop & Lobby Lounge',
                'description' => 'Kopi tubruk tersedia dengan e-voucher.',
                'badge' => 'Gratis dengan e-voucher',
                'sort_order' => 10,
                'updated_at' => now(),
            ]);
    }
};
