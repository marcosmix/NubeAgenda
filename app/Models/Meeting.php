<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'scheduled_at',
        'location',
        'description',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function externalContacts(): HasMany
    {
        return $this->hasMany(ExternalContact::class);
    }
}
