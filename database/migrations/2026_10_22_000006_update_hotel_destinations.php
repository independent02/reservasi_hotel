<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateHotelLocation('aksara-ubud-resort-spa', 'Halmahera', 'Tegal Bahari');
        $this->updateHotelLocation('svara-signature-retreat', 'Halmahera', 'Tegal Bahari');
        $this->updateHotelLocation('taman-sari-garden-hotel', 'Kota Tegal', 'Jawa Tengah');
    }

    public function down(): void
    {
        $this->updateHotelLocation('aksara-ubud-resort-spa', 'Ubud', 'Bali');
        $this->updateHotelLocation('svara-signature-retreat', 'Ubud', 'Bali');
        $this->updateHotelLocation('taman-sari-garden-hotel', 'Canggu', 'Bali');
    }

    private function updateHotelLocation(string $slug, string $city, string $province): void
    {
        DB::table('hotels')
            ->where('slug', $slug)
            ->update([
                'city' => $city,
                'province' => $province,
            ]);
    }
};
