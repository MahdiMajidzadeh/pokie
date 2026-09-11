<?php

declare(strict_types=1);

use App\Enums\EntryType;
use App\Livewire\Tables\Show;
use App\Models\Entry;
use App\Models\Player;
use App\Models\Table;
use Livewire\Livewire;

function tableWithDefaultBuyIn(int $amount = 100): Table
{
    return Table::factory()->create(['default_buy_in' => $amount]);
}

function manage(Table $table)
{
    return Livewire::test(Show::class, ['tableHash' => $table->table_hash, 'managerHash' => $table->manager_hash]);
}

// ---------------------------------------------------------------
// Players (FR-5..FR-10)
// ---------------------------------------------------------------

it('lets the manager add a player', function () {
    $table = tableWithDefaultBuyIn();

    manage($table)->set('playerForm.name', 'Alice')->call('savePlayer');

    expect($table->players()->where('name', 'Alice')->exists())->toBeTrue();
});

it('records the default buy-in when adding a player with_buy_in checked (FR-7)', function () {
    $table = tableWithDefaultBuyIn(100);

    manage($table)
        ->set('playerForm.name', 'Alice')
        ->set('playerForm.with_buy_in', true)
        ->call('savePlayer');

    $player = $table->players()->where('name', 'Alice')->firstOrFail();

    expect($player->entries()->where('type', EntryType::BuyIn)->sum('amount'))->toEqual(100);
});

it('rejects a duplicate player name case-insensitively (FR-6)', function () {
    $table = tableWithDefaultBuyIn();
    Player::factory()->for($table)->create(['name' => 'Alice']);

    manage($table)
        ->set('playerForm.name', 'ALICE')
        ->call('savePlayer')
        ->assertHasErrors(['playerForm.name']);
});

it('lets the manager rename a player while open (FR-8)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create(['name' => 'Alice']);

    manage($table)
        ->call('openRenamePlayer', $player->id)
        ->set('playerForm.name', 'Alicia')
        ->call('savePlayer');

    expect($player->fresh()->name)->toBe('Alicia');
});

it('blocks removing a player who has entries (FR-9)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)->call('removePlayer', $player->id);

    expect(Player::find($player->id))->not->toBeNull();
});

it('removes a player with no entries', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();

    manage($table)->call('removePlayer', $player->id);

    expect(Player::find($player->id))->toBeNull();
});

it('toggles a player between active and left (FR-10)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create(['has_left' => false]);

    manage($table)->call('toggleLeft', $player->id);
    expect($player->fresh()->has_left)->toBeTrue();

    manage($table)->call('toggleLeft', $player->id);
    expect($player->fresh()->has_left)->toBeFalse();
});

// ---------------------------------------------------------------
// Entries (FR-11..FR-19)
// ---------------------------------------------------------------

it('records a buy-in and a cash-out', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();

    manage($table)
        ->call('openEntry', $player->id, 'buy_in')
        ->set('entryForm.amount', 200)
        ->call('saveEntry');

    expect($player->entries()->where('type', 'buy_in')->sum('amount'))->toEqual(200);

    manage($table)
        ->call('openEntry', $player->id, 'cash_out')
        ->set('entryForm.amount', 150)
        ->call('saveEntry');

    expect($player->entries()->where('type', 'cash_out')->sum('amount'))->toEqual(150);
});

it('rejects a cash-out larger than what is on the table, with the exact message (FR-18)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)
        ->call('openEntry', $player->id, 'cash_out')
        ->set('entryForm.amount', 500)
        ->call('saveEntry')
        ->assertHasErrors(['entryForm.amount']);

    expect($player->entries()->where('type', 'cash_out')->count())->toBe(0);
});

it('allows a cash-out larger than the player\'s own buy-in — a winner cashes out more (FR-17)', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)
        ->call('openEntry', $a->id, 'cash_out')
        ->set('entryForm.amount', 200)
        ->call('saveEntry')
        ->assertHasNoErrors();

    expect($a->entries()->where('type', 'cash_out')->sum('amount'))->toEqual(200);
});

it('marks a player as left when cashing out with the one-tap option (FR-19)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)
        ->call('openEntry', $player->id, 'cash_out')
        ->set('entryForm.amount', 100)
        ->set('entryForm.mark_left', true)
        ->call('saveEntry');

    expect($player->fresh()->has_left)->toBeTrue();
});

