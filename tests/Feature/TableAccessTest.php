<?php

declare(strict_types=1);

use App\Models\Table;

it('returns 404 for an unknown table hash', function () {
    $this->get('/ZZZZZZ')->assertNotFound();
});

it('returns 404 for a syntactically invalid hash', function () {
    $this->get('/abc')->assertNotFound(); // too short, also fails the route pattern
});

it('accepts a lowercase hash — lookup is case-insensitive (§4.1)', function () {
    $table = Table::factory()->create();

    $this->get('/'.strtolower($table->table_hash))->assertOk();
});

it('returns 404 for a wrong manager hash on a real table, same as an unknown table (NFR-3)', function () {
    $table = Table::factory()->create();

    $this->get("/{$table->table_hash}/WRONG1")->assertNotFound();
});

it('never renders the manager hash on the plain viewer page', function () {
    $table = Table::factory()->create();

    // Modal markup (headings like "Add player") is always present-but-hidden
    // by design, for every role, so it's this table's manager_hash itself —
    // not incidental heading text — that must never appear for a viewer.
    $this->get("/{$table->table_hash}")
        ->assertOk()
        ->assertDontSee($table->manager_hash)
        ->assertDontSee('wire:click="openAddPlayer"', false)
        ->assertSee('Read-only view');
});

it('shows manager controls only via the manager link', function () {
    $table = Table::factory()->create();

    $this->get("/{$table->table_hash}/{$table->manager_hash}")
        ->assertOk()
        ->assertSee('Add player')
        ->assertSee($table->manager_hash);
});

it('sends the noindex header on every table page (§4.2)', function () {
    $table = Table::factory()->create();

    $this->get("/{$table->table_hash}")->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get("/{$table->table_hash}/{$table->manager_hash}")->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('sends the noindex header even on a 404', function () {
    $this->get('/ZZZZZZ')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
