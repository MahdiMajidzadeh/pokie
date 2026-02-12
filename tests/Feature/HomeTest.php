<?php

declare(strict_types=1);

use App\Models\Table;

it('create table with invalid name returns validation errors', function () {
    $response = $this->post('/tables', ['name' => '']);
    $response->assertSessionHasErrors('name');

    $response = $this->post('/tables', ['name' => str_repeat('a', 256)]);
    $response->assertSessionHasErrors('name');
});

it('create table with valid name redirects to manager url', function () {
    $response = $this->post('/tables', ['name' => 'Friday game']);
    $response->assertRedirect();
    $this->assertDatabaseHas('poker_tables', ['name' => 'Friday game']);

    $table = Table::where('name', 'Friday game')->first();
    $redirect = $response->headers->get('Location');
    expect($redirect)->toContain($table->token);
    expect($redirect)->toContain($table->manager_token);
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
