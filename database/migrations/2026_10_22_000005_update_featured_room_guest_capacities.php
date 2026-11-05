<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateRoomCapacity('aksara-ubud-resort-spa', 'Deluxe Valley', 4);
        $this->updateRoomCapacity('svara-signature-retreat', 'Signature Suite', 3);
    }

    public function down(): void
    {
        $this->updateRoomCapacity('aksara-ubud-resort-spa', 'Deluxe Valley', 2);
        $this->updateRoomCapacity('svara-signature-retreat', 'Signature Suite', 3);
    }

    private function updateRoomCapacity(string $hotelSlug, string $roomName, int $capacity): void
    {
        DB::table('rooms')
            ->whereIn('hotel_id', DB::table('hotels')->where('slug', $hotelSlug)->select('id'))
            ->where('name', $roomName)
            ->update(['max_guests' => $capacity]);
    }
};
