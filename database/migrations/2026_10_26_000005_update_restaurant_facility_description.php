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
                'description' => 'Sajian makanan lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('facilities')
            ->where('scope', 'public')
            ->where('icon', 'cafe')
            ->update([
                'description' => 'Sajian lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.',
                'updated_at' => now(),
            ]);
    }
};
