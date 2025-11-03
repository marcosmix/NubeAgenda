<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExternalContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'name',
        'email',
        'phone',
        'position',
        'company',
        'photo_path',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->photo_path);
    }
}
