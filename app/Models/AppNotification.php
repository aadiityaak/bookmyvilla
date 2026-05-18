<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    protected function isUnread(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->read_at === null,
        );
    }
}
