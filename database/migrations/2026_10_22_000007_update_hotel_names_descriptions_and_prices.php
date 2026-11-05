<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateHotel('aksara-ubud-resort-spa', [
            'name' => 'Rusunawa Hotel',
            'description' => 'Pemandangan taman yang tenang, termasuk sarapan.',
        ]);
        $this->updateRoom('aksara-ubud-resort-spa', 'Deluxe Valley', [
            'description' => 'Pemandangan taman yang tenang, termasuk sarapan.',
            'price_per_night' => 1,
        ]);

        $this->updateHotel('svara-signature-retreat', [
            'name' => 'Urbanview Hotel DJ Inn Tegal',
            'description' => 'Ruang lebih luas, detail personal, dan sarapan.',
        ]);
        $this->updateRoom('svara-signature-retreat', 'Signature Suite', [
            'description' => 'Ruang lebih luas, detail personal, dan sarapan.',
            'price_per_night' => 1,
        ]);

        $this->updateHotel('taman-sari-garden-hotel', [
            'name' => 'Ajeng Hotel',
            'description' => 'Kamar hangat dan damai, dekat masjid Kota Tegal.',
        ]);
        $this->updateRoom('taman-sari-garden-hotel', 'Garden Retreat', [
            'description' => 'Kamar hangat dan damai, dekat masjid Kota Tegal.',
            'price_per_night' => 1,
        ]);
    }

    public function down(): void
    {
        $this->updateHotel('aksara-ubud-resort-spa', [
            'name' => 'Aksara Ubud Resort & Spa',
            'description' => 'Resor tenang dengan kolam renang, spa, dan pemandangan lembah Ubud.',
        ]);
        $this->updateRoom('aksara-ubud-resort-spa', 'Deluxe Valley', [
            'description' => 'Pemandangan lembah yang tenang, termasuk sarapan.',
            'price_per_night' => 1450000,
        ]);

        $this->updateHotel('svara-signature-retreat', [
            'name' => 'Svara Signature Retreat',
            'description' => 'Suite lapang dengan layanan personal di tengah suasana tropis.',
        ]);
        $this->updateRoom('svara-signature-retreat', 'Signature Suite', [
            'description' => 'Ruang lebih luas, detail personal, dan sarapan.',
            'price_per_night' => 2250000,
        ]);

        $this->updateHotel('taman-sari-garden-hotel', [
            'name' => 'Taman Sari Garden Hotel',
            'description' => 'Hotel intim dekat taman tropis dan pilihan kuliner lokal.',
        ]);
        $this->updateRoom('taman-sari-garden-hotel', 'Garden Retreat', [
            'description' => 'Kamar hangat dan intim, dekat taman tropis.',
            'price_per_night' => 1180000,
        ]);
    }

    private function updateHotel(string $slug, array $attributes): void
    {
        DB::table('hotels')->where('slug', $slug)->update($attributes);
    }

    private function updateRoom(string $hotelSlug, string $roomName, array $attributes): void
    {
        DB::table('rooms')
            ->whereIn('hotel_id', DB::table('hotels')->where('slug', $hotelSlug)->select('id'))
            ->where('name', $roomName)
            ->update($attributes);
    }
};
