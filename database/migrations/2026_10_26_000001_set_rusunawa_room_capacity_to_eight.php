<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')
            ->whereIn(
                'hotel_id',
                DB::table('hotels')
                    ->where('slug', 'aksara-ubud-resort-spa')
                    ->select('id'),
            )
            ->update(['max_guests' => 8]);
    }

    public function down(): void
    {
        DB::table('rooms')
            ->whereIn(
                'hotel_id',
                DB::table('hotels')
                    ->where('slug', 'aksara-ubud-resort-spa')
                    ->select('id'),
            )
            ->update(['max_guests' => 4]);
    }
};
