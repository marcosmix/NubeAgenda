<?php

namespace App\Volt\Contacts;

use App\Models\Meeting;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

class ContactList extends Component
{
    public ?int $meetingId = null;

    public function mount(): void
    {
        $this->meetingId = $this->meetings->first()?->id;
    }

    #[Computed]
    public function meetings(): Collection
    {
        return Meeting::with(['externalContacts' => fn ($query) => $query->orderBy('name')])
            ->orderBy('scheduled_at')
            ->orderBy('title')
            ->get();
    }

    #[Computed]
    public function contacts(): Collection
    {
        if (! $this->meetingId) {
            return collect();
        }

        $meeting = $this->meetings->firstWhere('id', $this->meetingId);

        return $meeting?->externalContacts ?? collect();
    }

    #[On('contact-created')]
    public function refreshContacts(): void
    {
        // Refresh computed properties by triggering a re-render.
    }

    public function template(): string
    {
        return <<<'BLADE'
<x-layouts.app :title="__('Contactos externos')">
    <div class="flex flex-col gap-6">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Contactos externos') }}
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Administra los contactos invitados a tus reuniones.') }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <livewire:contacts.contact-form :meeting-id="$meetingId" />
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ __('Contactos') }}
                        </h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Selecciona una reunión para ver los contactos asociados.') }}
                        </p>
                    </div>
                    <div class="w-full sm:w-60">
                        <label for="meeting" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Reunión') }}
                        </label>
                        <select
                            id="meeting"
                            wire:model.live="meetingId"
                            class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            @forelse ($meetings as $meeting)
                                <option value="{{ $meeting->id }}">{{ $meeting->title }}</option>
                            @empty
                                <option value="">
                                    {{ __('No hay reuniones registradas') }}
                                </option>
                            @endforelse
                        </select>
                    </div>
                </div>

                <div class="mt-6" wire:key="contacts-{{ $meetingId }}">
                    @if ($contacts->isEmpty())
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Aún no hay contactos registrados para esta reunión.') }}
                        </p>
                    @else
                        <ul class="space-y-4">
                            @foreach ($contacts as $contact)
                                <li class="flex items-start gap-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <img
                                        class="size-16 rounded-full object-cover"
                                        src="{{ $contact->photo_url }}"
                                        alt="{{ $contact->name }}"
                                    >
                                    <div class="flex-1 space-y-1">
                                        <p class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $contact->name }}
                                        </p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $contact->position }} · {{ $contact->company }}
                                        </p>
                                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $contact->email }} · {{ $contact->phone }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
BLADE;
    }
}
