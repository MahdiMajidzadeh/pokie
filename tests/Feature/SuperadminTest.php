<?php

declare(strict_types=1);

use App\Livewire\Superadmin\Dashboard;
use App\Livewire\Superadmin\Login;
use App\Models\Table;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('unauthenticated access to dashboard redirects to login', function () {
    $response = $this->get(route('superadmin.dashboard'));
    $response->assertRedirect(route('superadmin.login'));
    $response->assertSessionHas('error');
});

it('login with wrong password returns error', function () {
    Config::set('superadmin.password', 'correctpass');

    Livewire::test(Login::class)
        ->set('form.password', 'wrongpass')
        ->call('login')
        ->assertNoRedirect()
        ->assertSet('error', 'Wrong password.');
});

it('login with correct password redirects to dashboard', function () {
    Config::set('superadmin.password', 'testpass');

    Livewire::test(Login::class)
        ->set('form.password', 'testpass')
        ->call('login')
        ->assertRedirect(route('superadmin.dashboard'));

    expect(session('superadmin'))->toBeTrue();
});

it('throttles repeated failed login attempts', function () {
    Config::set('superadmin.password', 'correctpass');

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('form.password', 'wrongpass')
            ->call('login');
    }

    expect(RateLimiter::tooManyAttempts('superadmin-login:'.request()->ip(), 5))->toBeTrue();

    $test = Livewire::test(Login::class)
        ->set('form.password', 'wrongpass')
        ->call('login')
        ->assertNoRedirect();

    expect($test->get('error'))->toContain('Too many attempts');
});

it('dashboard shows paginated tables', function () {
    $this->withSession(['superadmin' => true]);

    Table::create([
        'name' => 'Dashboard Table',
        'token' => 'dash1',
        'manager_token' => 'dashm1',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Dashboard Table');
});

it('logout redirects to login and clears session', function () {
    $this->withSession(['superadmin' => true]);

    Livewire::test(Dashboard::class)
        ->call('logout')
        ->assertRedirect(route('superadmin.login'));

    expect(session('success'))->toBe('Logged out.');
    expect(session('superadmin'))->toBeNull();
});
