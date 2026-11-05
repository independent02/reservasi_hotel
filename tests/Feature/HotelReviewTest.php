<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HotelReviewTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private User $user;

    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->hotel = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail();
        $this->user = User::create([
            'name' => 'Rina Andini',
            'email' => 'rina@example.com',
            'password' => 'safe-password-123',
        ]);
        $this->reservation = $this->makeCompletedReservation();
    }

    public function test_search_cards_and_review_page_use_real_review_data(): void
    {
        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => today()->addDay()->toDateString(),
            'checkout' => today()->addDays(2)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('Belum ada ulasan')
            ->assertSee(route('hotels.reviews.show', $this->hotel->slug), false);

        $this->get(route('hotels.reviews.show', $this->hotel->slug))
            ->assertOk()
            ->assertSee('Ulasan '.$this->hotel->name)
            ->assertSee('Belum ada ulasan untuk filter ini');
    }

    public function test_review_back_link_uses_search_when_referrer_is_another_review_filter(): void
    {
        $reviewUrl = route('hotels.reviews.show', $this->hotel->slug);
        $this->from($reviewUrl.'?rating=4&sort=recent')
            ->get($reviewUrl.'?rating=3&sort=recent')
            ->assertOk()
            ->assertSee('class="hotel-reviews-nav-back" href="'.e(route('hotels.search', [
                'destination' => $this->hotel->city,
                'checkin' => today()->toDateString(),
                'checkout' => today()->addDay()->toDateString(),
                'guests' => 1,
                'rooms' => 1,
            ])).'"', false);
    }

    public function test_completed_reservation_owner_can_submit_a_review(): void
    {
        $this->actingAs($this->user)
            ->get(route('reservations.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee('Tulis ulasan')
            ->assertSee('review-dialog')
            ->assertSee('Pesanan #'.$this->reservation->id.' · Selesai');

        $this->get(route('hotels.reviews.show', $this->hotel->slug))
            ->assertOk()
            ->assertSee('review-dialog');

        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->reservation), [
                'reservation_id' => $this->reservation->id,
                'rating' => 5,
                'cleanliness' => 5,
                'comfort' => 4,
                'location' => 5,
                'service' => 5,
                'tags' => ['Bersih', 'Staf ramah'],
                'comment' => 'Kamar bersih dan staf sangat membantu.',
            ])
            ->assertRedirect(route('hotels.reviews.show', $this->hotel->slug))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reservation_id' => $this->reservation->id,
            'rating' => 5,
            'comment' => 'Kamar bersih dan staf sangat membantu.',
        ]);
        $this->assertSame(5.0, (float) $this->hotel->fresh()->rating);

        $this->get(route('hotels.reviews.show', $this->hotel->slug))
            ->assertOk()
            ->assertSee('Rina A.')
            ->assertSee('Tamu terverifikasi')
            ->assertSee('Bersih · Staf ramah');

        $this->get(route('hotels.search', [
            'destination' => 'Halmahera',
            'checkin' => today()->addDay()->toDateString(),
            'checkout' => today()->addDays(2)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))
            ->assertOk()
            ->assertSee('5.0')
            ->assertSee('1 ulasan');
    }

    public function test_review_submission_rejects_non_owner_and_incomplete_stays(): void
    {
        $otherUser = User::create([
            'name' => 'Pengguna lain',
            'email' => 'other@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($otherUser)
            ->post(route('reviews.store', $this->reservation), [
                'reservation_id' => $this->reservation->id,
                'rating' => 5,
            ])
            ->assertForbidden();

        $futureReservation = $this->makeCompletedReservation([
            'check_out' => today()->addDay(),
            'check_in' => today(),
        ]);

        $this->actingAs($this->user)
            ->post(route('reviews.store', $futureReservation), [
                'reservation_id' => $futureReservation->id,
                'rating' => 5,
            ])
            ->assertForbidden();
    }

    public function test_review_media_is_stored_on_the_public_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->user)
            ->post(route('reviews.store', $this->reservation), [
                'reservation_id' => $this->reservation->id,
                'rating' => 4,
                'photos' => [UploadedFile::fake()->createWithContent(
                    'kamar.png',
                    file_get_contents(public_path('assets/1kamar.PNG')),
                )],
                'videos' => [UploadedFile::fake()->create('suasana.mp4', 128, 'video/mp4')],
            ])
            ->assertRedirect(route('hotels.reviews.show', $this->hotel->slug));

        $review = Review::where('reservation_id', $this->reservation->id)->firstOrFail();
        $this->assertCount(2, $review->media);
        $this->assertSame('image', $review->media[0]['type']);
        $this->assertSame('video', $review->media[1]['type']);
        Storage::disk('public')->assertExists($review->media[0]['path']);
        Storage::disk('public')->assertExists($review->media[1]['path']);

        $this->get(route('hotels.reviews.show', $this->hotel->slug))
            ->assertOk()
            ->assertSee('hotel-reviews-topbar')
            ->assertSee('navbar-preferences-topbar')
            ->assertDontSee('navbar-preferences-floating')
            ->assertSee('hotel-reviews-nav-back')
            ->assertDontSee('class="hotel-reviews-back"')
            ->assertSee(route('reviews.media', ['review' => $review, 'index' => 0]), false)
            ->assertSee(route('reviews.media', ['review' => $review, 'index' => 1]), false);

        $this->get(route('reviews.media', ['review' => $review, 'index' => 0]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->get(route('reviews.media', ['review' => $review, 'index' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4');

        $this->get(route('reviews.media', ['review' => $review, 'index' => 2]))
            ->assertNotFound();
    }

    private function makeCompletedReservation(array $overrides = []): Reservation
    {
        $room = $this->hotel->rooms()->firstOrFail();

        return Reservation::create(array_merge([
            'hotel_id' => $this->hotel->id,
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'guest_name' => $this->user->name,
            'guest_email' => $this->user->email,
            'check_in' => today()->subDays(4),
            'check_out' => today()->subDays(2),
            'guest_count' => 2,
            'room_count' => 1,
            'total_price' => 100000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'voucher_code' => 'REVIEW-'.uniqid(),
        ], $overrides));
    }
}
