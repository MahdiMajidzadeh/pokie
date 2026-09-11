<?php

declare(strict_types=1);

use App\Livewire\Home;
use App\Models\Table;
use Livewire\Livewire;

it('creates a table with two independent hashes and the correct default buy-in', function () {
    Livewire::test(Home::class)
        ->set('form.name', 'Friday Game')
        ->set('form.default_buy_in', 100)
        ->call('create');

    $table = Table::where('name', 'Friday Game')->first();

    expect($table)->not->toBeNull();
    expect($table->table_hash)->toHaveLength(6);
    expect($table->manager_hash)->toHaveLength(6);
    expect($table->table_hash)->not->toBe($table->manager_hash);
    expect($table->default_buy_in)->toBe(100);
});

it('redirects to the manager url (FR-2)', function () {
    $test = Livewire::test(Home::class)->set('form.name', 'Friday Game')->call('create');

    $table = Table::where('name', 'Friday Game')->firstOrFail();

    $test->assertRedirect(
        route('table.manage', ['tableHash' => $table->table_hash, 'managerHash' => $table->manager_hash])
    );
});

it('defaults the table name when left blank', function () {
    Livewire::test(Home::class)->call('create');

    $table = Table::first();

    expect($table->name)->toContain('Poker night —');
});

it('validates the name length and the default buy-in bounds', function () {
    Livewire::test(Home::class)
        ->set('form.name', str_repeat('a', 100))
        ->call('create')
        ->assertHasErrors(['form.name' => 'max']);

    Livewire::test(Home::class)
        ->set('form.default_buy_in', 0)
        ->call('create')
        ->assertHasErrors(['form.default_buy_in' => 'min']);
});

it('rate limits table creation to 10 per hour per ip (NFR-2)', function () {
    for ($i = 0; $i < 10; $i++) {
        Livewire::test(Home::class)->set('form.name', "Table $i")->call('create');
    }

    Livewire::test(Home::class)
        ->set('form.name', 'One too many')
        ->call('create')
        ->assertHasErrors(['form.name']);

    expect(Table::count())->toBe(10);
});
