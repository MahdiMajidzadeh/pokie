<?php

declare(strict_types=1);

use App\Models\Table;
use Illuminate\Support\Facades\Config;

it('unauthenticated access to dashboard redirects to login', function () {
    $response = $this->get(route('superadmin.dashboard'));
    $response->assertRedirect(route('superadmin.login'));
    $response->assertSessionHas('error');
});

it('login with wrong password returns error', function () {
    Config::set('superadmin.password', 'correctpass');

    $response = $this->post(route('superadmin.login'), ['password' => 'wrongpass']);
    $response->assertRedirect(route('superadmin.login'));
    $response->assertSessionHas('error');
});

it('login with correct password redirects to dashboard', function () {
    Config::set('superadmin.password', 'testpass');

    $response = $this->post(route('superadmin.login'), ['password' => 'testpass']);
    $response->assertRedirect(route('superadmin.dashboard'));
    expect(session('superadmin'))->toBeTrue();
});

it('dashboard shows paginated tables', function () {
    Config::set('superadmin.password', 'testpass');
    $this->post(route('superadmin.login'), ['password' => 'testpass']);

    Table::create([
        'name' => 'Dashboard Table',
        'token' => 'dash1',
        'manager_token' => 'dashm1',
    ]);

    $response = $this->get(route('superadmin.dashboard'));
    $response->assertStatus(200);
    $response->assertSee('Dashboard Table');
});

it('logout redirects to login and clears session', function () {
    Config::set('superadmin.password', 'testpass');
    $this->post(route('superadmin.login'), ['password' => 'testpass']);

    $response = $this->post(route('superadmin.logout'));
    $response->assertRedirect(route('superadmin.login'));
    $response->assertSessionHas('success');
    expect(session('superadmin'))->toBeNull();
});
