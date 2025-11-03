<?php

namespace App\Volt\Meetings;

use App\Models\Meeting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Volt\Component;

abstract class Agenda extends Component
{
    public ?string $selectedDate = null;

    public ?string $selectedCategory = null;

    /**
     * Define the data available to the view.
     */
    public function with(): array
    {
        return [
            'calendar' => $this->calendar,
            'displayedDays' => $this->displayedDays,
            'categories' => $this->categories,
            'currentDate' => $this->currentDate,
            'isFiltered' => $this->isFiltered,
        ];
    }

    /**
     * Set defaults when the component boots.
     */
    public function mount(): void
    {
        $this->selectedDate ??= CarbonImmutable::now()->toDateString();
    }

    /**
     * Normalize the selected date whenever it is updated.
     */
    public function updatedSelectedDate(?string $value): void
    {
        $this->selectedDate = $this->normalizeDate($value);
    }

    /**
     * Normalize the selected category whenever it is updated.
     */
    public function updatedSelectedCategory(?string $value): void
    {
        $this->selectedCategory = $value ?: null;
    }

    /**
     * Move the calendar view to the previous week.
     */
    public function previousWeek(): void
    {
        $this->selectedDate = $this->currentDate->subWeek()->toDateString();
    }

    /**
     * Move the calendar view to the next week.
     */
    public function nextWeek(): void
    {
        $this->selectedDate = $this->currentDate->addWeek()->toDateString();
    }

    /**
     * Reset the calendar view to today.
     */
    public function goToToday(): void
    {
        $this->selectedDate = CarbonImmutable::now()->toDateString();
    }

    /**
     * Determine if the current filters are active.
     */
    public function getIsFilteredProperty(): bool
    {
        return filled($this->selectedCategory) || $this->selectedDate !== CarbonImmutable::now()->toDateString();
    }

    /**
     * Retrieve the meetings grouped by date for the displayed period.
     */
    public function getCalendarProperty(): SupportCollection
    {
        $days = $this->displayedDays;

        if ($days->isEmpty()) {
            return collect();
        }

        $rangeStart = $days->first()->toDateString();
        $rangeEnd = $days->last()->toDateString();

        $meetings = Meeting::query()
            ->with('responsibles')
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->when($this->selectedCategory, fn ($query) => $query->where('category', $this->selectedCategory))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return $meetings->groupBy(fn (Meeting $meeting) => $meeting->date?->toDateString());
    }

    /**
     * Retrieve the available calendar days for the current week.
     */
    public function getDisplayedDaysProperty(): SupportCollection
    {
        $startOfWeek = $this->currentDate->startOfWeek(CarbonInterface::MONDAY);

        return collect(range(0, 6))->map(
            fn (int $offset) => $startOfWeek->addDays($offset)
        );
    }

    /**
     * Distinct meeting categories available for filtering.
     */
    public function getCategoriesProperty(): SupportCollection
    {
        return Meeting::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Meetings for a specific day from the computed calendar.
     */
    public function meetingsFor(string $date): SupportCollection
    {
        return $this->calendar->get($date, collect());
    }

    /**
     * Get the currently selected date as an immutable Carbon instance.
     */
    public function getCurrentDateProperty(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->selectedDate ?? CarbonImmutable::now()->toDateString());
    }

    /**
     * Normalize input date values.
     */
    protected function normalizeDate(?string $value): string
    {
        if (! $value) {
            return CarbonImmutable::now()->toDateString();
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable $exception) {
            return CarbonImmutable::now()->toDateString();
        }
    }
}
