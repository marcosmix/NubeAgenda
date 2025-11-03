<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SyncMeetingToGoogle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ACTION_SYNC = 'sync';
    public const ACTION_DELETE = 'delete';

    public ?Meeting $meeting;

    protected array $payload;

    protected int $userId;

    protected string $action;

    public function __construct(Meeting $meeting, string $action = self::ACTION_SYNC)
    {
        $this->meeting = $meeting;
        $this->payload = $meeting->withoutRelations()->toArray();
        $this->userId = (int) $meeting->user_id;
        $this->action = $action;

        $this->onQueue(config('services.google.queue', 'default'));
    }

    public function handle(GoogleCalendarService $calendar): void
    {
        $meeting = $this->resolveMeeting();

        if (! $meeting) {
            return;
        }

        if ($this->action === self::ACTION_DELETE) {
            $calendar->deleteMeeting($meeting);

            return;
        }

        $calendar->syncMeeting($meeting);
    }

    protected function resolveMeeting(): ?Meeting
    {
        $meeting = $this->meeting;

        if ($meeting instanceof Meeting) {
            $meeting->loadMissing('user');

            if ($meeting->user) {
                return $meeting;
            }
        }

        $meetingId = Arr::get($this->payload, 'id');

        if ($meetingId) {
            $meeting = Meeting::find($meetingId);

            if ($meeting) {
                return $meeting->loadMissing('user');
            }
        }

        $meeting = Meeting::make($this->payload);

        $user = User::find($this->userId);

        if (! $user) {
            return null;
        }

        $meeting->setRelation('user', $user);

        return $meeting;
    }
}
