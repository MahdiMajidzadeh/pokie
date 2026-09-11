<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Livewire\Forms\AdminLoginForm;
use App\Support\AdminAudit;
use App\Support\AdminSession;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * AR-16..AR-20: throttled login, one generic failure message, every
 * attempt logged.
 */
#[Title('Admin login — pTable')]
class Login extends Component
{
    public AdminLoginForm $form;

    public function login(): void
    {
        $this->form->validate();

        $ip = (string) request()->ip();
        $lockoutKey = "admin-lockout:{$ip}";
        $attemptsKey = "admin-login:{$ip}";

        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $this->fail();

            return;
        }

        // AR-1: hash_equals on both fields regardless of which is wrong,
        // so the timing/response never reveals which one failed.
        $usernameMatches = hash_equals((string) config('ptable.admin.username'), $this->form->username);
        $passwordMatches = hash_equals((string) config('ptable.admin.password'), $this->form->password);

        if ($usernameMatches && $passwordMatches) {
            RateLimiter::clear($attemptsKey);
            AdminSession::login();
            AdminAudit::loginAttempt(true, $this->form->username);
            $this->redirect(route('admin.tables'));

            return;
        }

        AdminAudit::loginAttempt(false, $this->form->username);

        $decaySeconds = ((int) config('ptable.admin.login_decay_minutes')) * 60;
        RateLimiter::hit($attemptsKey, $decaySeconds);

        if (RateLimiter::attempts($attemptsKey) >= (int) config('ptable.admin.login_max_attempts')) {
            $lockoutSeconds = ((int) config('ptable.admin.login_lockout_minutes')) * 60;
            RateLimiter::hit($lockoutKey, $lockoutSeconds);
        }

        $this->fail();
    }

    /**
     * AR-20: one generic message, whether the username or the password was wrong.
     */
    protected function fail(): void
    {
        $this->addError('form.password', 'Invalid username or password.');
    }

    public function render()
    {
        return view('livewire.admin.login');
    }
}
