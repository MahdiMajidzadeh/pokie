<?php

declare(strict_types=1);

use App\Livewire\Admin\Login;
use App\Livewire\Admin\Tables as AdminTables;
use App\Models\Table;
use Livewire\Livewire;
use Tests\TestCase;

beforeEach(function () {
    config(['ptable.admin.username' => 'majid', 'ptable.admin.password' => 'secret123']);
});

function loggedInAsAdmin(TestCase $test): TestCase
{
    return $test->withSession(['ptable_admin' => true, 'ptable_admin_last_seen' => time()]);
}

it('returns 404 for /admin/* when credentials are not configured (AR-3)', function () {
    config(['ptable.admin.username' => null, 'ptable.admin.password' => null]);

    $this->get('/admin')->assertNotFound();
    $this->get('/admin/login')->assertNotFound();
});

it('shows one generic error for a wrong username or password (AR-20)', function () {
    Livewire::test(Login::class)
        ->set('form.username', 'wrong')
        ->set('form.password', 'wrong')
        ->call('login')
        ->assertHasErrors(['form.password'])
        ->assertSee('Invalid username or password');

    Livewire::test(Login::class)
        ->set('form.username', 'majid')
        ->set('form.password', 'wrong')
        ->call('login')
        ->assertHasErrors(['form.password'])
        ->assertSee('Invalid username or password');
});

it('logs in with the correct credentials and redirects to the table list', function () {
    Livewire::test(Login::class)
        ->set('form.username', 'majid')
        ->set('form.password', 'secret123')
        ->call('login')
        ->assertRedirect(route('admin.tables'));
});

it('locks out after too many failed attempts (AR-16)', function () {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('form.username', 'majid')
            ->set('form.password', 'wrong')
            ->call('login');
    }

    // Even the correct password is rejected during the lockout window.
    Livewire::test(Login::class)
        ->set('form.username', 'majid')
        ->set('form.password', 'secret123')
        ->call('login')
        ->assertHasErrors(['form.password']);
});

it('lists tables and supports search (AR-9)', function () {
    Table::factory()->create(['name' => 'Alpha Night']);
    Table::factory()->create(['name' => 'Beta Night']);

    loggedInAsAdmin($this);

    Livewire::test(AdminTables::class)
        ->assertSee('Alpha Night')
        ->assertSee('Beta Night')
        ->set('q', 'Alpha')
        ->assertSee('Alpha Night')
        ->assertDontSee('Beta Night');
});

it('elevates an admin session to the manager view on the plain viewer link (AR-11)', function () {
    $table = Table::factory()->create();

    loggedInAsAdmin($this)
        ->get("/{$table->table_hash}")
        ->assertSee('Viewing as super admin')
        ->assertSee('Add player');
});

it('reveals a table\'s manager hash from the admin list (AR-13)', function () {
    $table = Table::factory()->create();

    loggedInAsAdmin($this);

    Livewire::test(AdminTables::class)
        ->call('revealManagerHash', $table->id)
        ->assertSee($table->manager_hash);
});

it('deletes a table only when the typed hash matches exactly (AR-14)', function () {
    $table = Table::factory()->create();

    loggedInAsAdmin($this);

    Livewire::test(AdminTables::class)
        ->call('confirmDelete', $table->id)
        ->set('deleteConfirmationInput', 'WRONGHASH')
        ->call('deleteTable')
        ->assertHasErrors(['deleteConfirmationInput']);

    expect(Table::find($table->id))->not->toBeNull();

    Livewire::test(AdminTables::class)
        ->call('confirmDelete', $table->id)
        ->set('deleteConfirmationInput', $table->table_hash)
        ->call('deleteTable');

    expect(Table::find($table->id))->toBeNull();
});

it('expires the admin session after the inactivity window (AR-7)', function () {
    $table = Table::factory()->create();

    $this->withSession([
        'ptable_admin' => true,
        'ptable_admin_last_seen' => time() - (121 * 60),
    ])
        ->get("/{$table->table_hash}")
        ->assertDontSee('Viewing as super admin');
});

it('requires an active admin session for /admin routes other than login', function () {
    $this->get('/admin')->assertRedirect(route('admin.login'));
});
