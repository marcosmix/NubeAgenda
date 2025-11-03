<?php

namespace App\Volt\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

class EditProfile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $gmail = '';

    public string $google_api_key = '';

    /**
     * Mount the component with the authenticated user's data.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->gmail = (string) ($user->gmail ?? '');
        $this->google_api_key = (string) ($user->google_api_key ?? '');
    }

    /**
     * Validation rules for updating the profile.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore(Auth::id()),
            ],
            'gmail' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'google_api_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate();

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'gmail' => $validated['gmail'] ?? null,
        ];

        $apiKey = $validated['google_api_key'] ?? null;

        if ($apiKey !== null) {
            $apiKey = \is_string($apiKey) ? Str::of($apiKey)->trim()->toString() : $apiKey;

            if ($apiKey !== '') {
                $updates['google_api_key'] = $apiKey;
            }
        }

        $user->fill($updates);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->mount();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
