<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $fillable = [
        'hotel_id',
        'room_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'identity_type',
        'identity_number',
        'special_request',
        'check_in',
        'check_in_time',
        'check_out',
        'check_out_time',
        'guest_count',
        'room_count',
        'total_price',
        'status',
        'room_numbers',
        'payment_status',
        'payment_method',
        'payment_proof',
        'destination_bank',
        'bank_name',
        'account_name',
        'payment_submitted_at',
        'expires_at',
        'voucher_code',
        'paid_at',
        'cancelled_at',
        'cancel_reason',
        'refund_percent',
        'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'total_price' => 'decimal:2',
            'room_numbers' => 'array',
            'identity_number' => 'encrypted',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_percent' => 'integer',
            'refund_amount' => 'decimal:2',
            'payment_submitted_at' => 'datetime',
        ];
    }

    public function hasCheckedOut(): bool
    {
        return $this->checkOutAt()->lessThanOrEqualTo(now());
    }

    public function checkOutAt(): Carbon
    {
        return Carbon::parse(
            $this->check_out->format('Y-m-d').' '.($this->check_out_time ?? '12:00:00'),
        );
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
