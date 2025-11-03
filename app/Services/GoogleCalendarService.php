<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarService
{
    public function syncMeeting(Meeting $meeting): void
    {
        if (! $meeting->shouldSyncToGoogle()) {
            return;
        }

        $user = $this->resolveUser($meeting);

        if (! $user || ! $user->prefersGoogleCalendarSync()) {
            return;
        }

        if (! $this->ensureValidAccessToken($user)) {
            return;
        }

        $payload = $this->buildEventPayload($meeting);
        $calendarId = $this->resolveCalendarId($user);

        try {
            if ($meeting->google_event_id) {
                $this->authenticatedRequest($user)
                    ->patch($this->eventEndpoint($calendarId, $meeting->google_event_id), $payload)
                    ->throw();
            } else {
                $response = $this->authenticatedRequest($user)
                    ->post($this->eventsEndpoint($calendarId), $payload)
                    ->throw()
                    ->json();

                $meeting->google_event_id = $response['id'] ?? $meeting->google_event_id;
            }

            $this->persistMeetingChanges($meeting, [
                'google_event_id' => $meeting->google_event_id,
                'google_synced_at' => Carbon::now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to sync meeting to Google Calendar.', [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'exception' => $exception,
            ]);
        }
    }

    public function deleteMeeting(Meeting $meeting): void
    {
        if (empty($meeting->google_event_id)) {
            $this->persistMeetingChanges($meeting, [
                'google_event_id' => null,
                'google_synced_at' => null,
            ]);

            return;
        }

        $user = $this->resolveUser($meeting);

        if (! $user || ! $user->prefersGoogleCalendarSync()) {
            return;
        }

        if (! $this->ensureValidAccessToken($user)) {
            return;
        }

        $calendarId = $this->resolveCalendarId($user);

        try {
            $this->authenticatedRequest($user)
                ->delete($this->eventEndpoint($calendarId, $meeting->google_event_id))
                ->throw();
        } catch (Throwable $exception) {
            Log::warning('Failed to remove Google Calendar event.', [
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'exception' => $exception,
            ]);
            return;
        }

        $this->persistMeetingChanges($meeting, [
            'google_event_id' => null,
            'google_synced_at' => null,
        ]);
    }

    protected function ensureValidAccessToken(User $user): bool
    {
        if ($user->hasValidGoogleAccessToken()) {
            return true;
        }

        if (empty($user->google_refresh_token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'refresh_token' => $user->google_refresh_token,
                    'grant_type' => 'refresh_token',
                ])
                ->throw()
                ->json();
        } catch (Throwable $exception) {
            Log::error('Failed to refresh Google access token.', [
                'user_id' => $user->id,
                'exception' => $exception,
            ]);

            return false;
        }

        $user->forceFill([
            'google_access_token' => Arr::get($response, 'access_token'),
            'google_token_expires_at' => Carbon::now()->addSeconds(Arr::get($response, 'expires_in', 3600)),
        ])->save();

        return $user->hasValidGoogleAccessToken();
    }

    protected function buildEventPayload(Meeting $meeting): array
    {
        $timezone = config('app.timezone');

        $payload = [
            'summary' => $meeting->title,
            'description' => $meeting->description,
            'location' => $meeting->location,
            'start' => [
                'dateTime' => $meeting->start_at?->copy()->timezone($timezone)->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $meeting->end_at?->copy()->timezone($timezone)->toRfc3339String(),
                'timeZone' => $timezone,
            ],
            'status' => 'confirmed',
            'attendees' => array_values(array_filter([
                $meeting->hasExternalContact() ? ['email' => $meeting->external_contact_email, 'displayName' => $meeting->external_contact_name] : null,
            ])),
        ];

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    protected function resolveCalendarId(User $user): string
    {
        return $user->google_calendar_id ?: (string) config('services.google.calendar_id', 'primary');
    }

    protected function authenticatedRequest(User $user): PendingRequest
    {
        return Http::withToken($user->google_access_token)
            ->acceptJson();
    }

    protected function eventsEndpoint(string $calendarId): string
    {
        return sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events', urlencode($calendarId));
    }

    protected function eventEndpoint(string $calendarId, string $eventId): string
    {
        return sprintf('%s/%s', $this->eventsEndpoint($calendarId), urlencode($eventId));
    }

    protected function resolveUser(Meeting $meeting): ?User
    {
        if ($meeting->relationLoaded('user')) {
            return $meeting->getRelation('user');
        }

        return $meeting->user;
    }

    protected function persistMeetingChanges(Meeting $meeting, array $attributes): void
    {
        if (! $meeting->exists) {
            return;
        }

        $meeting->forceFill($attributes)->save();
    }
}
