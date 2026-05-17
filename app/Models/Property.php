<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'investor_id',
    'type',
    'name',
    'featured_image',
    'address',
    'province_id',
    'regency_id',
    'latitude',
    'longitude',
    'description',
    'amenities',
    'status',
    'gallery',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
