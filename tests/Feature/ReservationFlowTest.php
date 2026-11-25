<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('supabase');
        $this->seed();
        $this->user = User::create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'safe-password-123',
        ]);
        $this->hotel = Hotel::where('slug', 'aksara-ubud-resort-spa')->firstOrFail();

        config([
            'payments.admin_emails' => ['admin@example.com'],
            'payments.methods' => [
                'bca_va' => [
                    'category' => 'bank', 'label' => 'Transfer Bank BCA', 'short_label' => 'BCA',
                    'recipient' => '1234567890', 'account_name' => 'PT HAR Hotel Indonesia', 'enabled' => true,
                ],
                'mandiri_va' => [
                    'category' => 'bank', 'label' => 'Transfer Bank Mandiri', 'short_label' => 'MDR',
                    'recipient' => '9876543210', 'account_name' => 'PT HAR Hotel Indonesia', 'enabled' => true,
                ],
                'bri_va' => [
                    'category' => 'bank', 'label' => 'Transfer Bank BRI', 'short_label' => 'BRI',
                    'recipient' => null, 'account_name' => null, 'enabled' => false,
                ],
                'gopay' => [
                    'category' => 'ewallet', 'label' => 'GoPay', 'short_label' => 'GP',
                    'recipient' => null, 'account_name' => null, 'enabled' => false,
                ],
                'ovo' => [
                    'category' => 'ewallet', 'label' => 'OVO', 'short_label' => 'OVO',
                    'recipient' => null, 'account_name' => null, 'enabled' => false,
                ],
                'dana' => [
                    'category' => 'ewallet', 'label' => 'DANA', 'short_label' => 'DANA',
                    'recipient' => null, 'account_name' => null, 'enabled' => false,
                ],
                'shopeepay' => [
                    'category' => 'ewallet', 'label' => 'ShopeePay', 'short_label' => 'SPay',
                    'recipient' => null, 'account_name' => null, 'enabled' => false,
                ],
                'qris' => [
                    'category' => 'qris', 'label' => 'QRIS', 'short_label' => 'QRIS',
                    'recipient' => null, 'account_name' => null, 'qr_image' => null, 'enabled' => false,
                ],
            ],
            'payments.proof_disk' => 'supabase',
            'payments.expiry_hours' => 24,
        ]);
    }

    public function test_payment_proof_storage_uses_the_supabase_disk(): void
    {
        $this->assertSame('supabase', config('payments.proof_disk'));
        $this->assertSame('s3', config('filesystems.disks.supabase.driver'));
        $this->assertNotEmpty(config('filesystems.disks.supabase.bucket'));
    }

    public function test_booking_requires_an_authenticated_user(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();

        $this->get(route('reservations.create', [
            'room' => $room,
            'checkin' => now()->addDay()->toDateString(),
            'checkout' => now()->addDays(3)->toDateString(),
            'guests' => 2,
            'rooms' => 1,
        ]))->assertRedirect(route('login'));
    }

    public function test_room_plan_displays_ten_rooms_on_each_of_three_floors(): void
    {
        $this->assertSame('customer', $this->user->fresh()->role);
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();

        $this->actingAs($this->user)
            ->get(route('reservations.create', ['room' => $room, ...$dates, 'guests' => 2, 'rooms' => 1]))
            ->assertOk()
            ->assertSee('data-label="101"', false)
            ->assertSee('data-label="210"', false)
            ->assertSee('data-label="310"', false);

        $this->getJson(route('reservations.availability', ['room' => $room, ...$dates]))
            ->assertOk()
            ->assertJsonCount(30, 'rooms')
            ->assertJsonPath('rooms.29.number', 30);
    }

    public function test_user_can_create_reservation_without_a_payment_gateway(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();
        $this->lockRoom($room, $dates);

        $this->actingAs($this->user)
            ->post(route('reservations.store', $room), [
                ...$dates,
                'checkin_time' => '15:30',
                'checkout_time' => '11:15',
                'guests' => 2,
                'rooms' => 1,
                'room_numbers' => ['1'],
                'guest_name' => 'Ayu Putri',
                'guest_email' => 'ayu@example.com',
                'identity_type' => 'ktp',
                'identity_number' => '3174091203940001',
            ])
            ->assertRedirect();

        $reservation = Reservation::firstOrFail();
        $this->assertSame('15:30', substr($reservation->check_in_time, 0, 5));
        $this->assertSame('11:15', substr($reservation->check_out_time, 0, 5));
        $this->assertSame('pending', $reservation->payment_status);
        $this->assertSame('pending', $reservation->status);
        $this->assertTrue($reservation->expires_at->isFuture());
        $this->actingAs($this->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('flow-topbar')
            ->assertSee('Pilih Metode Pembayaran')
            ->assertSee('15:30')
            ->assertSee('11:15')
            ->assertSee('Instruksi Pembayaran')
            ->assertSee('Rekening pengirim')
            ->assertSee('Nama pemilik rekening pengirim')
            ->assertSee('name="account_name"', false)
            ->assertSee('Bank/aplikasi pengirim')
            ->assertSee('name="bank_name"', false)
            ->assertSee('data-sender-bank-name', false)
            ->assertSee('value="Transfer Bank BCA"', false)
            ->assertSee('readonly', false)
            ->assertSee('name="manual_verification_consent"', false)
            ->assertSee('data-payment-countdown', false)
            ->assertSee('payment-upload-dropzone', false)
            ->assertSee('Transfer Bank BCA')
            ->assertSee('1234567890')
            ->assertSee('Unggah Bukti Pembayaran');
    }

    public function test_rusunawa_reservation_rejects_more_than_eight_guests_per_room(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();
        $bookingData = [
            ...$dates,
            'rooms' => 1,
            'room_numbers' => ['1'],
            'guest_name' => 'Ayu Putri',
            'guest_email' => 'ayu@example.com',
            'identity_type' => 'ktp',
            'identity_number' => '3174091203940001',
        ];

        $this->actingAs($this->user)
            ->from(route('reservations.create', ['room' => $room, ...$dates, 'guests' => 9, 'rooms' => 1]))
            ->post(route('reservations.store', $room), [
                ...$bookingData,
                'guests' => 9,
            ])
            ->assertSessionHasErrors([
                'guests' => 'Maaf, kapasitas maksimal untuk kamar Rusunawa Hotel adalah 8 tamu per 1 kamar. Pemesanan di atas 8 tamu tidak diizinkan.',
            ]);

        $this->lockRoom($room, $dates);
        $this->post(route('reservations.store', $room), [
            ...$bookingData,
            'guests' => 8,
        ])->assertRedirect();

        $this->assertDatabaseHas('reservations', [
            'room_id' => $room->id,
            'guest_count' => 8,
        ]);
    }

    public function test_overlapping_room_is_rejected_even_if_a_selection_lock_was_obtained_first(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();
        $this->lockRoom($room, $dates);
        $this->makeReservation([
            'check_in' => $dates['checkin'],
            'check_out' => $dates['checkout'],
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'voucher_code' => 'HTL-OVERLAP',
        ]);

        $this->actingAs($this->user)
            ->post(route('reservations.store', $room), [
                ...$dates,
                'guests' => 2,
                'rooms' => 1,
                'room_numbers' => ['1'],
                'guest_name' => 'Ayu Putri',
                'guest_email' => 'ayu@example.com',
                'identity_type' => 'ktp',
                'identity_number' => '3174091203940001',
            ])
            ->assertSessionHasErrors('room_numbers');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_room_selection_lock_expires_and_releases_room_for_another_user(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();
        $this->lockRoom($room, $dates);
        $other = User::create([
            'name' => 'Pengguna lain',
            'email' => 'other@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($other)
            ->postJson(route('reservations.lock-selection', $room), [
                ...$dates,
                'rooms' => 1,
                'room_numbers' => [1],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_numbers');

        $this->travel(11)->minutes();
        $this->postJson(route('reservations.lock-selection', $room), [
            ...$dates,
            'rooms' => 1,
            'room_numbers' => [1],
        ])->assertOk();
    }

    public function test_user_can_replace_their_temporarily_locked_room_selection(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $dates = $this->stayDates();
        $this->lockRoom($room, $dates);

        $this->actingAs($this->user)
            ->postJson(route('reservations.lock-selection', $room), [
                ...$dates,
                'rooms' => 1,
                'room_numbers' => [2],
            ])
            ->assertOk()
            ->assertJsonPath('room_numbers', [2]);

        $this->assertDatabaseMissing('room_selection_locks', [
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'room_number' => 1,
            'check_in' => $dates['checkin'],
            'check_out' => $dates['checkout'],
        ]);
        $this->assertDatabaseHas('room_selection_locks', [
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'room_number' => 2,
            'check_in' => $dates['checkin'],
            'check_out' => $dates['checkout'],
        ]);
    }

    public function test_guest_transfer_proof_waits_for_admin_and_admin_approval_issues_voucher(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->post(route('reservations.payment.proof', $reservation), [
                'payment_method' => 'bca_va',
                'manual_verification_consent' => '1',
                'bank_name' => 'BCA',
                'account_name' => 'Ayu Putri',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHas('status');

        $reservation->refresh();
        $this->assertSame('waiting_confirmation', $reservation->payment_status);
        $this->assertSame('bca_va', $reservation->payment_method);
        $this->assertSame('Transfer Bank BCA', $reservation->destination_bank);
        $this->assertSame('Ayu Putri', $reservation->account_name);
        $this->assertSame('Transfer Bank BCA', $reservation->bank_name);
        $this->assertSame('pending', $reservation->status);
        $this->assertNull($reservation->expires_at);
        $this->assertTrue(Storage::disk('supabase')->exists($reservation->payment_proof));
        $this->actingAs($this->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Bukti transfer diterima')
            ->assertSee('Menunggu Verifikasi Admin')
            ->assertSee('flow-card');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->get(route('admin.payment-reviews.index'))
            ->assertOk()
            ->assertSee('Rekening pengirim')
            ->assertSee('Ayu Putri · Transfer Bank BCA');
        $this->get(route('admin.payment-reviews.proof', $reservation))->assertOk();
        $this->patch(route('admin.payment-reviews.review', $reservation), ['decision' => 'approve'])
            ->assertRedirect(route('admin.payment-reviews.index'));

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('confirmed', $reservation->status);
        $this->assertNotNull($reservation->paid_at);
        $this->assertNotEmpty($reservation->voucher_code);
        $this->actingAs($this->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Booking Berhasil!')
            ->assertSee($reservation->voucher_code)
            ->assertDontSee('Lihat Pesanan Saya');

        $this->get(route('reservations.voucher', $reservation))
            ->assertOk()
            ->assertSee('Kembali ke pesanan saya')
            ->assertSee(route('reservations.index'))
            ->assertSee('The Grand Resto & Coffee Lounge')
            ->assertSee('Sajian makanan lezat hidangan resto dan aroma kopi khas Nusantara untuk menemani santai Anda.')
            ->assertSee('Gratis dengan e-voucher')
            ->assertSee('Benefit Rusunawa Hotel untuk tipe kamar Deluxe Valley.');
    }

    public function test_only_admin_role_can_review_private_transfer_proofs(): void
    {
        $reservation = $this->makeReservation([
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/sample.jpg',
            'payment_submitted_at' => now(),
            'expires_at' => null,
        ]);
        Storage::disk('supabase')->put('proofs/sample.jpg', 'proof');

        $this->actingAs($this->user)
            ->get(route('admin.payment-reviews.index'))
            ->assertForbidden()
            ->assertDontSee('Pesanan #'.$reservation->id);
        $this->get(route('admin.payment-reviews.proof', $reservation))->assertForbidden();

        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]))->get(route('admin.payment-reviews.proof', $reservation))->assertOk();
    }

    public function test_admin_payment_portal_searches_and_filters_all_reservation_statuses(): void
    {
        $waiting = $this->makeReservation([
            'guest_name' => 'Khaekal Putra',
            'guest_email' => 'khaekal@example.com',
            'identity_type' => 'ktp',
            'identity_number' => '3174012345678901',
            'special_request' => 'Kamar lantai tinggi, non-smoking.',
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/waiting.jpg',
            'payment_submitted_at' => now(),
            'expires_at' => null,
        ]);
        $confirmed = $this->makeReservation([
            'guest_name' => 'Rina Ayu',
            'guest_email' => 'rina@example.com',
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $cancelled = $this->makeReservation([
            'guest_name' => 'Intan Sari',
            'guest_email' => 'intan@example.com',
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-reviews.index'))
            ->assertOk()
            ->assertSee('Verifikasi Pembayaran')
            ->assertSee('#'.$waiting->id)
            ->assertSee('#'.$confirmed->id)
            ->assertSee('#'.$cancelled->id)
            ->assertSee('data-bank="Nama tidak tersedia · Bank tidak disebutkan"', false)
            ->assertSee('data-identity-type="KTP"', false)
            ->assertSee('data-identity-number="3174012345678901"', false)
            ->assertSee('data-special-request="Kamar lantai tinggi, non-smoking."', false)
            ->assertSee('data-booking-detail', false);

        $this->get(route('admin.payment-reviews.index', ['status' => 'waiting', 'q' => 'Khaekal']))
            ->assertOk()
            ->assertSee('Khaekal Putra')
            ->assertDontSee('Rina Ayu')
            ->assertDontSee('Intan Sari');

        $this->get(route('admin.payment-reviews.index', ['status' => 'confirmed']))
            ->assertOk()
            ->assertSee('Rina Ayu')
            ->assertSee('Menampilkan 1-1 dari 1 pesanan')
            ->assertDontSee('Intan Sari');
    }

    public function test_admin_pages_are_translated_when_english_is_selected(): void
    {
        $this->makeReservation([
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/waiting.jpg',
            'payment_submitted_at' => now(),
            'expires_at' => null,
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Admin Dashboard')
            ->assertSee('Hotel owner portal')
            ->assertSee('Awaiting verification')
            ->assertSee('View queue')
            ->assertDontSee('Menunggu verifikasi');

        $this->get(route('admin.payment-reviews.index'))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Payment Verification')
            ->assertSee('Search bookings')
            ->assertSee('Guest Name & Email')
            ->assertSee('Awaiting verification')
            ->assertSee('Transfer Proof')
            ->assertDontSee('Cari pesanan')
            ->assertDontSee('Menunggu Verifikasi');
    }

    public function test_admin_can_delete_a_reservation_and_its_private_payment_proof(): void
    {
        $reservation = $this->makeReservation([
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/remove-me.jpg',
            'payment_submitted_at' => now(),
            'expires_at' => null,
        ]);
        $proofDisk = Storage::disk('supabase');
        $proofDisk->put('proofs/remove-me.jpg', 'private-proof');
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.payment-reviews.destroy', $reservation))
            ->assertRedirect(route('admin.payment-reviews.index'))
            ->assertSessionHas('status', 'Pesanan berhasil dihapus.');

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
        $this->assertFalse($proofDisk->exists('proofs/remove-me.jpg'));
    }

    public function test_payment_page_lists_bank_and_ewallet_methods_but_marks_unconfigured_destinations_unavailable(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
        ->get(route('reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Pilih Metode Pembayaran')
        ->assertSee('Transfer Bank BCA')
        ->assertSee('Transfer Bank Mandiri')
        ->assertSee('Transfer Bank BRI')
        ->assertSee('GoPay')
        ->assertSee('OVO')
        ->assertSee('DANA')
        ->assertSee('ShopeePay')
        ->assertSee('QRIS')
        ->assertSee('Belum tersedia')
        ->assertSee('value="bca_va"', false)
        ->assertSee('value="gopay"', false)
        ->assertSee('value="bri_va"', false)
        ->assertDontSee('bni_va');
    }

    public function test_payment_proof_requires_manual_verification_consent(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->from(route('reservations.show', $reservation))
            ->post(route('reservations.payment.proof', $reservation), [
                'payment_method' => 'bca_va',
                'bank_name' => 'BCA',
                'account_name' => 'Ayu Putri',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHasErrors('manual_verification_consent');

        $this->assertSame('pending', $reservation->fresh()->payment_status);
    }

    public function test_sender_bank_is_automatically_saved_from_the_selected_payment_method(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->post(route('reservations.payment.proof', $reservation), [
                'payment_method' => 'bca_va',
                'manual_verification_consent' => '1',
                'account_name' => 'Ayu Putri',
                'bank_name' => 'Nama yang berbeda',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHas('status');

        $this->assertSame('Transfer Bank BCA', $reservation->fresh()->bank_name);
        $this->assertSame('Ayu Putri', $reservation->fresh()->account_name);
    }

    public function test_payment_proof_requires_sender_account_name(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->from(route('reservations.show', $reservation))
            ->post(route('reservations.payment.proof', $reservation), [
                'payment_method' => 'bca_va',
                'manual_verification_consent' => '1',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHasErrors('account_name');

        $this->assertSame('pending', $reservation->fresh()->payment_status);
    }

    public function test_configured_bri_and_shopeepay_methods_can_be_submitted_for_manual_review(): void
    {
        config(['payments.methods.bri_va' => [
            'category' => 'bank',
            'label' => 'Transfer Bank BRI',
            'short_label' => 'BRI',
            'recipient' => '123456789012345',
            'account_name' => 'PT HAR Hotel Indonesia',
            'enabled' => true,
        ]]);

        $briReservation = $this->makeReservation();
        $this->actingAs($this->user)
            ->post(route('reservations.payment.proof', $briReservation), [
                'payment_method' => 'bri_va',
                'manual_verification_consent' => '1',
                'account_name' => 'Ayu Putri',
                'bank_name' => 'BRI',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $briReservation));
        $this->assertSame('bri_va', $briReservation->fresh()->payment_method);
        $this->assertSame('Transfer Bank BRI', $briReservation->fresh()->destination_bank);
        $this->assertSame('Transfer Bank BRI', $briReservation->fresh()->bank_name);

        config(['payments.methods.shopeepay' => [
            'category' => 'ewallet',
            'label' => 'ShopeePay',
            'short_label' => 'SPay',
            'recipient' => '081234567890',
            'account_name' => 'PT HAR Hotel Indonesia',
            'enabled' => true,
        ]]);

        $shopeepayReservation = $this->makeReservation();
        $this->post(route('reservations.payment.proof', $shopeepayReservation), [
            'payment_method' => 'shopeepay',
            'manual_verification_consent' => '1',
            'account_name' => 'Ayu Putri',
            'bank_name' => 'ShopeePay',
            'payment_proof' => $this->fakePngProof(),
        ])->assertRedirect(route('reservations.show', $shopeepayReservation));
        $this->assertSame('shopeepay', $shopeepayReservation->fresh()->payment_method);
        $this->assertSame('ShopeePay', $shopeepayReservation->fresh()->destination_bank);
        $this->assertSame('ShopeePay', $shopeepayReservation->fresh()->bank_name);
    }

    public function test_configured_qris_method_displays_qr_and_accepts_proof_upload(): void
    {
        config(['payments.methods' => [
            'qris' => [
                'category' => 'qris',
                'label' => 'QRIS',
                'short_label' => 'QRIS',
                'recipient' => null,
                'account_name' => 'PT HAR Hotel Indonesia',
                'qr_image' => 'assets/qris-hotel.png',
                'enabled' => true,
            ],
        ]]);
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Pindai QRIS ini melalui aplikasi pembayaran Anda.')
            ->assertSee('data-method-qr-image="'.asset('assets/qris-hotel.png').'"', false)
            ->assertSee('data-selected-method-qr', false)
            ->assertSee('placeholder="Contoh: BCA, GoPay, atau bank/aplikasi yang Anda gunakan"', false);

        $this->post(route('reservations.payment.proof', $reservation), [
            'payment_method' => 'qris',
            'manual_verification_consent' => '1',
            'account_name' => 'Ayu Putri',
            'bank_name' => 'BCA',
            'payment_proof' => $this->fakePngProof(),
        ])->assertRedirect(route('reservations.show', $reservation));

        $this->assertSame('qris', $reservation->fresh()->payment_method);
        $this->assertSame('QRIS', $reservation->fresh()->destination_bank);
        $this->assertSame('BCA', $reservation->fresh()->bank_name);
    }

    public function test_qris_proof_requires_sender_bank_or_app_name(): void
    {
        config(['payments.methods' => [
            'qris' => [
                'category' => 'qris',
                'label' => 'QRIS',
                'short_label' => 'QRIS',
                'recipient' => null,
                'account_name' => 'PT HAR Hotel Indonesia',
                'qr_image' => 'assets/qris-hotel.png',
                'enabled' => true,
            ],
        ]]);
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->from(route('reservations.show', $reservation))
            ->post(route('reservations.payment.proof', $reservation), [
                'payment_method' => 'qris',
                'manual_verification_consent' => '1',
                'account_name' => 'Ayu Putri',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertRedirect(route('reservations.show', $reservation))
            ->assertSessionHasErrors('bank_name');

        $this->assertSame('pending', $reservation->fresh()->payment_status);
    }

    public function test_configured_ewallet_method_can_be_selected_and_recorded_for_manual_review(): void
    {
        config(['payments.methods.gopay' => [
        'category' => 'ewallet',
        'label' => 'GoPay',
        'short_label' => 'GP',
        'recipient' => '081234567890',
        'account_name' => 'PT HAR Hotel Indonesia',
        'enabled' => true,
        ]]);
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
        ->post(route('reservations.payment.proof', $reservation), [
            'payment_method' => 'gopay',
            'manual_verification_consent' => '1',
            'account_name' => 'Ayu Putri',
            'bank_name' => 'GoPay',
            'payment_proof' => $this->fakePngProof(),
        ])
        ->assertRedirect(route('reservations.show', $reservation));

        $this->assertSame('gopay', $reservation->fresh()->payment_method);
        $this->assertSame('GoPay', $reservation->fresh()->destination_bank);
        $this->assertSame('GoPay', $reservation->fresh()->bank_name);
    }

    public function test_admin_can_cancel_waiting_transfer_and_expired_payment_cannot_be_uploaded(): void
    {
        $waiting = $this->makeReservation([
            'payment_status' => 'waiting_confirmation',
            'payment_proof' => 'proofs/sample.jpg',
            'payment_submitted_at' => now(),
            'expires_at' => null,
        ]);
        $expired = $this->makeReservation([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'expires_at' => now()->subMinute(),
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'safe-password-123',
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->patch(route('admin.payment-reviews.review', $waiting), ['decision' => 'cancel'])
            ->assertRedirect();
        $this->assertSame('cancelled', $waiting->fresh()->payment_status);
        $this->assertSame('cancelled', $waiting->fresh()->status);

        $this->actingAs($this->user)
            ->from(route('reservations.show', $expired))
            ->post(route('reservations.payment.proof', $expired), [
                'payment_method' => 'bca_va',
                'manual_verification_consent' => '1',
                'account_name' => 'Ayu Putri',
                'bank_name' => 'BCA',
                'payment_proof' => $this->fakePngProof(),
            ])
            ->assertSessionHasErrors('payment_proof');
        $this->assertSame('cancelled', $expired->fresh()->payment_status);
        $this->assertSame('expired', $expired->fresh()->status);
    }

    public function test_user_cannot_view_another_users_reservation_or_submit_bad_proof(): void
    {
        $reservation = $this->makeReservation();
        $other = User::create([
            'name' => 'Pengguna lain',
            'email' => 'other@example.com',
            'password' => 'safe-password-123',
        ]);

        $this->actingAs($other)->get(route('reservations.show', $reservation))->assertForbidden();
        $this->delete(route('reservations.destroy', $reservation))->assertForbidden();
        $this->post(route('reservations.payment.proof', $reservation), [
            'payment_method' => 'bca_va',
            'payment_proof' => UploadedFile::fake()->create('not-an-image.pdf', 20, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_my_reservations_filters_by_status_and_current_user(): void
    {
        $room = $this->hotel->rooms()->firstOrFail();
        $active = $this->makeReservation();
        $completed = $this->makeReservation([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'voucher_code' => 'HTL-DONE-1',
            'check_in' => now()->subDays(4)->toDateString(),
            'check_out' => now()->subDays(2)->toDateString(),
        ]);
        $cancelled = $this->makeReservation([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
        ]);

        $this->actingAs($this->user)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Pesanan #'.$active->id)
            ->assertDontSee('data-booking-delete-trigger', false)
            ->assertDontSee('HTL-DONE-1');
        $this->get(route('reservations.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee('Pesanan #'.$completed->id)
            ->assertSee('Selesai')
            ->assertDontSee('data-booking-delete-trigger', false);
        $this->get(route('reservations.index', ['tab' => 'cancelled']))
            ->assertOk()
            ->assertSee('Pesanan #'.$cancelled->id)
            ->assertSee('data-booking-delete-trigger', false)
            ->assertSee('booking-delete-dialog', false);
    }

    public function test_empty_completed_bookings_tab_shows_an_informative_empty_state(): void
    {
        $this->actingAs($this->user)
            ->get(route('reservations.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee('Belum ada pesanan')
            ->assertSee('Pesanan di tab ini akan muncul di sini.')
            ->assertSee('Mulai Cari Kamar');
    }

    public function test_completed_tab_uses_checkout_time_for_reservations_ending_today(): void
    {
        Carbon::setTestNow('2026-10-25 22:00:00');
        $completedToday = $this->makeReservation([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_in' => today()->subDays(3)->toDateString(),
            'check_out' => today()->toDateString(),
            'check_out_time' => '12:00:00',
        ]);
        $activeToday = $this->makeReservation([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'check_in' => today()->subDay()->toDateString(),
            'check_out' => today()->toDateString(),
            'check_out_time' => '23:00:00',
        ]);

        $this->actingAs($this->user)
            ->get(route('reservations.index', ['tab' => 'completed']))
            ->assertOk()
            ->assertSee('Pesanan #'.$completedToday->id)
            ->assertSee('Selesai')
            ->assertDontSee('Pesanan #'.$activeToday->id);

        $this->get(route('reservations.index', ['tab' => 'active']))
            ->assertOk()
            ->assertSee('Pesanan #'.$activeToday->id)
            ->assertDontSee('Pesanan #'.$completedToday->id);

        Carbon::setTestNow();
    }

    public function test_owner_can_delete_cancelled_or_expired_reservations_and_their_private_proof(): void
    {
        $cancelled = $this->makeReservation([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'payment_proof' => 'proofs/cancelled.jpg',
        ]);
        $expired = $this->makeReservation([
            'status' => 'pending',
            'payment_status' => 'pending',
            'expires_at' => now()->subMinute(),
            'payment_proof' => null,
        ]);
        $proofDisk = Storage::disk('supabase');
        $proofDisk->put('proofs/cancelled.jpg', 'private-proof');

        $this->actingAs($this->user)
            ->get(route('reservations.index', ['tab' => 'cancelled']))
            ->assertOk()
            ->assertSee('Pesanan #'.$cancelled->id)
            ->assertSee('Pesanan #'.$expired->id)
            ->assertSee('data-booking-delete-trigger', false);

        $this->delete(route('reservations.destroy', $cancelled))
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status', 'Pesanan berhasil dihapus.');

        $this->assertDatabaseMissing('reservations', ['id' => $cancelled->id]);
        $this->assertFalse($proofDisk->exists('proofs/cancelled.jpg'));

        $this->delete(route('reservations.destroy', $expired))
            ->assertRedirect(route('reservations.index', ['tab' => 'cancelled']))
            ->assertSessionHas('status', 'Pesanan berhasil dihapus.');
        $this->assertDatabaseMissing('reservations', ['id' => $expired->id]);
    }

    public function test_owner_cannot_delete_active_or_completed_reservations(): void
    {
        $active = $this->makeReservation();
        $completed = $this->makeReservation([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'check_in' => now()->subDays(4)->toDateString(),
            'check_out' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('reservations.destroy', $active))
            ->assertForbidden();
        $this->delete(route('reservations.destroy', $completed))
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', ['id' => $active->id]);
        $this->assertDatabaseHas('reservations', ['id' => $completed->id]);
    }

    private function makeReservation(array $attributes = []): Reservation
    {
        $room = $this->hotel->rooms()->firstOrFail();

        return Reservation::create(array_merge([
            'hotel_id' => $this->hotel->id,
            'room_id' => $room->id,
            'user_id' => $this->user->id,
            'guest_name' => $this->user->name,
            'guest_email' => $this->user->email,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guest_count' => 2,
            'room_count' => 1,
            'room_numbers' => [1],
            'total_price' => 100000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'expires_at' => now()->addDay(),
        ], $attributes));
    }

    private function stayDates(): array
    {
        return [
            'checkin' => now()->addDays(10)->toDateString(),
            'checkout' => now()->addDays(12)->toDateString(),
        ];
    }

    private function fakePngProof(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'transfer.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j/l8AAAAASUVORK5CYII='),
        );
    }

    private function lockRoom(Room $room, array $dates): void
    {
        $this->actingAs($this->user)->postJson(route('reservations.lock-selection', $room), [
            ...$dates,
            'rooms' => 1,
            'room_numbers' => [1],
        ])->assertOk();
    }
}
