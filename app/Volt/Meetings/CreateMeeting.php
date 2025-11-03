<?php

namespace App\Volt\Meetings;

use App\Models\ExternalContact;
use App\Models\Meeting;
use App\Models\User;
use App\Rules\PreventMeetingOverlap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

class CreateMeeting extends Component
{
    public string $title = '';

    public string $agenda = '';

    public string $reason = '';

    public ?string $location = null;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $visibility = 'private';

    public array $responsibleIds = [];

    public array $selectedContactIds = [];

    public string $contactSearch = '';

    public ?string $successMessage = null;

    /**
     * Bootstrap component state.
     */
    public function mount(): void
    {
        $this->initializeDefaults();
    }

    /**
     * Validation rules for the component.
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'reason' => ['required', 'string', Rule::in(array_keys($this->reasonOptions))],
            'location' => ['nullable', 'string', 'max:255'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', new PreventMeetingOverlap()],
            'visibility' => ['required', 'string', Rule::in(array_keys($this->visibilityOptions))],
            'responsibleIds' => ['required', 'array', 'min:1'],
            'responsibleIds.*' => ['integer', 'exists:users,id'],
            'selectedContactIds' => ['array'],
            'selectedContactIds.*' => ['integer', 'exists:external_contacts,id'],
        ];
    }

    /**
     * Validation messages customization.
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('title'),
            'agenda' => __('agenda'),
            'reason' => __('reason'),
            'location' => __('location'),
            'startsAt' => __('start time'),
            'endsAt' => __('end time'),
            'visibility' => __('visibility'),
            'responsibleIds' => __('responsibles'),
            'selectedContactIds' => __('external contacts'),
        ];
    }

    /**
     * Computed collection of users.
     */
    public function getUsersProperty(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    /**
     * Meeting reasons.
     */
    public function getReasonOptionsProperty(): array
    {
        return [
            'follow_up' => __('Seguimiento de proyecto'),
            'kickoff' => __('Inicio de proyecto'),
            'presentation' => __('Presentación'),
            'training' => __('Capacitación'),
            'other' => __('Otro'),
        ];
    }

    /**
     * Meeting visibility options.
     */
    public function getVisibilityOptionsProperty(): array
    {
        return [
            'private' => __('Privada (solo participantes)'),
            'team' => __('Equipo (usuarios internos)'),
            'organization' => __('Organización completa'),
        ];
    }

    /**
     * Selected contacts with full detail.
     */
    public function getSelectedContactsProperty(): Collection
    {
        if (blank($this->selectedContactIds)) {
            return collect();
        }

        return ExternalContact::query()
            ->whereIn('id', $this->selectedContactIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Suggestions for the autocomplete contact field.
     */
    public function getContactSuggestionsProperty(): Collection
    {
        $query = ExternalContact::query();

        if (filled($this->contactSearch)) {
            $term = $this->contactSearch;

            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('organization', 'like', "%{$term}%");
            });
        }

        if (! empty($this->selectedContactIds)) {
            $query->whereNotIn('id', $this->selectedContactIds);
        }

        return $query->orderBy('name')->limit(5)->get();
    }

    /**
     * Select a contact from the autocomplete suggestion list.
     */
    public function selectContact(int $contactId): void
    {
        if (! ExternalContact::whereKey($contactId)->exists()) {
            return;
        }

        if (! in_array($contactId, $this->selectedContactIds, true)) {
            $this->selectedContactIds[] = $contactId;
        }

        $this->contactSearch = '';
    }

    /**
     * Remove a selected contact.
     */
    public function removeContact(int $contactId): void
    {
        $this->selectedContactIds = array_values(array_filter(
            $this->selectedContactIds,
            fn (int $id) => $id !== $contactId,
        ));
    }

    /**
     * Persist the meeting.
     */
    public function createMeeting(): void
    {
        $validated = $this->validate();

        $meeting = Meeting::query()->create([
            'title' => $validated['title'],
            'agenda' => blank($validated['agenda'] ?? null) ? null : $validated['agenda'],
            'reason' => $validated['reason'],
            'location' => blank($validated['location'] ?? null) ? null : $validated['location'],
            'starts_at' => Carbon::parse($validated['startsAt'])->timezone(config('app.timezone')),
            'ends_at' => Carbon::parse($validated['endsAt'])->timezone(config('app.timezone')),
            'visibility' => $validated['visibility'],
            'created_by' => Auth::id(),
        ]);

        $responsibles = collect($validated['responsibleIds'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (! $responsibles->contains(Auth::id())) {
            $responsibles->push(Auth::id());
        }

        $meeting->responsibles()->sync($responsibles->all());

        $meeting->externalContacts()->sync(
            collect($validated['selectedContactIds'] ?? [])->map(fn ($id) => (int) $id)->unique()->all()
        );

        $this->successMessage = __('Meeting created successfully.');

        $this->initializeDefaults();
        $this->resetValidation();
    }

    /**
     * Reset form defaults.
     */
    protected function initializeDefaults(): void
    {
        $start = now()->addHour()->setMinute(0)->setSecond(0);
        $end = (clone $start)->addHour();

        $this->startsAt = $start->format('Y-m-d\TH:i');
        $this->endsAt = $end->format('Y-m-d\TH:i');
        $this->visibility = 'private';
        $this->responsibleIds = Auth::check() ? [Auth::id()] : [];
        $this->selectedContactIds = [];
        $this->contactSearch = '';
        $this->title = '';
        $this->agenda = '';
        $this->reason = '';
        $this->location = null;
    }
}
