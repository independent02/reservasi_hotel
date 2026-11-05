<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FaqSeeder::class);

        $hotels = [
            [
                'name' => 'Rusunawa Hotel',
                'slug' => 'aksara-ubud-resort-spa',
                'city' => 'Halmahera',
                'province' => 'Tegal Bahari',
                'description' => 'Pemandangan taman yang tenang, termasuk sarapan.',
                'image' => 'stayvia-deluxe.jpg',
                'rating' => 4.9,
                'room' => 'Deluxe Valley',
                'room_description' => 'Pemandangan taman yang tenang, termasuk sarapan.',
                'price' => 1,
                'guests' => 8,
                'quantity' => 30,
            ],
            [
                'name' => 'Urbanview Hotel DJ Inn Tegal',
                'slug' => 'svara-signature-retreat',
                'city' => 'Halmahera',
                'province' => 'Tegal Bahari',
                'description' => 'Ruang lebih luas, detail personal, dan sarapan.',
                'image' => 'stayvia-suite.jpg',
                'rating' => 4.8,
                'room' => 'Signature Suite',
                'room_description' => 'Ruang lebih luas, detail personal, dan sarapan.',
                'price' => 1,
                'guests' => 4,
                'quantity' => 30,
            ],
            [
                'name' => 'Ajeng Hotel',
                'slug' => 'taman-sari-garden-hotel',
                'city' => 'Kota Tegal',
                'province' => 'Jawa Tengah',
                'description' => 'Kamar hangat dan damai, dekat masjid Kota Tegal.',
                'image' => 'stayvia-warm.jpg',
                'rating' => 4.7,
                'address' => '44XX+X59, Mintaragen, Tegal Timur, Tegal City, Central Java 52121',
                'maps_url' => 'https://maps.app.goo.gl/cWfH4pCL6gQvnanM6',
                'room' => 'Garden Retreat',
                'room_description' => 'Kamar hangat dan damai, dekat masjid Kota Tegal.',
                'price' => 1,
                'guests' => 2,
                'quantity' => 30,
            ],
        ];

        foreach ($hotels as $hotelData) {
            $roomData = [
                'name' => $hotelData['room'],
                'description' => $hotelData['room_description'],
                'image' => $hotelData['image'],
                'price_per_night' => $hotelData['price'],
                'max_guests' => $hotelData['guests'],
                'quantity' => $hotelData['quantity'],
                'is_featured' => true,
                'is_active' => true,
            ];

            unset(
                $hotelData['room'],
                $hotelData['room_description'],
                $hotelData['price'],
                $hotelData['guests'],
                $hotelData['quantity'],
            );

            $hotel = Hotel::updateOrCreate(
                ['slug' => $hotelData['slug']],
                $hotelData,
            );

            Room::updateOrCreate(
                ['hotel_id' => $hotel->id, 'name' => $roomData['name']],
                $roomData,
            );
        }

        $this->call(RoomDetailSeeder::class);
    }
}
