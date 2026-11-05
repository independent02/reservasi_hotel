<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\CancellationPolicy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController
{
    public function show(Request $request, Room $room): View
    {
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
        $filters = array_replace([
            'checkin_time' => '14:00',
            'checkout_time' => '12:00',
        ], $filters);

        $room->load([
            'facilities' => fn ($query) => $query->where('is_active', true),
            'hotel' => fn ($query) => $query
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->with([
                    'facilities' => fn ($facilities) => $facilities->where('is_active', true),
                ]),
        ]);

        abort_unless($room->is_active && $room->hotel->is_active, 404);

        $policies = $room->hotel->cancellationPolicies;
        if ($policies->isEmpty()) {
            $policies = CancellationPolicy::query()
                ->whereNull('hotel_id')
                ->orderBy('sort_order')
                ->get();
        }

        $activeReservation = $request->user()?->reservations()
            ->where('room_id', $room->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->whereDate('check_out', '>=', today())
            ->where(function ($query): void {
                $query->where('status', '!=', 'pending')
                    ->orWhereNull('expires_at')
                    ->orWhere('expires_at', '>', now())
                    ->orWhereNotNull('payment_proof');
            })
            ->orderBy('check_in')
            ->first();

        return view('rooms.show', [
            'room' => $room,
            'filters' => $filters,
            'policies' => $policies,
            'activeReservation' => $activeReservation,
        ]);
    }
}
