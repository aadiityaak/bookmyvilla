<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'property_id',
    'guest_user_id',
    'check_in_date',
    'check_out_date',
    'guests_count',
    'status',
    'total_amount',
    'currency',
    'price_snapshot',
])]
class Booking extends Model
{
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'price_snapshot' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }
}

