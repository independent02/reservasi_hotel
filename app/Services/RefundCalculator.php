<?php

namespace App\Services;

use App\Models\CancellationPolicy;
use App\Models\Reservation;
use Carbon\Carbon;
use RuntimeException;

class RefundCalculator
{
    /**
     * @return array{policy: CancellationPolicy, refund_percent: int, refund_amount: float}
     */
    public function calculate(Reservation $reservation): array
    {
        $checkIn = Carbon::parse(
            $reservation->check_in->format('Y-m-d').' '.($reservation->check_in_time ?? '14:00:00'),
        );
        $hoursUntilCheckIn = max(0, now()->diffInMinutes($checkIn, false) / 60);
        $hotelId = $reservation->hotel_id;
        $hotelPolicies = CancellationPolicy::query()
            ->where('hotel_id', $hotelId)
            ->orderByDesc('min_hours')
            ->get();
        $policies = $hotelPolicies->isNotEmpty()
            ? $hotelPolicies
            : CancellationPolicy::query()
                ->whereNull('hotel_id')
                ->orderByDesc('min_hours')
                ->get();

        $policy = $policies->first(function (CancellationPolicy $policy) use ($hoursUntilCheckIn): bool {
            if ($policy->max_hours === null) {
                return $hoursUntilCheckIn > $policy->min_hours;
            }

            if ($policy->min_hours === 0) {
                return $hoursUntilCheckIn < $policy->max_hours;
            }

            return $hoursUntilCheckIn >= $policy->min_hours
                && $hoursUntilCheckIn <= $policy->max_hours;
        });

        if (! $policy) {
            throw new RuntimeException('Kebijakan pembatalan belum dikonfigurasi untuk hotel ini.');
        }

        $refundPercent = $policy->refund_percent;

        return [
            'policy' => $policy,
            'refund_percent' => $refundPercent,
            'refund_amount' => round((float) $reservation->total_price * $refundPercent / 100, 2),
        ];
    }
}
