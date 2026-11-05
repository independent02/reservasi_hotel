<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomVoucherBenefitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_details_do_not_show_the_special_voucher_box(): void
    {
        $this->seed();

        $room = Room::query()
            ->where('name', 'Garden Retreat')
            ->whereHas('hotel', fn ($query) => $query->where('slug', 'taman-sari-garden-hotel'))
            ->firstOrFail();

        $this->get(route('rooms.show', ['room' => $room, 'guests' => 2, 'rooms' => 1]))
            ->assertOk()
            ->assertDontSee('VOUCHER KHUSUS KAMAR')
            ->assertDontSee('Benefit Garden Retreat')
            ->assertDontSee('Voucher Resto &amp; Cafe');
    }

    public function test_public_restaurant_facility_has_updated_copy_without_voucher_badge(): void
    {
        $this->seed();

        $room = Room::query()
            ->where('name', 'Garden Retreat')
            ->whereHas('hotel', fn ($query) => $query->where('slug', 'taman-sari-garden-hotel'))
            ->firstOrFail();

        $this->get(route('rooms.show', ['room' => $room, 'guests' => 2, 'rooms' => 1]))
            ->assertOk()
            ->assertSee('The Grand Resto & Coffee Lounge')
            ->assertSee('Sajian makanan lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.')
            ->assertSee('Gratis dengan e-voucher')
            ->assertSee('rd-facility-badge-cafe')
            ->assertDontSee('tersedia dengan e-voucher');
    }

    public function test_room_details_translate_facilities_and_cancellation_policies_to_english(): void
    {
        $this->seed();

        $room = Room::query()
            ->where('name', 'Garden Retreat')
            ->whereHas('hotel', fn ($query) => $query->where('slug', 'taman-sari-garden-hotel'))
            ->firstOrFail();

        $this->withSession(['locale' => 'en'])
            ->get(route('rooms.show', ['room' => $room, 'guests' => 2, 'rooms' => 1]))
            ->assertOk()
            ->assertSee('Enjoy your favorite entertainment in the comfort of your room.')
            ->assertSee('Adjust the room temperature to keep it cool and comfortable.')
            ->assertSee('Parking to make getting around easier for guests.')
            ->assertSee('Enjoy delicious restaurant dishes and the aroma of Indonesian coffee as you unwind.')
            ->assertSee('Complimentary with e-voucher')
            ->assertSee('More than 24 hours before check-in')
            ->assertSee('Cancellation is made 6 to 24 hours before check-in.')
            ->assertDontSee('Tonton hiburan favorit dengan nyaman di kamar.')
            ->assertDontSee('Area parkir untuk membantu kebutuhan perjalanan tamu.');
    }
}
