<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\RoomVoucherBenefits;
use App\Services\VoucherQrCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController
{
    private const SELECTION_LOCK_MINUTES = 10;

    public function create(Request $request, Room $room): View
    {
        abort_unless($room->is_active && $room->hotel->is_active, 404);

        $filters = array_replace([
            'checkin' => now()->addDay()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(4)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ], $request->validate([
            'checkin' => ['sometimes', 'date', 'after_or_equal:today'],
            'checkin_time' => ['sometimes', 'date_format:H:i'],
            'checkout' => ['sometimes', 'date', 'after:checkin'],
            'checkout_time' => ['sometimes', 'date_format:H:i'],
            'guests' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]));
        $this->assertGuestCapacity($room, (int) $filters['guests'], (int) $filters['rooms']);

        $locks = DB::table('room_selection_locks')
            ->where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->where('check_in', $filters['checkin'])
            ->where('check_out', $filters['checkout'])
            ->where('expires_at', '>', now())
            ->orderBy('room_number')
            ->get();

        $selectedNumbers = $locks->pluck('room_number')->map(fn ($number): int => (int) $number)->all();
        $filters['room_numbers'] = $selectedNumbers;

        return view('reservations.create', [
            'room' => $room->load('hotel'),
            'filters' => $filters,
            'roomStates' => $this->roomStates($room, $filters, $request->user()->id),
            'selectionExpiry' => $locks->first()?->expires_at
                ? Carbon::parse($locks->first()->expires_at)->toIso8601String()
                : null,
        ]);
    }

    public function availability(Request $request, Room $room): JsonResponse
    {
        abort_unless($room->is_active && $room->hotel->is_active, 404);

        $filters = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin'],
        ]);

        return response()->json([
            'rooms' => $this->roomStates($room, $filters, $request->user()->id),
            'selection_expiry' => $this->selectionExpiry($room, $request->user()->id, $filters),
        ]);
    }

    public function lockSelection(Request $request, Room $room): JsonResponse
    {
        abort_unless($room->is_active && $room->hotel->is_active, 404);

        $validated = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'room_numbers' => ['required', 'array', 'max:'.$room->quantity],
            'room_numbers.*' => ['required', 'integer', 'min:1', 'max:'.$room->quantity],
        ]);

        $numbers = collect($validated['room_numbers'])->map(fn ($number): int => (int) $number);
        if ($numbers->unique()->count() !== $numbers->count()
            || ($numbers->isNotEmpty() && $numbers->count() !== (int) $validated['rooms'])) {
            throw ValidationException::withMessages([
                'room_numbers' => 'Pilih jumlah kamar yang sesuai dan jangan memilih nomor kamar yang sama.',
            ]);
        }

        $expiry = $numbers->isEmpty() ? null : now()->addMinutes(self::SELECTION_LOCK_MINUTES);

        DB::transaction(function () use ($request, $room, $validated, $numbers, $expiry): void {
            DB::table('room_selection_locks')
                ->where('expires_at', '<=', now())
                ->delete();

            $this->assertRoomsAvailable(
                $room,
                $validated['checkin'],
                $validated['checkout'],
                $numbers->all(),
                $request->user()->id,
            );

            DB::table('room_selection_locks')
                ->where('room_id', $room->id)
                ->where('user_id', $request->user()->id)
                ->where('check_in', $validated['checkin'])
                ->where('check_out', $validated['checkout'])
                ->delete();

            if ($expiry !== null) {
                $now = now();
                DB::table('room_selection_locks')->insert(
                    $numbers->map(fn (int $number): array => [
                        'room_id' => $room->id,
                        'user_id' => $request->user()->id,
                        'room_number' => $number,
                        'check_in' => $validated['checkin'],
                        'check_out' => $validated['checkout'],
                        'expires_at' => $expiry,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                );
            }
        });

        return response()->json([
            'room_numbers' => $numbers->values()->all(),
            'expires_at' => $expiry?->toIso8601String(),
        ]);
    }

    public function continueToGuest(Request $request, Room $room): \Illuminate\Http\RedirectResponse
    {
        $validated = $this->validateRoomSelection($request, $room);
        $this->assertSelectionLocked($request, $room, $validated);

        return redirect()->route('reservations.guest', [
            'room' => $room,
            ...$validated,
        ]);
    }

    public function guestDetails(Request $request, Room $room): View|\Illuminate\Http\RedirectResponse
    {
        abort_unless($room->is_active && $room->hotel->is_active, 404);
        $validated = $this->validateRoomSelection($request, $room);

        try {
            $this->assertSelectionLocked($request, $room, $validated);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('reservations.create', [
                    'room' => $room,
                    ...collect($validated)->except('room_numbers')->all(),
                ])
                ->withErrors($exception->errors());
        }

        $expiry = $this->selectionExpiry($room, $request->user()->id, $validated);
        $displayRoomNumbers = collect($validated['room_numbers'])
            ->map(fn (int $number): int => $this->displayRoomNumber($number));
        $nights = Carbon::parse($validated['checkin'])->diffInDays(Carbon::parse($validated['checkout']));

        return view('reservations.guest', [
            'room' => $room->load('hotel'),
            'filters' => $validated,
            'displayRoomNumbers' => $displayRoomNumbers,
            'selectionExpiry' => $expiry,
            'nights' => $nights,
        ]);
    }

    public function store(Request $request, Room $room): \Illuminate\Http\RedirectResponse
    {
        abort_unless($room->is_active && $room->hotel->is_active, 404);

        $validated = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkin_time' => ['sometimes', 'date_format:H:i'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'checkout_time' => ['sometimes', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:12'],
            'rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'room_numbers' => ['required', 'array', 'min:1', 'max:'.$room->quantity],
            'room_numbers.*' => ['required', 'integer', 'min:1', 'max:'.$room->quantity],
            'guest_name' => ['required', 'string', 'max:100'],
            'guest_email' => ['required', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:32'],
            'identity_type' => ['required', 'in:ktp,passport'],
            'identity_number' => ['required', 'string', 'max:50'],
            'special_request' => ['nullable', 'string', 'max:1000'],
        ]);
        $validated = array_replace([
            'checkin_time' => '14:00',
            'checkout_time' => '12:00',
        ], $validated);
        $this->assertGuestCapacity($room, (int) $validated['guests'], (int) $validated['rooms']);

        $numbers = collect($validated['room_numbers'])->map(fn ($number): int => (int) $number)->unique()->sort()->values();
        if ($numbers->count() !== (int) $validated['rooms']) {
            throw ValidationException::withMessages([
                'room_numbers' => 'Pilih jumlah kamar yang sesuai sebelum melanjutkan.',
            ]);
        }

        $reservation = DB::transaction(function () use ($request, $room, $validated, $numbers): Reservation {
            $selection = [
                'checkin' => $validated['checkin'],
                'checkout' => $validated['checkout'],
                'rooms' => (int) $validated['rooms'],
                'room_numbers' => $numbers->all(),
            ];
            $this->assertSelectionLocked($request, $room, $selection);
            $this->assertRoomsAvailable(
                $room,
                $validated['checkin'],
                $validated['checkout'],
                $numbers->all(),
                $request->user()->id,
            );

            $nights = Carbon::parse($validated['checkin'])->diffInDays(Carbon::parse($validated['checkout']));
            $reservation = Reservation::create([
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'user_id' => $request->user()->id,
                'guest_name' => $validated['guest_name'],
                'guest_email' => $validated['guest_email'],
                'guest_phone' => $validated['guest_phone'] ?? null,
                'identity_type' => $validated['identity_type'],
                'identity_number' => $validated['identity_number'],
                'special_request' => $validated['special_request'] ?? null,
                'check_in' => $validated['checkin'],
                'check_in_time' => $validated['checkin_time'],
                'check_out' => $validated['checkout'],
                'check_out_time' => $validated['checkout_time'],
                'guest_count' => $validated['guests'],
                'room_count' => $validated['rooms'],
                'total_price' => (float) $room->price_per_night * $nights * $numbers->count(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'room_numbers' => $numbers->all(),
                'expires_at' => now()->addHours((int) config('payments.expiry_hours', 24)),
            ]);

            DB::table('room_selection_locks')
                ->where('room_id', $room->id)
                ->where('user_id', $request->user()->id)
                ->where('check_in', $validated['checkin'])
                ->where('check_out', $validated['checkout'])
                ->whereIn('room_number', $numbers->all())
                ->delete();

            return $reservation;
        });

        return redirect()->route('reservations.show', $reservation);
    }

    public function index(Request $request): View
    {
        $activeTab = $request->validate([
            'tab' => ['sometimes', 'in:active,completed,cancelled'],
        ])['tab'] ?? 'active';

        $baseQuery = Reservation::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('user_id');
        $tabs = [
            'active' => ['label' => 'Aktif', 'count' => $this->reservationsForTab(clone $baseQuery, 'active')->count()],
            'completed' => ['label' => 'Selesai', 'count' => $this->reservationsForTab(clone $baseQuery, 'completed')->count()],
            'cancelled' => ['label' => 'Dibatalkan', 'count' => $this->reservationsForTab(clone $baseQuery, 'cancelled')->count()],
        ];
        $reservations = $this->reservationsForTab(clone $baseQuery, $activeTab)
            ->with(['hotel', 'room', 'review'])
            ->orderBy('check_in')
            ->orderByDesc('created_at')
            ->paginate(8)
            ->withQueryString();

        return view('reservations.index', [
            'reservations' => $reservations,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
        ]);
    }

    public function destroy(Request $request, Reservation $reservation): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeOwner($request, $reservation);

        $proofPath = DB::transaction(function () use ($request, $reservation): ?string {
            $lockedReservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            $this->authorizeOwner($request, $lockedReservation);

            $isExpiredUnpaid = $lockedReservation->status === 'pending'
                && $lockedReservation->payment_status === 'pending'
                && $lockedReservation->expires_at?->isPast()
                && blank($lockedReservation->payment_proof);

            abort_unless(
                in_array($lockedReservation->status, ['cancelled', 'expired'], true) || $isExpiredUnpaid,
                403,
                'Hanya pesanan dibatalkan atau kedaluwarsa yang dapat dihapus.',
            );

            $paymentProof = $lockedReservation->payment_proof;
            $lockedReservation->delete();

            return $paymentProof;
        });

        if ($proofPath) {
            try {
                $proofDisk = Storage::disk(config('payments.proof_disk', 'supabase'));
                if (! $proofDisk->delete($proofPath)) {
                    Log::warning('Could not remove payment proof for deleted reservation.', [
                        'reservation_id' => $reservation->id,
                        'proof_path' => $proofPath,
                    ]);

                    return redirect()->route('reservations.index', ['tab' => 'cancelled'])
                        ->with('warning', 'Pesanan dihapus, tetapi file bukti pembayaran tidak berhasil dibersihkan. Hubungi admin untuk pemeriksaan.');
                }
            } catch (\Throwable $exception) {
                Log::error('Could not remove payment proof for deleted reservation.', [
                    'reservation_id' => $reservation->id,
                    'proof_path' => $proofPath,
                    'exception' => $exception,
                ]);

                return redirect()->route('reservations.index', ['tab' => 'cancelled'])
                    ->with('warning', 'Pesanan dihapus, tetapi file bukti pembayaran tidak berhasil dibersihkan. Hubungi admin untuk pemeriksaan.');
            }
        }

        return redirect()->route('reservations.index', ['tab' => 'cancelled'])
            ->with('status', 'Pesanan berhasil dihapus.');
    }

    public function show(
        Request $request,
        Reservation $reservation,
        VoucherQrCodeService $qrCode,
        RoomVoucherBenefits $voucherBenefits,
    ): View
    {
        $this->authorizeOwner($request, $reservation);

        if ($reservation->status === 'pending'
            && $reservation->expires_at?->isPast()
            && blank($reservation->payment_proof)) {
            $reservation->update(['status' => 'expired', 'payment_status' => 'cancelled']);
        }

        return view('reservations.show', [
            'reservation' => $reservation->load(['hotel', 'room.hotel']),
            'paymentMethods' => config('payments.methods', []),
            'voucherQrCode' => $reservation->status === 'confirmed' && filled($reservation->voucher_code)
                ? $qrCode->dataUri($reservation->voucher_code)
                : null,
            'voucherBenefits' => $reservation->status === 'confirmed'
                ? $voucherBenefits->forRoom($reservation->room)
                : [],
        ]);
    }

    public function voucher(
        Request $request,
        Reservation $reservation,
        VoucherQrCodeService $qrCode,
        RoomVoucherBenefits $voucherBenefits,
    ): View
    {
        $this->authorizeOwner($request, $reservation);
        abort_unless($reservation->status === 'confirmed' && filled($reservation->voucher_code), 404);

        return view('reservations.voucher', [
            'reservation' => $reservation->load(['hotel', 'room.hotel']),
            'voucherQrCode' => $qrCode->dataUri($reservation->voucher_code),
            'voucherBenefits' => $voucherBenefits->forRoom($reservation->room),
        ]);
    }

    private function validateRoomSelection(Request $request, Room $room): array
    {
        $validated = $request->validate([
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkin_time' => ['sometimes', 'date_format:H:i'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'checkout_time' => ['sometimes', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:12'],
            'rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'room_numbers' => ['required', 'array', 'min:1', 'max:'.$room->quantity],
            'room_numbers.*' => ['required', 'integer', 'min:1', 'max:'.$room->quantity],
        ]);
        $validated = array_replace([
            'checkin_time' => '14:00',
            'checkout_time' => '12:00',
        ], $validated);
        $this->assertGuestCapacity($room, (int) $validated['guests'], (int) $validated['rooms']);

        $numbers = collect($validated['room_numbers'])->map(fn ($number): int => (int) $number)->unique()->sort()->values();

        if ($numbers->count() !== (int) $validated['rooms']) {
            throw ValidationException::withMessages([
                'room_numbers' => 'Pilih jumlah kamar yang sesuai sebelum melanjutkan.',
            ]);
        }

        $validated['room_numbers'] = $numbers->all();

        return $validated;
    }

    private function assertGuestCapacity(Room $room, int $guestCount, int $roomCount): void
    {
        $room->loadMissing('hotel');

        $capacityPerRoom = (int) $room->max_guests;
        $isRusunawa = $room->hotel->slug === 'aksara-ubud-resort-spa';
        if ($isRusunawa) {
            $capacityPerRoom = min($capacityPerRoom, 8);
        }

        $maxGuests = $capacityPerRoom * $roomCount;
        if ($guestCount <= $maxGuests) {
            return;
        }

        $message = $isRusunawa
            ? ($roomCount === 1
                ? 'Maaf, kapasitas maksimal untuk kamar Rusunawa Hotel adalah 8 tamu per 1 kamar. Pemesanan di atas 8 tamu tidak diizinkan.'
                : "Maaf, kapasitas maksimal kamar Rusunawa Hotel adalah 8 tamu per kamar. Untuk {$roomCount} kamar, maksimal {$maxGuests} tamu.")
            : "Maaf, kapasitas maksimal untuk {$roomCount} kamar ini adalah {$maxGuests} tamu.";

        throw ValidationException::withMessages(['guests' => $message]);
    }

    private function reservationsForTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'active' => $query->where(function (Builder $query): void {
                $query->where(function (Builder $pending): void {
                    $pending->where('status', 'pending')
                        ->where(function (Builder $expiry): void {
                            $expiry->whereNull('expires_at')
                                ->orWhere('expires_at', '>=', now());
                        });
                })->orWhere(function (Builder $confirmed): void {
                    $confirmed->where('status', 'confirmed')
                        ->where(function (Builder $checkout): void {
                            $checkout->whereDate('check_out', '>', today())
                                ->orWhere(function (Builder $sameDay): void {
                                    $sameDay->whereDate('check_out', today())
                                        ->whereTime('check_out_time', '>', now()->format('H:i:s'));
                                });
                        });
                });
            }),
            'completed' => $query->where('status', 'confirmed')
                ->where(function (Builder $checkout): void {
                    $checkout->whereDate('check_out', '<', today())
                        ->orWhere(function (Builder $sameDay): void {
                            $sameDay->whereDate('check_out', today())
                                ->whereTime('check_out_time', '<=', now()->format('H:i:s'));
                        });
                }),
            'cancelled' => $query->where(function (Builder $query): void {
                $query->whereIn('status', ['cancelled', 'expired', 'refunded'])
                    ->orWhere(function (Builder $pending): void {
                        $pending->where('status', 'pending')
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<', now())
                            ->whereNull('payment_proof');
                    });
            }),
            default => $query,
        };
    }

    private function assertSelectionLocked(Request $request, Room $room, array $selection): void
    {
        $lockedNumbers = DB::table('room_selection_locks')
            ->where('room_id', $room->id)
            ->where('user_id', $request->user()->id)
            ->where('check_in', $selection['checkin'])
            ->where('check_out', $selection['checkout'])
            ->where('expires_at', '>', now())
            ->whereIn('room_number', $selection['room_numbers'])
            ->pluck('room_number')
            ->map(fn ($number): int => (int) $number)
            ->unique()
            ->all();

        if (count($lockedNumbers) !== count($selection['room_numbers'])) {
            throw ValidationException::withMessages([
                'room_numbers' => 'Waktu penguncian kamar habis atau pilihan kamar tidak lagi tersedia. Silakan pilih ulang.',
            ]);
        }
    }

    private function assertRoomsAvailable(
        Room $room,
        string $checkin,
        string $checkout,
        array $numbers,
        int $userId,
    ): void {
        if ($numbers === []) {
            return;
        }

        $occupied = Reservation::query()
            ->where('room_id', $room->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('check_in', '<', $checkout)
            ->whereDate('check_out', '>', $checkin)
            ->where(function (Builder $query): void {
                $query->where('status', 'confirmed')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get(['room_numbers'])
            ->flatMap(fn (Reservation $reservation): array => $reservation->room_numbers ?? [])
            ->map(fn ($number): int => (int) $number)
            ->unique()
            ->all();

        $lockedByOthers = DB::table('room_selection_locks')
            ->where('room_id', $room->id)
            ->where('user_id', '!=', $userId)
            ->where('check_in', $checkin)
            ->where('check_out', $checkout)
            ->where('expires_at', '>', now())
            ->whereIn('room_number', $numbers)
            ->pluck('room_number')
            ->map(fn ($number): int => (int) $number)
            ->all();

        if (array_intersect($numbers, [...$occupied, ...$lockedByOthers]) !== []) {
            throw ValidationException::withMessages([
                'room_numbers' => 'Nomor kamar yang dipilih sudah terisi atau sedang dikunci tamu lain.',
            ]);
        }
    }

    private function roomStates(Room $room, array $filters, int $userId): array
    {
        $occupied = Reservation::query()
            ->where('room_id', $room->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('check_in', '<', $filters['checkout'])
            ->whereDate('check_out', '>', $filters['checkin'])
            ->where(function (Builder $query): void {
                $query->where('status', 'confirmed')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get(['room_numbers'])
            ->flatMap(fn (Reservation $reservation): array => $reservation->room_numbers ?? [])
            ->map(fn ($number): int => (int) $number)
            ->unique()
            ->all();

        $locks = DB::table('room_selection_locks')
            ->where('room_id', $room->id)
            ->where('check_in', $filters['checkin'])
            ->where('check_out', $filters['checkout'])
            ->where('expires_at', '>', now())
            ->get(['room_number', 'user_id']);

        $lockByNumber = $locks->keyBy('room_number');

        return collect(range(1, max(1, (int) $room->quantity)))
            ->map(function (int $number) use ($occupied, $lockByNumber, $userId): array {
                $lock = $lockByNumber->get($number);
                $status = in_array($number, $occupied, true)
                    ? 'occupied'
                    : ($lock
                        ? ((int) $lock->user_id === $userId ? 'selected' : 'locked')
                        : 'available');

                return ['number' => $number, 'status' => $status];
            })
            ->all();
    }

    private function selectionExpiry(Room $room, int $userId, array $filters): ?string
    {
        $expiresAt = DB::table('room_selection_locks')
            ->where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('check_in', $filters['checkin'])
            ->where('check_out', $filters['checkout'])
            ->where('expires_at', '>', now())
            ->min('expires_at');

        return $expiresAt ? Carbon::parse($expiresAt)->toIso8601String() : null;
    }

    private function displayRoomNumber(int $number): int
    {
        $floor = intdiv($number - 1, 10) + 1;
        $roomIndex = (($number - 1) % 10) + 1;

        return ($floor * 100) + $roomIndex;
    }

    private function authorizeOwner(Request $request, Reservation $reservation): void
    {
        abort_unless((int) $reservation->user_id === (int) $request->user()->id, 403);
    }

}
