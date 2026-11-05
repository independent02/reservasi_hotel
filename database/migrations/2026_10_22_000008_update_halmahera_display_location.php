<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hotels')
            ->where('city', 'Halmahera')
            ->where('province', 'Maluku Utara')
            ->update(['province' => 'Tegal Bahari']);
    }

    public function down(): void
    {
        DB::table('hotels')
            ->where('city', 'Halmahera')
            ->where('province', 'Tegal Bahari')
            ->update(['province' => 'Maluku Utara']);
    }
};
