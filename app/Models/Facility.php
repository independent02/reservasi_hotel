<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    protected $fillable = [
        'name',
        'description',
        'icon',
        'scope',
        'badge',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'facility_room');
    }

    public function hotels(): BelongsToMany
    {
        return $this->belongsToMany(Hotel::class, 'facility_hotel');
    }
}
