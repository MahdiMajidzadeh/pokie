<?php

declare(strict_types=1);

namespace App\Livewire\Superadmin;

use App\Livewire\Forms\SuperadminLoginForm;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Login extends Component
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    #[Locked]
    public bool $passwordConfigured = false;

    public ?string $error = null;

    public SuperadminLoginForm $form;

    public function mount(): void
    {
        if (session('superadmin')) {
            $this->redirect(route('superadmin.dashboard'));

            return;
        }

        $this->passwordConfigured = filled(config('superadmin.password'));
        // Real full-page redirects (e.g. SuperadminMiddleware bouncing an
        // unauthenticated visitor here) still flash through the session
        // normally; same-request login failures set $error directly below.
        $this->error = session('error');
    }

    private function throttleKey(): string
    {
        return 'superadmin-login:'.request()->ip();
    }

    public function login(): void
    {
        $this->error = null;
        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->error = 'Too many attempts. Try again in '.RateLimiter::availableIn($key).'s.';

            return;
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);

        $password = config('superadmin.password');
        if (! filled($password)) {
            $this->error = 'Superadmin is not configured.';

            return;
        }

        $validated = $this->form->validate();

        if (! hash_equals((string) $password, $validated['password'])) {
            $this->error = 'Wrong password.';

            return;
        }

        RateLimiter::clear($key);
        session()->put('superadmin', true);
        $this->redirect(route('superadmin.dashboard'));
    }

    public function render()
    {
        return view('livewire.superadmin.login')->extends('layouts.app');
    }
}
