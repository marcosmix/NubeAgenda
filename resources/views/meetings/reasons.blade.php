<?php

use App\Models\MeetingReason;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    public string $description = '';

    public bool $is_global = false;

    public ?int $editingReasonId = null;

    public ?int $deletingReasonId = null;

    /**
     * Validation rules for a meeting reason.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_global' => ['boolean'],
        ];
    }

    /**
     * Meeting reasons ordered alphabetically.
     */
    public function getReasonsProperty()
    {
        return MeetingReason::orderBy('name')->get();
    }

    /**
     * Persist a meeting reason in storage.
     */
    public function saveReason(): void
    {
        $data = $this->validate();
        $data['is_global'] = (bool) $data['is_global'];
        $data['description'] = array_key_exists('description', $data)
            ? (\is_string($data['description']) ? trim($data['description']) : $data['description'])
            : null;

        if ($data['description'] === '') {
            $data['description'] = null;
        }

        if ($this->editingReasonId) {
            MeetingReason::query()->findOrFail($this->editingReasonId)->update($data);

            $this->dispatch('reason-saved');
        } else {
            MeetingReason::create($data);

            $this->dispatch('reason-saved');
        }

        $this->resetForm();
    }

    /**
     * Populate the form for editing an existing meeting reason.
     */
    public function editReason(int $reasonId): void
    {
        $reason = MeetingReason::query()->findOrFail($reasonId);

        $this->editingReasonId = $reason->id;
        $this->name = $reason->name;
        $this->description = (string) ($reason->description ?? '');
        $this->is_global = (bool) $reason->is_global;
    }

    /**
     * Prepare a meeting reason for deletion.
     */
    public function confirmDelete(int $reasonId): void
    {
        $this->deletingReasonId = $reasonId;
    }

    /**
     * Delete the meeting reason that is pending confirmation.
     */
    public function deleteReason(): void
    {
        if (! $this->deletingReasonId) {
            return;
        }

        MeetingReason::query()->findOrFail($this->deletingReasonId)->delete();

        $this->dispatch('reason-deleted');

        $this->deletingReasonId = null;

        if ($this->editingReasonId) {
            $this->resetForm();
        }
    }

    /**
     * Cancel any destructive action currently pending confirmation.
     */
    public function cancelDelete(): void
    {
        $this->deletingReasonId = null;
    }

    /**
     * Reset the form to its default state.
     */
    public function resetForm(): void
    {
        $this->reset('name', 'description', 'is_global', 'editingReasonId');
        $this->is_global = false;
    }
}; ?>

<section class="w-full space-y-6">
    <header>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
            {{ __('Meeting reasons') }}
        </h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Manage the reasons that can be assigned to meetings.') }}
        </p>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <form wire:submit="saveReason" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <flux:input
                        wire:model.defer="name"
                        :label="__('Name')"
                        type="text"
                        required
                        autofocus
                    />
                    @error('name')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="is_global">
                        {{ __('Global reason') }}
                    </label>
                    <div class="flex items-center gap-3">
                        <input
                            id="is_global"
                            type="checkbox"
                            wire:model.defer="is_global"
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Available for all users when enabled.') }}
                        </span>
                    </div>
                    @error('is_global')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="description">
                    {{ __('Description') }}
                </label>
                <textarea
                    id="description"
                    wire:model.defer="description"
                    rows="3"
                    class="w-full rounded-md border border-gray-300 bg-white p-3 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    placeholder="{{ __('Optional details about when this reason should be used.') }}"
                ></textarea>
                @error('description')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <flux:button type="submit" variant="primary">
                    {{ $editingReasonId ? __('Update reason') : __('Create reason') }}
                </flux:button>

                @if ($editingReasonId)
                    <flux:button type="button" variant="ghost" wire:click="resetForm">
                        {{ __('Cancel edit') }}
                    </flux:button>
                @endif

                <x-action-message on="reason-saved">
                    {{ __('Reason saved successfully.') }}
                </x-action-message>
                <x-action-message on="reason-deleted">
                    {{ __('Reason deleted.') }}
                </x-action-message>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Name') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Description') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Global') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-950">
                    @forelse ($this->reasons as $reason)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $reason->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ $reason->description ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full {{ $reason->is_global ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $reason->is_global ? __('Yes') : __('No') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="editReason({{ $reason->id }})">
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="destructive"
                                        wire:click="confirmDelete({{ $reason->id }})"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No meeting reasons have been created yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($deletingReasonId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4">
            <div class="w-full max-w-md space-y-4 rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Delete meeting reason') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Are you sure you want to delete this reason? This action cannot be undone.') }}
                </p>
                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="cancelDelete">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="button" variant="destructive" wire:click="deleteReason">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</section>
