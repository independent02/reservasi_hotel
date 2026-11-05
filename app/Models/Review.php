<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'hotel_id',
        'reservation_id',
        'rating',
        'cleanliness',
        'comfort',
        'location',
        'service',
        'tags',
        'comment',
        'media',
        'hotel_response',
        'hotel_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'cleanliness' => 'integer',
            'comfort' => 'integer',
            'location' => 'integer',
            'service' => 'integer',
            'tags' => 'array',
            'media' => 'array',
            'hotel_responded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
