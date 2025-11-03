<?php

use App\Volt\Meetings\Agenda as AgendaComponent;
use Illuminate\Support\Str;

new class extends AgendaComponent {
}; ?>

<x-layouts.app :title="__('Agenda de reuniones')">
    <div class="space-y-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                {{ __('Agenda de reuniones') }}
            </h1>
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ __('Organiza y filtra tus reuniones por fecha de referencia y categoría.') }}
            </p>
        </div>

        <div class="space-y-6 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="grid w-full gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model.live="selectedDate"
                        type="date"
                        :label="__('Fecha de referencia')"
                    />

                    <div class="w-full">
                        <label for="category-filter" class="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            {{ __('Categoría') }}
                        </label>
                        <select
                            id="category-filter"
                            wire:model.live="selectedCategory"
                            class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                        >
                            <option value="">{{ __('Todas las categorías') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ Str::of($category)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <flux:button type="button" variant="ghost" wire:click="goToToday" class="w-full sm:w-auto">
                        {{ __('Hoy') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="previousWeek" class="inline-flex sm:hidden">
                        {{ __('Anterior') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="nextWeek" class="inline-flex sm:hidden">
                        {{ __('Siguiente') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="previousWeek" class="hidden sm:inline-flex">
                        {{ __('Semana anterior') }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="nextWeek" class="hidden sm:inline-flex">
                        {{ __('Semana siguiente') }}
                    </flux:button>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        {{ __('Semana del :date', ['date' => $currentDate->translatedFormat('d \d\e F, Y')]) }}
                    </h2>
                    <p class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Visualiza las reuniones planificadas para la semana seleccionada.') }}
                    </p>
                </div>

                @if ($isFiltered)
                    <span class="inline-flex items-center justify-center rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-500/20 dark:text-primary-200">
                        {{ __('Filtros activos') }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-7">
                @foreach ($displayedDays as $day)
                    @php
                        $dateKey = $day->toDateString();
                        $isToday = $dateKey === now()->toDateString();
                        $meetings = $this->meetingsFor($dateKey);
                    @endphp

                    <div class="flex flex-col gap-3 rounded-lg border border-neutral-200 bg-neutral-50 p-4 transition hover:border-primary-400 dark:border-neutral-700 dark:bg-neutral-900/60 dark:hover:border-primary-400">
                        <div class="flex items-baseline justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                                    {{ $day->translatedFormat('l') }}
                                </p>
                                <p class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">
                                    {{ $day->format('d') }}
                                </p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $day->translatedFormat('F Y') }}
                                </p>
                            </div>

                            @if ($isToday)
                                <span class="rounded-full bg-primary-100 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/20 dark:text-primary-200">
                                    {{ __('Hoy') }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3">
                            @forelse ($meetings as $meeting)
                                <article class="flex flex-col gap-2 rounded-lg border border-neutral-200 bg-white p-3 shadow-sm dark:border-neutral-700 dark:bg-neutral-950">
                                    <header class="flex flex-wrap items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                            {{ $meeting->title }}
                                        </h3>
                                        <span class="rounded-full bg-primary-100 px-2 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
                                            {{ Str::of($meeting->category)->headline() }}
                                        </span>
                                    </header>

                                    <p class="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                        {{ optional($meeting->start_time)->format('H:i') }} - {{ optional($meeting->end_time)->format('H:i') }}
                                    </p>

                                    <p class="text-xs text-neutral-600 dark:text-neutral-400">
                                        {{ __('Responsables: :names', ['names' => $meeting->responsibles->pluck('name')->join(', ') ?: __('No asignados')]) }}
                                    </p>

                                    @if ($meeting->visibility)
                                        <p class="text-[11px] uppercase tracking-wide text-neutral-500 dark:text-neutral-400">
                                            {{ __('Visibilidad: :visibility', ['visibility' => Str::of($meeting->visibility)->headline()]) }}
                                        </p>
                                    @endif
                                </article>
                            @empty
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ __('Sin reuniones para este día.') }}
                                </p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($calendar->flatten()->isEmpty())
                <div class="rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-center text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-400">
                    {{ __('No se encontraron reuniones para los filtros seleccionados.') }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
