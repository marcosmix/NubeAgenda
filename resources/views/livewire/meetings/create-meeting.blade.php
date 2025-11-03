<x-layouts.app :title="__('Programar reunión')">
    <section class="mx-auto w-full max-w-4xl space-y-6 py-6">
        <header class="space-y-2">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ __('Programar una reunión') }}
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Completa la información necesaria para agendar una nueva reunión y compartirla con las personas responsables.') }}
            </p>
        </header>

        @if ($successMessage)
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-900/60 dark:bg-green-900/40 dark:text-green-100">
                {{ $successMessage }}
            </div>
        @endif

        <form wire:submit="createMeeting" class="space-y-8">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <flux:input wire:model.live="title" :label="__('Título de la reunión')" required />
                    @error('title')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Motivo') }}
                    </label>
                    <select wire:model="reason" class="w-full rounded-lg border border-zinc-300 bg-white p-2 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                        <option value="">{{ __('Selecciona un motivo') }}</option>
                        @foreach ($this->reasonOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('reason')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input wire:model.live="startsAt" :label="__('Fecha y hora de inicio')" type="datetime-local" required />
                    @error('startsAt')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input wire:model.live="endsAt" :label="__('Fecha y hora de finalización')" type="datetime-local" required />
                    @error('endsAt')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input wire:model.live="location" :label="__('Ubicación o sala')" placeholder="{{ __('Sala A, Oficina, Zoom...') }}" />
                    @error('location')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Visibilidad') }}
                    </label>
                    <select wire:model="visibility" class="w-full rounded-lg border border-zinc-300 bg-white p-2 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                        @foreach ($this->visibilityOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Agenda o detalles') }}
                </label>
                <textarea wire:model.live="agenda" rows="5" class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm text-zinc-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
                @error('agenda')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('Responsables internos') }}
                    </h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Selecciona a las personas responsables de la reunión. Al menos un responsable es obligatorio.') }}
                    </p>
                </div>

                <div class="grid gap-2 md:grid-cols-2">
                    @foreach ($this->users as $user)
                        <label class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <input type="checkbox" wire:model="responsibleIds" value="{{ $user->id }}" class="size-4 rounded border-zinc-300 text-primary-600 focus:ring-primary-500 dark:border-zinc-600 dark:bg-zinc-900" />
                            <div class="flex flex-col">
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $user->email }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                @error('responsibleIds')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('Contactos externos') }}
                    </h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Busca y agrega contactos externos para mantenerlos informados. Puedes seleccionar varios.') }}
                    </p>
                </div>

                <flux:input wire:model.live="contactSearch" :label="__('Buscar contacto')" placeholder="{{ __('Nombre, empresa o correo') }}" />

                @if ($contactSearch && $this->contactSuggestions->isNotEmpty())
                    <ul class="divide-y divide-zinc-200 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                        @foreach ($this->contactSuggestions as $contact)
                            <li class="flex items-center justify-between gap-3 bg-white p-3 text-sm dark:bg-zinc-900">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $contact->name }}</p>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $contact->email ?? __('Sin correo') }}
                                        @if ($contact->organization)
                                            &middot; {{ $contact->organization }}
                                        @endif
                                    </p>
                                </div>
                                <flux:button size="sm" wire:click="selectContact({{ $contact->id }})" type="button">
                                    {{ __('Agregar') }}
                                </flux:button>
                            </li>
                        @endforeach
                    </ul>
                @elseif ($contactSearch)
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('No se encontraron contactos con ese criterio.') }}
                    </p>
                @endif

                @if ($this->selectedContacts->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->selectedContacts as $contact)
                            <span class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-sm text-primary-700 dark:bg-primary-500/10 dark:text-primary-200">
                                <span>{{ $contact->name }}</span>
                                <button type="button" wire:click="removeContact({{ $contact->id }})" class="text-xs font-medium uppercase tracking-wide text-primary-700 hover:text-primary-900 dark:text-primary-300">
                                    {{ __('Quitar') }}
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" type="submit">
                    {{ __('Crear reunión') }}
                </flux:button>
            </div>
        </form>
    </section>
</x-layouts.app>
