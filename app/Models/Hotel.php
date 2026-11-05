<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hotel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'city',
        'province',
        'description',
        'image',
        'rating',
        'address',
        'maps_url',
        'latitude',
        'longitude',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_hotel')
            ->where('scope', 'public')
            ->orderBy('sort_order');
    }

    public function cancellationPolicies(): HasMany
    {
        return $this->hasMany(CancellationPolicy::class)->orderBy('sort_order');
    }

    public function getAverageRatingAttribute(): ?float
    {
        $average = $this->attributes['reviews_avg_rating'] ?? null;

        if ($average === null && $this->relationLoaded('reviews')) {
            $average = $this->reviews->avg('rating');
        }

        if ($average === null) {
            $average = $this->reviews()->avg('rating');
        }

        return $average === null ? null : round((float) $average, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        return $this->relationLoaded('reviews')
            ? $this->reviews->count()
            : $this->reviews()->count();
    }

    public function scopeWithReviewSummary(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')->withCount('reviews');

        foreach (range(1, 5) as $rating) {
            $query->withCount([
                'reviews as rating_'.$rating.'_count' => fn (Builder $reviews): Builder => $reviews
                    ->where('rating', $rating),
            ]);
        }
    }
}
