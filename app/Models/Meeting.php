<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'date',
        'start_time',
        'end_time',
        'category',
        'reason_id',
        'visibility',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'immutable_date',
    ];

    /**
     * Accessor for the meeting start time.
     */
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?CarbonImmutable => $value
                ? CarbonImmutable::createFromFormat('H:i:s', $value)
                : null,
        );
    }

    /**
     * Accessor for the meeting end time.
     */
    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?CarbonImmutable => $value
                ? CarbonImmutable::createFromFormat('H:i:s', $value)
                : null,
        );
    }

    /**
     * Users responsible for the meeting.
     */
    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
