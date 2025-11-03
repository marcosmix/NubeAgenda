<?php

namespace App\Observers;

use App\Jobs\SyncMeetingToGoogle;
use App\Models\Meeting;

class MeetingObserver
{
    /**
     * @var list<string>
     */
    protected array $syncableAttributes = [
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'external_contact_email',
        'external_contact_name',
    ];

    public function created(Meeting $meeting): void
    {
        if ($meeting->shouldSyncToGoogle()) {
            SyncMeetingToGoogle::dispatch($meeting);
        }
    }

    public function updated(Meeting $meeting): void
    {
        $wasConfirmed = $meeting->getOriginal('status') === Meeting::STATUS_CONFIRMED;
        $isConfirmed = $meeting->isConfirmed();
        $externalRemoved = ! empty($meeting->getOriginal('external_contact_email')) && empty($meeting->external_contact_email);
        $statusNoLongerConfirmed = $wasConfirmed && ! $isConfirmed;

        if ($statusNoLongerConfirmed || $externalRemoved) {
            SyncMeetingToGoogle::dispatch($meeting, SyncMeetingToGoogle::ACTION_DELETE);

            return;
        }

        if (! $meeting->shouldSyncToGoogle()) {
            return;
        }

        $statusJustConfirmed = ! $wasConfirmed && $isConfirmed;
        $detailsChanged = $meeting->wasChanged($this->syncableAttributes);
        $externalAdded = empty($meeting->getOriginal('external_contact_email')) && ! empty($meeting->external_contact_email);

        if ($statusJustConfirmed || $detailsChanged || $externalAdded) {
            SyncMeetingToGoogle::dispatch($meeting);
        }
    }

    public function deleted(Meeting $meeting): void
    {
        SyncMeetingToGoogle::dispatch($meeting, SyncMeetingToGoogle::ACTION_DELETE);
    }
}
