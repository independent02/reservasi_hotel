<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\RefundCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingCancellationController
{
    public function destroy(
        Request $request,
        Reservation $reservation,
        RefundCalculator $refundCalculator,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'in:Rencana perjalanan berubah,Salah memilih kamar atau tanggal,Ingin mengganti metode pembayaran,Alasan lain',
            ],
            'reason_detail' => ['required_if:reason,Alasan lain', 'nullable', 'string', 'max:1000'],
            'ack' => ['required', 'accepted'],
        ]);
        $cancelReason = $validated['reason'] === 'Alasan lain'
            ? 'Alasan lain: '.$validated['reason_detail']
            : $validated['reason'];

        $result = DB::transaction(function () use ($request, $reservation, $refundCalculator, $validated, $cancelReason): array {
            $lockedReservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            abort_unless($lockedReservation->user_id === $request->user()->id, 403);
            $isExpiredUnpaid = $lockedReservation->status === 'pending'
                && $lockedReservation->payment_status === 'pending'
                && $lockedReservation->expires_at?->isPast()
                && blank($lockedReservation->payment_proof);
            abort_unless(
                in_array($lockedReservation->status, ['pending', 'confirmed', 'checked_in'], true)
                    && $lockedReservation->check_out->greaterThanOrEqualTo(today())
                    && ! $isExpiredUnpaid,
                403,
                'Reservasi ini tidak dapat dibatalkan.',
            );

            $refund = $refundCalculator->calculate($lockedReservation);
            $lockedReservation->forceFill([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $cancelReason,
                'refund_percent' => $refund['refund_percent'],
                'refund_amount' => $refund['refund_amount'],
            ])->save();

            return $refund;
        });

        return redirect()
            ->route('reservations.index', ['tab' => 'cancelled'])
            ->with(
                'status',
                'Pembatalan tercatat. Estimasi refund '.$result['refund_percent'].'% (Rp'
                    .number_format($result['refund_amount'], 0, ',', '.')
                    .') dicatat untuk ditinjau; transfer refund tidak diproses otomatis.',
            );
    }
}
