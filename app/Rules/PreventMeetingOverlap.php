<?php

namespace App\Rules;

use App\Models\Meeting;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class PreventMeetingOverlap implements DataAwareRule, ValidationRule
{
    /**
     * The data under validation.
     */
    protected array $data = [];

    /**
     * Set the data under validation.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validate the rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startsAt = data_get($this->data, 'startsAt', data_get($this->data, 'starts_at'));
        $endsAt = data_get($this->data, 'endsAt', data_get($this->data, 'ends_at'));

        if (! $startsAt || ! $endsAt) {
            return;
        }

        $start = Carbon::parse($startsAt);
        $end = Carbon::parse($endsAt);

        if ($end->lessThanOrEqualTo($start)) {
            $fail(__('The meeting end time must be after the start time.'));

            return;
        }

        $responsibleIds = Arr::wrap(data_get($this->data, 'responsibleIds', data_get($this->data, 'responsible_ids', [])));
        $location = data_get($this->data, 'location');

        if (blank($location) && blank($responsibleIds)) {
            return;
        }

        $overlapping = Meeting::query()
            ->where(function ($query) use ($location, $responsibleIds) {
                $conditionsApplied = false;

                if (filled($location)) {
                    $query->where('location', $location);
                    $conditionsApplied = true;
                }

                if (! blank($responsibleIds)) {
                    $method = $conditionsApplied ? 'orWhereHas' : 'whereHas';

                    $query->{$method}('responsibles', fn ($q) => $q->whereIn('users.id', $responsibleIds));
                }
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(function ($range) use ($start, $end) {
                        $range->where('starts_at', '<=', $start)
                            ->where('ends_at', '>=', $end);
                    });
            })
            ->exists();

        if ($overlapping) {
            $fail(__('There is already a meeting scheduled for the selected time with the same location or responsible.'));
        }
    }
}
