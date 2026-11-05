<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateRoomCapacity(4);
    }

    public function down(): void
    {
        $this->updateRoomCapacity(3);
    }

    private function updateRoomCapacity(int $capacity): void
    {
        DB::table('rooms')
            ->whereIn(
                'hotel_id',
                DB::table('hotels')
                    ->where('slug', 'svara-signature-retreat')
                    ->select('id'),
            )
            ->where('name', 'Signature Suite')
            ->update(['max_guests' => $capacity]);
    }
};