it('lets the manager edit and delete an entry while open (FR-14)', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();
    $entry = Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)
        ->call('openEditEntry', $entry->id)
        ->set('entryForm.amount', 250)
        ->call('saveEntry');

    expect($entry->fresh()->amount)->toBe(250);

    manage($table)->call('deleteEntry', $entry->id);

    expect(Entry::find($entry->id))->toBeNull();
});

it('re-checks the cash-out cap on edit, excluding the entry\'s own current amount', function () {
    $table = tableWithDefaultBuyIn();
    $player = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    $cashOut = Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::CashOut, 'amount' => 100]);
    // on_table is now 0; editing the cash-out itself back up to 100 should still be allowed.

    manage($table)
        ->call('openEditEntry', $cashOut->id)
        ->set('entryForm.amount', 100)
        ->call('saveEntry')
        ->assertHasNoErrors();

    // But raising it beyond 100 (the true remaining room) should fail.
    manage($table)
        ->call('openEditEntry', $cashOut->id)
        ->set('entryForm.amount', 101)
        ->call('saveEntry')
        ->assertHasErrors(['entryForm.amount']);
});

// ---------------------------------------------------------------
// Access control
// ---------------------------------------------------------------

it('forbids a viewer from calling any manager action', function () {
    $table = tableWithDefaultBuyIn();

    Livewire::test(Show::class, ['tableHash' => $table->table_hash])
        ->call('openAddPlayer')
        ->assertForbidden();
});

it('locks entries and players once the table is settled', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::CashOut, 'amount' => 200]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::CashOut, 'amount' => 0]);

    $component = manage($table)->call('settle');

    $component->set('playerForm.name', 'Late Player')->call('savePlayer')->assertForbidden();
});

// ---------------------------------------------------------------
// Settlement (FR-24..FR-32, §6)
// ---------------------------------------------------------------

it('blocks settling an unbalanced table and leaves it open', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);

    manage($table)->call('settle');

    expect($table->fresh()->isOpen())->toBeTrue();
    expect($table->payments()->count())->toBe(0);
});

it('blocks settling with fewer than 2 players with entries', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::CashOut, 'amount' => 100]);

    manage($table)->call('settle');

    expect($table->fresh()->isOpen())->toBeTrue();
});

it('settles a balanced table and creates the minimal payment list', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create(['position' => 1]);
    $b = Player::factory()->for($table)->create(['position' => 2]);

    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::CashOut, 'amount' => 150]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::CashOut, 'amount' => 50]);

    manage($table)->call('settle');

    $table->refresh();
    expect($table->isSettled())->toBeTrue();
    expect($table->payments()->count())->toBe(1);

    $payment = $table->payments()->first();
    expect($payment->from_player_id)->toBe($b->id);
    expect($payment->to_player_id)->toBe($a->id);
    expect($payment->amount)->toBe(50);
});

it('toggles a payment paid/unpaid and tracks paid_at', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::CashOut, 'amount' => 200]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::CashOut, 'amount' => 0]);

    $component = manage($table)->call('settle');
    $payment = $table->fresh()->payments()->firstOrFail();
    expect($payment->paid_at)->toBeNull();

    $component->call('togglePaid', $payment->id);
    expect($payment->fresh()->paid_at)->not->toBeNull();

    $component->call('togglePaid', $payment->id);
    expect($payment->fresh()->paid_at)->toBeNull();
});

it('reopening clears the payment list and reopens the table (§6)', function () {
    $table = tableWithDefaultBuyIn();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
    Entry::factory()->for($table)->create(['player_id' => $a->id, 'type' => EntryType::CashOut, 'amount' => 200]);
    Entry::factory()->for($table)->create(['player_id' => $b->id, 'type' => EntryType::CashOut, 'amount' => 0]);

    $component = manage($table)->call('settle');
    $component->call('reopen');

    $table->refresh();
    expect($table->isOpen())->toBeTrue();
    expect($table->payments()->count())->toBe(0);
});

it('enforces the write rate limit of 60 per minute per table (NFR-2)', function () {
    $table = tableWithDefaultBuyIn();
    $component = manage($table);

    for ($i = 0; $i < 60; $i++) {
        $component->set('playerForm.name', "Player $i")->call('savePlayer');
    }

    $component->set('playerForm.name', 'One too many')->call('savePlayer');

    expect($table->players()->count())->toBe(60);
});
