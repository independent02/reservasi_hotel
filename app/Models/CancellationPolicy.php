<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    protected $fillable = [
        'hotel_id',
        'title',
        'description',
        'min_hours',
        'max_hours',
        'refund_percent',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_hours' => 'integer',
            'max_hours' => 'integer',
            'refund_percent' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
