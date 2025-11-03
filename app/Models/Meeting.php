<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'status',
        'external_contact_email',
        'external_contact_name',
        'google_event_id',
        'google_synced_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'google_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function hasExternalContact(): bool
    {
        return ! empty($this->external_contact_email);
    }

    public function shouldSyncToGoogle(): bool
    {
        return $this->isConfirmed() && $this->hasExternalContact();
    }

    public function markSynced(?string $eventId): void
    {
        $this->forceFill([
            'google_event_id' => $eventId,
            'google_synced_at' => Carbon::now(),
        ])->save();
    }
}
