<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Str;

class RoomVoucherBenefits
{
    public function forRoom(Room $room): array
    {
        $room->loadMissing('hotel.facilities');

        $configuredBenefits = config(
            'room_voucher_benefits.'.$room->hotel->slug.'.'.Str::slug($room->name),
            [],
        );

        if ($configuredBenefits === []) {
            return [];
        }

        $specialFacilities = $room->hotel->facilities
            ->filter(fn ($facility): bool => filled($facility->badge))
            ->map(fn ($facility): array => [
                'title' => $facility->name,
                'description' => $facility->description,
                'badge' => $facility->badge,
            ])
            ->values()
            ->all();

        return $specialFacilities !== [] ? $specialFacilities : $configuredBenefits;
    }
}
