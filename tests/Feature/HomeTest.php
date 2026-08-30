<?php

declare(strict_types=1);

use App\Livewire\Home\Index;
use App\Models\Table;
use Livewire\Livewire;

it('create table with invalid name returns validation errors', function () {
    Livewire::test(Index::class)
        ->set('form.name', '')
        ->call('createTable')
        ->assertHasErrors(['form.name' => 'required']);

    Livewire::test(Index::class)
        ->set('form.name', str_repeat('a', 256))
        ->call('createTable')
        ->assertHasErrors(['form.name' => 'max']);
});

it('create table with valid name redirects to manager url', function () {
    $test = Livewire::test(Index::class)
        ->set('form.name', 'Friday game')
        ->call('createTable');

    $this->assertDatabaseHas('poker_tables', ['name' => 'Friday game']);

    $table = Table::where('name', 'Friday game')->first();
    expect($table->token)->not->toBeNull();
    expect($table->manager_token)->not->toBeNull();

    $test->assertRedirectContains($table->token)
        ->assertRedirectContains($table->manager_token);
});

it('home page shows recent tables from cookie', function () {
    $table = Table::create([
        'name' => 'Recent Game',
        'token' => 'token123',
        'manager_token' => 'mgrtoken456',
    ]);

    $response = $this->withCookie('pokie_recent_tables', json_encode([
        ['token' => $table->token, 'name' => $table->name],
    ]))->get('/');

    $response->assertStatus(200);
    $response->assertSee('Recent Game');
});
