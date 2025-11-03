<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class CorporateEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domains = collect(config('auth.corporate_domains', []))
            ->map(fn ($domain) => Str::of($domain ?? '')
                ->lower()
                ->ltrim('@')
                ->trim())
            ->filter()
            ->unique()
            ->values();

        if ($domains->isEmpty()) {
            return;
        }

        $email = Str::of((string) $value)->lower();

        if ($email->contains('@')) {
            $emailDomain = $email->afterLast('@');
        } else {
            $emailDomain = $email->toString();
        }

        if (! $domains->contains($emailDomain)) {
            $fail(__('The :attribute must use an approved corporate email domain.'));
        }
    }
}
