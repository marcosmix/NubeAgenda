<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meeting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'agenda',
        'reason',
        'location',
        'starts_at',
        'ends_at',
        'visibility',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Meeting creator relationship.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Meeting responsibles relationship.
     */
    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_user')->withTimestamps();
    }

    /**
     * Meeting external contacts relationship.
     */
    public function externalContacts(): BelongsToMany
    {
        return $this->belongsToMany(ExternalContact::class, 'external_contact_meeting')->withTimestamps();
    }
}
