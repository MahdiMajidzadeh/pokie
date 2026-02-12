<?php

declare(strict_types=1);

use App\Models\Table;

it('validates name is required', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);

    $response = $this->post("/t/t1/m1/players", ['name' => '']);

    $response->assertSessionHasErrors('name');
});

it('validates name max length', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);

    $response = $this->post("/t/t1/m1/players", ['name' => str_repeat('a', 256)]);

    $response->assertSessionHasErrors('name');
});

it('accepts valid name', function () {
    $table = Table::create(['name' => 'T', 'token' => 't1', 'manager_token' => 'm1']);

    $response = $this->post("/t/t1/m1/players", ['name' => 'Alice']);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('players', ['name' => 'Alice']);
});
