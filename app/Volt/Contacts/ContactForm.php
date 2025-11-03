<?php

namespace App\Volt\Contacts;

use App\Models\ExternalContact;
use App\Models\Meeting;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

class ContactForm extends Component
{
    use WithFileUploads;

    public ?int $meeting_id = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $position = '';
    public string $company = '';
    public $photo;

    public function mount(?int $meetingId = null): void
    {
        $this->meeting_id = $meetingId ?? $this->meetings->first()?->id;
    }

    #[Computed]
    public function meetings(): Collection
    {
        return Meeting::orderBy('scheduled_at')
            ->orderBy('title')
            ->get();
    }

    public function rules(): array
    {
        return [
            'meeting_id' => ['required', 'exists:meetings,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'position' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $photoPath = $this->photo->store('external-contacts', 'public');

        ExternalContact::create([
            'meeting_id' => $validated['meeting_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'position' => $validated['position'],
            'company' => $validated['company'],
            'photo_path' => $photoPath,
        ]);

        $meetingId = $this->meeting_id;

        $this->reset(['name', 'email', 'phone', 'position', 'company', 'photo']);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->meeting_id = $meetingId;

        session()->flash('contact-created', __('Contacto agregado correctamente.'));

        $this->dispatch('contact-created');
    }

    public function template(): string
    {
        return <<<'BLADE'
<div class="space-y-6">
    <div class="space-y-1">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Agregar contacto externo') }}
        </h2>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Completa el formulario para guardar la información del contacto.') }}
        </p>
    </div>

    @if (session()->has('contact-created'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('contact-created') }}
        </div>
    @endif

    @if ($meetings->isEmpty())
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Primero debes crear una reunión para poder registrar contactos externos.') }}
        </p>
    @else
        <form wire:submit.prevent="save" class="space-y-4">
            <div>
                <label for="form-meeting" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Reunión') }}
                </label>
                <select
                    id="form-meeting"
                    wire:model.live="meeting_id"
                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                >
                    @foreach ($meetings as $meeting)
                        <option value="{{ $meeting->id }}">{{ $meeting->title }}</option>
                    @endforeach
                </select>
                @error('meeting_id')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="form-name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Nombre completo') }}
                    </label>
                    <input
                        id="form-name"
                        type="text"
                        wire:model.live="name"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="{{ __('Nombre del contacto') }}"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="form-email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Correo electrónico') }}
                    </label>
                    <input
                        id="form-email"
                        type="email"
                        wire:model.live="email"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="correo@ejemplo.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="form-phone" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Teléfono') }}
                    </label>
                    <input
                        id="form-phone"
                        type="text"
                        wire:model.live="phone"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="{{ __('Número de contacto') }}"
                    >
                    @error('phone')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="form-position" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('Cargo') }}
                    </label>
                    <input
                        id="form-position"
                        type="text"
                        wire:model.live="position"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="{{ __('Puesto del contacto') }}"
                    >
                    @error('position')
                        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="form-company" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Empresa') }}
                </label>
                <input
                    id="form-company"
                    type="text"
                    wire:model.live="company"
                    class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    placeholder="{{ __('Nombre de la empresa') }}"
                >
                @error('company')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="form-photo" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Fotografía') }}
                </label>
                <input
                    id="form-photo"
                    type="file"
                    wire:model.live="photo"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-zinc-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-zinc-300 dark:file:bg-indigo-500/10 dark:file:text-indigo-200"
                >
                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Formatos permitidos: JPG, PNG, GIF. Tamaño máximo 2MB.') }}
                </div>
                @error('photo')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
                <div wire:loading wire:target="photo" class="mt-2 text-sm text-indigo-600 dark:text-indigo-400">
                    {{ __('Cargando imagen...') }}
                </div>
                @if ($photo)
                    <div class="mt-4">
                        <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('Vista previa') }}</p>
                        <img src="{{ $photo->temporaryUrl() }}" alt="{{ $name ?: __('Foto del contacto') }}" class="mt-2 h-20 w-20 rounded-full object-cover">
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                >
                    {{ __('Guardar contacto') }}
                </button>
            </div>
        </form>
    @endif
</div>
BLADE;
    }
}
