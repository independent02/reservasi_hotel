<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManualPaymentController
{
    public function uploadProof(Request $request, Reservation $reservation): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeOwner($request, $reservation);

        $methods = array_filter(
            config('payments.methods', []),
            static fn (array $method): bool => $method['enabled'],
        );
        $selectedMethod = $methods[$request->input('payment_method')] ?? null;
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:'.implode(',', array_keys($methods))],
            'manual_verification_consent' => ['required', 'accepted'],
            'bank_name' => ($selectedMethod['category'] ?? null) === 'qris'
                ? ['required', 'string', 'max:80']
                : ['nullable', 'string', 'max:80'],
            'account_name' => ['required', 'string', 'max:100'],
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('payments.proof_max_kilobytes', 5120)],
        ]);

        if ($reservation->status !== 'pending' || $reservation->payment_status !== 'pending') {
            throw ValidationException::withMessages([
                'payment_proof' => 'Bukti hanya dapat dikirim untuk reservasi yang menunggu pembayaran.',
            ]);
        }

        if (! $reservation->expires_at || $reservation->expires_at->isPast()) {
            $reservation->update(['status' => 'expired', 'payment_status' => 'cancelled']);
            throw ValidationException::withMessages([
                'payment_proof' => 'Batas waktu pembayaran telah berakhir. Silakan buat reservasi baru.',
            ]);
        }

        $method = $methods[$validated['payment_method']] ?? null;
        abort_unless($method, 422, 'Metode pembayaran tidak tersedia.');

        $disk = Storage::build([
            'driver' => 'local',
            'root' => config('payments.proof_path'),
            'throw' => true,
        ]);
        $path = $disk->putFile('', $request->file('payment_proof'));
        if (! is_string($path)) {
            throw new \RuntimeException('Could not store the uploaded payment proof.');
        }

        try {
            DB::transaction(function () use ($reservation, $validated, $method, $path): void {
                $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
                if ($locked->status !== 'pending'
                    || $locked->payment_status !== 'pending'
                    || ! $locked->expires_at
                    || $locked->expires_at->isPast()) {
                    throw ValidationException::withMessages([
                        'payment_proof' => 'Reservasi tidak lagi dapat menerima bukti pembayaran.',
                    ]);
                }

                $locked->update([
                    'payment_status' => 'waiting_confirmation',
                    'payment_method' => $validated['payment_method'],
                    'payment_proof' => $path,
                    'destination_bank' => $method['label'],
                    'bank_name' => $method['category'] === 'qris'
                        ? $validated['bank_name']
                        : $method['label'],
                    'account_name' => $validated['account_name'] ?? null,
                    'payment_submitted_at' => now(),
                    'expires_at' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return redirect()->route('reservations.show', $reservation)
            ->with('status', 'Bukti transfer diterima. Pesanan menunggu verifikasi admin.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,waiting,confirmed,cancelled'],
        ]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'all';

        $query = Reservation::query()->with(['hotel', 'room', 'user']);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $orderNumber = ltrim($search, '#');
                if ($orderNumber !== '' && ctype_digit($orderNumber)) {
                    $builder->whereKey((int) $orderNumber);
                }

                $builder->orWhere('guest_name', 'like', '%'.$search.'%')
                    ->orWhere('guest_email', 'like', '%'.$search.'%')
                    ->orWhere('guest_phone', 'like', '%'.$search.'%')
                    ->orWhereHas('room', fn (Builder $room): Builder => $room->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('hotel', fn (Builder $hotel): Builder => $hotel->where('name', 'like', '%'.$search.'%'));
            });
        }

        match ($status) {
            'waiting' => $query->where('payment_status', 'waiting_confirmation'),
            'confirmed' => $query->where(function (Builder $builder): void {
                $builder->where('status', 'confirmed')->orWhere('payment_status', 'paid');
            }),
            'cancelled' => $query->where(function (Builder $builder): void {
                $builder->whereIn('status', ['cancelled', 'expired', 'refunded'])
                    ->orWhere('payment_status', 'cancelled');
            }),
            default => null,
        };

        $reservations = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(6)
            ->withQueryString();
        $pendingCount = Reservation::query()->where('payment_status', 'waiting_confirmation')->count();
        $recentPending = Reservation::query()
            ->where('payment_status', 'waiting_confirmation')
            ->with(['room'])
            ->orderByDesc('payment_submitted_at')
            ->limit(3)
            ->get();

        return view('admin.payments.index', compact(
            'reservations',
            'pendingCount',
            'recentPending',
            'search',
            'status',
        ));
    }

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'pendingReviews' => Reservation::query()->where('payment_status', 'waiting_confirmation')->count(),
            'confirmedBookings' => Reservation::query()->where('status', 'confirmed')->count(),
            'customers' => \App\Models\User::query()->whereIn('role', ['customer', 'pemesan'])->count(),
        ]);
    }

    public function proof(Request $request, Reservation $reservation): StreamedResponse
    {
        $this->authorizeAdmin($request);
        abort_unless(filled($reservation->payment_proof), 404);

        $disk = Storage::build([
            'driver' => 'local',
            'root' => config('payments.proof_path'),
            'throw' => true,
        ]);
        abort_unless($disk->exists($reservation->payment_proof), 404);

        return $disk->response($reservation->payment_proof, 'bukti-transfer-'.$reservation->id, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function review(Request $request, Reservation $reservation): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['decision' => ['required', 'in:approve,cancel']]);

        DB::transaction(function () use ($reservation, $validated): void {
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            if ($locked->payment_status !== 'waiting_confirmation' || blank($locked->payment_proof)) {
                throw ValidationException::withMessages([
                    'decision' => 'Pesanan ini tidak sedang menunggu verifikasi pembayaran.',
                ]);
            }

            if ($validated['decision'] === 'cancel') {
                $locked->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);

                return;
            }

            $overlap = Reservation::query()
                ->where('room_id', $locked->room_id)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('check_in', '<', $locked->check_out)
                ->whereDate('check_out', '>', $locked->check_in)
                ->where(function (Builder $query): void {
                    $query->where('status', 'confirmed')
                        ->orWhereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get(['room_numbers'])
                ->flatMap(fn (Reservation $item): array => $item->room_numbers ?? [])
                ->map(fn ($number): int => (int) $number)
                ->intersect(array_map('intval', $locked->room_numbers ?? []))
                ->isNotEmpty();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'decision' => 'Kamar sudah dipesan pihak lain. Batalkan reservasi ini dan hubungi tamu terkait.',
                ]);
            }

            $locked->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'voucher_code' => $locked->voucher_code ?? Str::upper(Str::random(12)),
            ]);
        });

        return redirect()->route('admin.payment-reviews.index')
            ->with('status', $validated['decision'] === 'approve'
                ? 'Pembayaran disetujui dan reservasi dikonfirmasi.'
                : 'Reservasi dibatalkan.');
    }

    public function destroy(Request $request, Reservation $reservation): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAdmin($request);

        $proofPath = DB::transaction(function () use ($reservation): ?string {
            $locked = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
            $paymentProof = $locked->payment_proof;
            $locked->delete();

            return $paymentProof;
        });

        if ($proofPath) {
            try {
                $disk = Storage::build([
                    'driver' => 'local',
                    'root' => config('payments.proof_path'),
                    'throw' => true,
                ]);

                if (! $disk->delete($proofPath)) {
                    Log::warning('Could not remove payment proof for deleted admin reservation.', [
                        'reservation_id' => $reservation->id,
                        'proof_path' => $proofPath,
                    ]);

                    return redirect()->route('admin.payment-reviews.index')
                        ->with('warning', 'Pesanan dihapus, tetapi file bukti pembayaran tidak berhasil dibersihkan.');
                }
            } catch (\Throwable $exception) {
                Log::error('Could not remove payment proof for deleted admin reservation.', [
                    'reservation_id' => $reservation->id,
                    'proof_path' => $proofPath,
                    'exception' => $exception,
                ]);

                return redirect()->route('admin.payment-reviews.index')
                    ->with('warning', 'Pesanan dihapus, tetapi file bukti pembayaran tidak berhasil dibersihkan.');
            }
        }

        return redirect()->route('admin.payment-reviews.index')
            ->with('status', 'Pesanan berhasil dihapus.');
    }

    private function authorizeOwner(Request $request, Reservation $reservation): void
    {
        abort_unless((int) $reservation->user_id === (int) $request->user()->id, 403);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
