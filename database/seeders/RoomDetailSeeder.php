<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\CancellationPolicy;
use Illuminate\Database\Seeder;

class RoomDetailSeeder extends Seeder
{
    public function run(): void
    {
        $tegalHotel = Hotel::query()->where('slug', 'taman-sari-garden-hotel')->first();
        if ($tegalHotel) {
            $tegalHotel->fill([
                'address' => '44XX+X59, Mintaragen, Tegal Timur, Tegal City, Central Java 52121',
                'maps_url' => 'https://maps.app.goo.gl/cWfH4pCL6gQvnanM6',
            ])->save();
        }

        $facilities = [
            ['scope' => 'room', 'icon' => 'tv', 'name' => 'Smart TV', 'description' => 'Tonton hiburan favorit dengan nyaman di kamar.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'ac', 'name' => 'AC', 'description' => 'Atur suhu kamar agar tetap sejuk dan nyaman.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'fridge', 'name' => 'Kulkas', 'description' => 'Simpan minuman dan makanan ringan tetap dingin.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'desk', 'name' => 'Meja kerja', 'description' => 'Ruang praktis untuk bekerja atau membaca.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'bed', 'name' => 'Tempat tidur nyaman', 'description' => 'Beristirahat dengan nyaman setelah beraktivitas.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'bath', 'name' => 'Kamar mandi pribadi', 'description' => 'Kamar mandi pribadi untuk kenyamanan selama menginap.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'kettle', 'name' => 'Teko listrik', 'description' => 'Nikmati minuman hangat kapan saja.', 'badge' => null],
            ['scope' => 'room', 'icon' => 'wifi', 'name' => 'Wi-Fi kamar', 'description' => 'Tetap terhubung dari dalam kamar.', 'badge' => null],
            ['scope' => 'public', 'icon' => 'park', 'name' => 'Area parkir', 'description' => 'Area parkir untuk membantu kebutuhan perjalanan tamu.', 'badge' => null],
            ['scope' => 'public', 'icon' => 'cafe', 'name' => 'The Grand Resto & Coffee Lounge', 'description' => 'Sajian makanan lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.', 'badge' => 'Gratis dengan e-voucher', 'sort_order' => 2],
            ['scope' => 'public', 'icon' => 'cloche', 'name' => 'Layanan kamar 24 jam', 'description' => 'Bantuan dan layanan kamar tersedia sepanjang hari.', 'badge' => null],
            ['scope' => 'public', 'icon' => 'wifi', 'name' => 'Wi-Fi area umum', 'description' => 'Akses internet di area umum hotel.', 'badge' => null],
        ];

        foreach ($facilities as $index => $facilityData) {
            Facility::updateOrCreate(
                ['scope' => $facilityData['scope'], 'icon' => $facilityData['icon']],
                [
                    'name' => $facilityData['name'],
                    'description' => $facilityData['description'],
                    'badge' => $facilityData['badge'],
                    'sort_order' => $facilityData['sort_order'] ?? $index + 1,
                    'is_active' => true,
                ],
            );
        }

        $roomFacilityIds = Facility::query()->where('scope', 'room')->pluck('facilities.id');
        Room::query()->where('is_active', true)->each(
            fn (Room $room) => $room->facilities()->syncWithoutDetaching($roomFacilityIds),
        );

        $publicFacilityIds = Facility::query()->where('scope', 'public')->pluck('facilities.id');
        Hotel::query()->where('is_active', true)->each(
            fn (Hotel $hotel) => $hotel->facilities()->syncWithoutDetaching($publicFacilityIds),
        );

        $policies = [
            ['title' => 'Lebih dari 24 jam sebelum check-in', 'description' => 'Pembatalan dilakukan lebih dari 24 jam sebelum waktu check-in.', 'min_hours' => 24, 'max_hours' => null, 'refund_percent' => 100, 'sort_order' => 1],
            ['title' => '6–24 jam sebelum check-in', 'description' => 'Pembatalan dilakukan 6 hingga 24 jam sebelum waktu check-in.', 'min_hours' => 6, 'max_hours' => 24, 'refund_percent' => 50, 'sort_order' => 2],
            ['title' => 'Kurang dari 6 jam atau setelah check-in', 'description' => 'Pembatalan kurang dari 6 jam sebelum check-in atau setelah check-in tidak mendapat refund.', 'min_hours' => 0, 'max_hours' => 6, 'refund_percent' => 0, 'sort_order' => 3],
        ];

        foreach ($policies as $policy) {
            CancellationPolicy::updateOrCreate(
                ['hotel_id' => null, 'title' => $policy['title']],
                $policy,
            );
        }
    }
}
