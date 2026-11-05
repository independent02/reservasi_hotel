<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\RefundCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Hotel $hotel;

    private Room $room;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->hotel = Hotel::query()->where('slug', 'taman-sari-garden-hotel')->firstOrFail();
        $this->room = $this->hotel->rooms()->firstOrFail();
        $this->user = User::create([
            'name' => 'Test Guest',
            'email' => 'test-guest@example.com',
            'password' => 'safe-password-123',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_refund_calculator_uses_exact_policy_boundaries(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');

        foreach ([25 => 100, 24 => 50, 6 => 50, 5 => 0] as $hours => $expectedPercent) {
            $reservation = $this->makeReservation(now()->addHours($hours));
            $refund = app(RefundCalculator::class)->calculate($reservation);

            $this->assertSame($expectedPercent, $refund['refund_percent']);
            $this->assertSame(round(100000 * $expectedPercent / 100, 2), $refund['refund_amount']);
        }

        Carbon::setTestNow();
    }

    public function test_owner_can_cancel_an_active_reservation_and_refund_is_recorded(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $reservation = $this->makeReservation(now()->addHours(30));

        $this->actingAs($this->user)
            ->delete(route('bookings.cancel', $reservation), [
                'reason' => 'Rencana perjalanan berubah',
                'ack' => 'yes',
            ])
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'cancel_reason' => 'Rencana perjalanan berubah',
            'refund_percent' => 100,
            'refund_amount' => 100000,
        ]);

        Carbon::setTestNow();
    }

    public function test_owner_can_cancel_on_the_checkout_date_even_after_checkout_time(): void
    {
        Carbon::setTestNow('2026-10-25 21:00:00');
        $reservation = $this->makeReservation(now()->subDay());
        $reservation->forceFill([
            'check_out' => today(),
            'check_out_time' => '12:00:00',
        ])->save();

        $this->actingAs($this->user)
            ->delete(route('bookings.cancel', $reservation), [
                'reason' => 'Rencana perjalanan berubah',
                'ack' => 'yes',
            ])
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'refund_percent' => 0,
            'refund_amount' => 0,
        ]);
    }

    public function test_owner_cannot_cancel_after_the_checkout_date(): void
    {
        Carbon::setTestNow('2026-10-25 21:00:00');
        $reservation = $this->makeReservation(now()->subDays(2));

        $this->actingAs($this->user)
            ->delete(route('bookings.cancel', $reservation), [
                'reason' => 'Rencana perjalanan berubah',
                'ack' => 'yes',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'cancelled_at' => null,
        ]);
    }

    public function test_another_user_cannot_cancel_a_reservation(): void
    {
        $reservation = $this->makeReservation(now()->addDays(2));
        $anotherUser = User::create([
            'name' => 'Other Guest',
            'email' => 'other-guest@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($anotherUser)
            ->delete(route('bookings.cancel', $reservation), [
                'reason' => 'Rencana perjalanan berubah',
                'ack' => 'yes',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'confirmed',
            'cancelled_at' => null,
        ]);
    }

    public function test_active_reservation_renders_a_disabled_confirmation_dialog(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $reservation = $this->makeReservation(now()->addHours(30));
        $checkIn = $reservation->check_in->toDateString();
        $checkOut = $reservation->check_out->toDateString();

        $this->actingAs($this->user)
            ->get(route('rooms.show', [
                'room' => $this->room,
                'destination' => 'Kota Tegal',
                'checkin' => $checkIn,
                'checkin_time' => '14:00',
                'checkout' => $checkOut,
                'checkout_time' => '12:00',
                'guests' => 1,
                'rooms' => 1,
            ]))
            ->assertOk()
            ->assertSee('Stayvia')
            ->assertSee('4,7')
            ->assertSee('rating hotel')
            ->assertDontSee('126 ulasan')
            ->assertSee('Salah memilih kamar atau tanggal')
            ->assertSee('Ingin mengganti metode pembayaran')
            ->assertSee('data-cancel-dialog', false)
            ->assertSee('data-cancel-submit disabled', false)
            ->assertSee(route('bookings.cancel', $reservation), false)
            ->assertSee('room-detail.css')
            ->assertSee('room-detail.js');

        Carbon::setTestNow();
    }

    public function test_waiting_admin_confirmation_reservation_can_be_cancelled_from_reservations_list(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $reservation = $this->makeReservation(now()->addDays(2));
        $reservation->forceFill([
            'status' => 'pending',
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/waiting-confirmation.jpg',
            'expires_at' => null,
        ])->save();

        $this->actingAs($this->user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Menunggu Konfirmasi Admin')
            ->assertSee('Batalkan pemesanan')
            ->assertSee('data-booking-cancel-dialog', false)
            ->assertSee('data-booking-cancel-submit disabled', false)
            ->assertSee(route('bookings.cancel', $reservation), false);

        $this->delete(route('bookings.cancel', $reservation), [
            'reason' => 'Rencana perjalanan berubah',
            'ack' => 'yes',
        ])
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'cancel_reason' => 'Rencana perjalanan berubah',
        ]);
    }

    public function test_admin_confirmed_reservation_can_be_cancelled_from_reservations_list(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $reservation = $this->makeReservation(now()->addDays(2));

        $this->actingAs($this->user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Terkonfirmasi')
            ->assertSee('Batalkan pemesanan')
            ->assertSee('data-booking-cancel-dialog', false)
            ->assertSee(route('bookings.cancel', $reservation), false);

        $this->delete(route('bookings.cancel', $reservation), [
            'reason' => 'Rencana perjalanan berubah',
            'ack' => 'yes',
        ])
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'cancel_reason' => 'Rencana perjalanan berubah',
        ]);
    }

    public function test_other_cancellation_reason_requires_and_saves_user_detail(): void
    {
        Carbon::setTestNow('2026-10-25 10:00:00');
        $reservation = $this->makeReservation(now()->addDays(2));

        $this->actingAs($this->user)
            ->from(route('reservations.index'))
            ->delete(route('bookings.cancel', $reservation), [
                'reason' => 'Alasan lain',
                'ack' => 'yes',
            ])
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHasErrors('reason_detail');

        $this->delete(route('bookings.cancel', $reservation), [
            'reason' => 'Alasan lain',
            'reason_detail' => 'Ada keperluan keluarga mendadak.',
            'ack' => 'yes',
        ])
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancel_reason' => 'Alasan lain: Ada keperluan keluarga mendadak.',
        ]);
    }

    private function makeReservation(Carbon $checkIn): Reservation
    {
        return Reservation::create([
            'hotel_id' => $this->hotel->id,
            'room_id' => $this->room->id,
            'user_id' => $this->user->id,
            'guest_name' => $this->user->name,
            'guest_email' => $this->user->email,
            'identity_type' => 'ktp',
            'identity_number' => '3174091203940001',
            'check_in' => $checkIn->toDateString(),
            'check_in_time' => $checkIn->format('H:i:s'),
            'check_out' => $checkIn->copy()->addDay()->toDateString(),
            'check_out_time' => '12:00:00',
            'guest_count' => 1,
            'room_count' => 1,
            'total_price' => 100000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }
}
