<?php

declare(strict_types=1);

it('validates name is required', function () {
    $response = $this->post('/tables', ['name' => '']);

    $response->assertSessionHasErrors('name');
});

it('validates name max length', function () {
    $response = $this->post('/tables', ['name' => str_repeat('a', 256)]);

    $response->assertSessionHasErrors('name');
});

it('accepts valid name', function () {
    $response = $this->post('/tables', ['name' => 'Friday game']);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('poker_tables', ['name' => 'Friday game']);
});
