<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_id',
    'investor_id',
    'type',
    'name',
    'featured_image',
    'address',
    'description',
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

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
        ];
    }
}
