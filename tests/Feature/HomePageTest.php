<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('hotels', 3);
    }

    public function test_home_page_shows_featured_database_records(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Hotel HAR Official — Menginap yang terasa tepat')
            ->assertSee('name="destination"', false)
            ->assertSee('id="landing-navigation"', false)
            ->assertSee('Curated stays, effortless moments')
            ->assertSee('<source src="'.asset('assets/hotell.mp4').'?v='.filemtime(public_path('assets/hotell.mp4')).'" type="video/mp4">', false)
            ->assertDontSee('poster=', false)
            ->assertSee('Waktunya')
            ->assertSee('Lokasi hotel')
            ->assertSee('value="Halmahera"', false)
            ->assertSee('value="Kota Tegal"', false)
            ->assertDontSee('value="Ubud, Bali"', false)
            ->assertDontSee('value="Canggu, Bali"', false)
            ->assertSee('landing-header')
            ->assertSee('name="checkin"', false)
            ->assertSee('name="checkin_time"', false)
            ->assertSee('name="checkout"', false)
            ->assertSee('name="checkout_time"', false)
            ->assertSee('value="14:00"', false)
            ->assertSee('value="12:00"', false)
            ->assertSee('name="guests"', false)
            ->assertSee('type="hidden" name="rooms" value="1"', false)
            ->assertSee('<small>Tamu</small>', false)
            ->assertDontSee('aria-label="Jumlah kamar"', false)
            ->assertDontSee('2 kamar</option>', false)
            ->assertSee('data-max-guests-per-room="8"', false)
            ->assertSee('>8 tamu</option>', false)
            ->assertDontSee('>9 tamu</option>', false)
            ->assertSee('href="#koleksi">Temukan Hotel</a>', false)
            ->assertSee('href="#cara">Cara Pesan</a>', false)
            ->assertSee('id="koleksi"', false)
            ->assertSee('id="cara"', false)
            ->assertDontSee('Fasilitas Hotel HAR Official')
            ->assertDontSee('Semua yang kamu butuhkan, sudah menunggu.')
            ->assertDontSee('Wi-Fi Cepat')
            ->assertDontSee('Sarapan Pagi')
            ->assertDontSee('Area Parkir')
            ->assertDontSee('AC dan Air Panas')
            ->assertDontSee('Resepsionis 24 Jam')
            ->assertDontSee('Keamanan dan CCTV')
            ->assertDontSee('Linen Premium')
            ->assertDontSee('Check-In Mudah')
            ->assertSee('class="back-top-button"', false)
            ->assertSee('data-back-to-top', false)
            ->assertSee('Kembali ke atas')
            ->assertSee('PT HAR Hotel Indonesia')
            ->assertDontSee('850+')
            ->assertSee('Rusunawa Hotel')
            ->assertSee('Pemandangan taman yang tenang, termasuk sarapan.')
            ->assertSee('Mulai Rp1')
            ->assertSee('Urbanview Hotel DJ Inn Tegal')
            ->assertSee('Ajeng Hotel')
            ->assertSee('Kamar hangat dan damai, dekat masjid Kota Tegal.')
            ->assertSee(e(route('rooms.show', [
                'room' => $this->hotelRoomId('aksara-ubud-resort-spa'),
                'destination' => 'Halmahera',
                'checkin' => now()->toDateString(),
                'checkin_time' => '14:00',
                'checkout' => now()->addDays(3)->toDateString(),
                'checkout_time' => '12:00',
                'guests' => 2,
                'rooms' => 1,
            ])), false);

        $this->assertDatabaseHas('hotels', [
            'slug' => 'svara-signature-retreat',
            'city' => 'Halmahera',
            'province' => 'Tegal Bahari',
        ]);
    }

    public function test_guest_options_follow_the_selected_room_capacity(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'rooms' => 2,
        ]))
            ->assertOk()
            ->assertSee('>8 tamu</option>', false)
            ->assertDontSee('>9 tamu</option>', false)
            ->assertDontSee('>12 tamu</option>', false);
    }

    public function test_home_page_defaults_to_two_guests_and_one_room(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<option value="2" selected>2 tamu</option>', false)
            ->assertSee('type="hidden" name="rooms" value="1"', false)
            ->assertDontSee('<option value="1" selected>1 tamu</option>', false);
    }

    public function test_search_page_preserves_selected_guest_count_after_refresh(): void
    {
        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('<option value="2" selected>2 tamu</option>', false)
            ->assertDontSee('<option value="1" selected>1 tamu</option>', false);
    }

    public function test_returning_from_search_preserves_one_selected_guest(): void
    {
        $filters = [
            'destination' => 'Halmahera',
            'checkin' => now()->addDay()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(4)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 1,
            'rooms' => 1,
        ];

        $this->get(route('hotels.search', $filters))
            ->assertOk()
            ->assertSee('guests=1&amp;rooms=1', false);

        $this->get(route('home', $filters))
            ->assertOk()
            ->assertSee('<option value="1" selected>1 tamu</option>', false)
            ->assertDontSee('<option value="2" selected>2 tamu</option>', false);
    }

    public function test_room_detail_page_offers_a_reservation_link(): void
    {
        $room = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail()->rooms()->firstOrFail();
        $filters = [
            'checkin' => now()->addDay()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(3)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ];

        $this->get(route('rooms.show', array_merge(['room' => $room], $filters)))
            ->assertOk()
            ->assertSee($room->name)
            ->assertSee('<dt>Kapasitas kamar</dt><dd>2 tamu</dd>', false)
            ->assertDontSee('<dt>Kapasitas kamar</dt><dd>8 tamu</dd>', false)
            ->assertSee('checkin_time=14%3A00&amp;checkout_time=12%3A00', false)
            ->assertSee('checkin='.$filters['checkin'].'&amp;checkout='.$filters['checkout'], false);
    }

    public function test_room_detail_capacity_indicator_uses_selected_guest_count(): void
    {
        $room = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail()->rooms()->firstOrFail();

        $this->get(route('rooms.show', [
            'room' => $room,
            'checkin' => now()->addDay()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(3)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 6,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('<dt>Kapasitas kamar</dt><dd>6 tamu</dd>', false);
    }

    public function test_signature_suite_room_capacity_is_four_guests(): void
    {
        $room = Hotel::where('slug', 'svara-signature-retreat')->firstOrFail()->rooms()->firstOrFail();

        $this->assertSame(4, $room->max_guests);
    }

    public function test_rusunawa_room_capacity_is_eight_guests(): void
    {
        $room = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail()->rooms()->firstOrFail();

        $this->assertSame(8, $room->max_guests);
    }

    private function hotelRoomId(string $hotelSlug): int
    {
        return Hotel::where('slug', $hotelSlug)->firstOrFail()->rooms()->firstOrFail()->id;
    }

    public function test_valid_search_displays_matching_database_results(): void
    {
        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('Ajeng Hotel')
            ->assertSee('1 properti ditemukan untuk 2 tamu')
            ->assertSee('1–2 tamu')
            ->assertDontSee('Rusunawa Hotel')
            ->assertDontSee('Urbanview Hotel DJ Inn Tegal')
            ->assertDontSee('>Kembali</a>', false);
    }

    public function test_view_all_hotels_bypasses_guest_mapping_and_lists_every_available_hotel(): void
    {
        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 8,
            'rooms' => 1,
            'all_hotels' => 1,
        ]))
            ->assertOk()
            ->assertSee('3 properti ditemukan untuk 8 tamu')
            ->assertSee('Ajeng Hotel')
            ->assertSee('Urbanview Hotel DJ Inn Tegal')
            ->assertSee('Rusunawa Hotel')
            ->assertSee('1–2 tamu')
            ->assertSee('3–4 tamu')
            ->assertSee('5–8 tamu')
            ->assertDontSee('1 kamar · 2 tamu')
            ->assertDontSee('1 kamar · 4 tamu');
    }

    public function test_rusunawa_search_shows_dynamic_eight_guest_capacity(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 8,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('Rusunawa Hotel')
            ->assertSee('1 kamar · 8 tamu')
            ->assertDontSee('Urbanview Hotel DJ Inn Tegal');
    }

    public function test_search_result_label_uses_selected_guest_count_without_room_count(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 5,
            'rooms' => 2,
        ]))
            ->assertOk()
            ->assertSee('5–8 tamu')
            ->assertDontSee('2 kamar · 5 tamu');
    }

    public function test_guest_count_maps_search_to_the_configured_hotel(): void
    {
        $searchCases = [
            [1, 'taman-sari-garden-hotel', 'Ajeng Hotel'],
            [2, 'taman-sari-garden-hotel', 'Ajeng Hotel'],
            [3, 'svara-signature-retreat', 'Urbanview Hotel DJ Inn Tegal'],
            [4, 'svara-signature-retreat', 'Urbanview Hotel DJ Inn Tegal'],
            [5, 'aksara-ubud-resort-spa', 'Rusunawa Hotel'],
            [8, 'aksara-ubud-resort-spa', 'Rusunawa Hotel'],
        ];

        foreach ($searchCases as [$guests, $slug, $hotelName]) {
            $response = $this->get(route('hotels.search', [
                'destination' => 'Halmahera',
                'checkin' => now()->addDay()->toDateString(),
                'checkout' => now()->addDays(4)->toDateString(),
                'guests' => $guests,
                'rooms' => 1,
            ]));

            $response->assertOk()
                ->assertSee($hotelName)
                ->assertSee(match ($slug) {
                    'taman-sari-garden-hotel' => '1–2 tamu',
                    'svara-signature-retreat' => '3–4 tamu',
                    default => '5–8 tamu',
                });

            foreach (['taman-sari-garden-hotel', 'svara-signature-retreat', 'aksara-ubud-resort-spa'] as $otherSlug) {
                if ($otherSlug !== $slug) {
                    $otherHotelName = Hotel::where('slug', $otherSlug)->value('name');
                    $response->assertDontSee($otherHotelName);
                }
            }
        }
    }

    public function test_search_rejects_guest_count_above_eight(): void
    {
        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 9,
            'rooms' => 2,
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors('guests');
    }

    public function test_search_rejects_guest_count_above_rusunawa_capacity(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 9,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('empty-state')
            ->assertDontSee('Rusunawa Hotel');
    }

    public function test_kota_tegal_search_only_shows_hotels_in_that_destination(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Kota Tegal',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('Ajeng Hotel')
            ->assertDontSee('Rusunawa Hotel');
    }

    public function test_search_hides_rooms_that_are_fully_booked(): void
    {
        $hotel = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail();
        $room = $hotel->rooms()->firstOrFail();
        $checkin = now()->addDay()->toDateString();
        $checkout = now()->addDays(4)->toDateString();

        for ($index = 0; $index < $room->quantity; $index++) {
            Reservation::create([
                'hotel_id' => $hotel->id,
                'room_id' => $room->id,
                'guest_name' => "Guest {$index}",
                'guest_email' => "guest{$index}@example.com",
                'check_in' => $checkin,
                'check_out' => $checkout,
                'guest_count' => 2,
                'room_count' => 1,
                'total_price' => $room->price_per_night,
                'status' => 'confirmed',
            ]);
        }

        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => $checkin,
            'checkout' => $checkout,
            'guests' => 8,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertDontSee('Rusunawa Hotel')
            ->assertSee('empty-state');
    }

    public function test_checkout_same_as_checkin_is_normalized_to_the_next_day(): void
    {
        $checkin = now()->addDay()->toDateString();

        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => $checkin,
            'checkout' => $checkin,
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('value="'.$checkin.'"', false)
            ->assertSee('value="'.\Illuminate\Support\Carbon::parse($checkin)->addDay()->toDateString().'"', false);
    }

    public function test_empty_destination_searches_all_destinations(): void
    {
        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('1 properti ditemukan untuk 2 tamu')
            ->assertSee('Ajeng Hotel')
            ->assertDontSee('Rusunawa Hotel')
            ->assertDontSee('Urbanview Hotel DJ Inn Tegal');
    }

    public function test_search_without_destination_defaults_to_all_locations(): void
    {
        $this->get(route('hotels.search', [
            'checkin' => now()->addDay()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(3)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('value="" selected', false)
            ->assertSee('1 properti ditemukan untuk 2 tamu')
            ->assertSee('Ajeng Hotel');
    }

    public function test_search_with_a_stale_checkin_date_uses_today_without_redirecting(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-26 00:03:00');

        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => '2026-10-25',
            'checkin_time' => '14:00',
            'checkout' => '2026-10-28',
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('value="2026-10-26"', false)
            ->assertSee('1 properti ditemukan untuk 2 tamu');

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_search_with_checkout_same_as_checkin_moves_checkout_forward_without_error(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-26 00:12:00');

        $this->get(route('hotels.search', [
            'destination' => '',
            'checkin' => '2026-10-26',
            'checkin_time' => '14:00',
            'checkout' => '2026-10-26',
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('value="2026-10-26"', false)
            ->assertSee('value="2026-10-27"', false)
            ->assertDontSee('The checkout field must be a date after checkin')
            ->assertSee('1 properti ditemukan untuk 2 tamu');

        \Illuminate\Support\Carbon::setTestNow();
    }
}
