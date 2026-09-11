<?php

declare(strict_types=1);

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Player;
use App\Models\Table;
use App\Settlement\SettleTable;
use App\Support\SettlementText;

function settledThreePlayerTable(): Table
{
    $table = Table::factory()->create(['name' => 'Friday Game']);
    $alice = Player::factory()->for($table)->create(['name' => 'Alice', 'position' => 1]);
    $bob = Player::factory()->for($table)->create(['name' => 'Bob', 'position' => 2]);
    $carol = Player::factory()->for($table)->create(['name' => 'Carol', 'position' => 3]);

    foreach ([$alice, $bob, $carol] as $player) {
        Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 1000]);
    }
    Entry::factory()->for($table)->create(['player_id' => $alice->id, 'type' => EntryType::CashOut, 'amount' => 2500]);
    Entry::factory()->for($table)->create(['player_id' => $bob->id, 'type' => EntryType::CashOut, 'amount' => 500]);
    Entry::factory()->for($table)->create(['player_id' => $carol->id, 'type' => EntryType::CashOut, 'amount' => 0]);

    return (new SettleTable)($table)->fresh();
}

it('renders results winners-first, payments grouped by payer, and the share link', function () {
    $table = settledThreePlayerTable();

    $text = SettlementText::for($table);
    $lines = explode("\n", $text);

    expect($lines[0])->toBe('Friday Game');
    expect($text)->toContain("Results\nAlice +1,500\nBob -500\nCarol -1,000");
    expect($text)->toContain("Payments\nBob pays Alice 500\nCarol pays Alice 1,000");
    expect($text)->toContain('0 of 2 paid');
    expect($lines[array_key_last($lines)])->toBe(url('/'.$table->table_hash));

    // Chat-friendly: no markdown, no U+2212 minus sign.
    expect($text)->not->toContain('**')->not->toContain("\u{2212}");
});

it('marks paid payments and updates the tally', function () {
    $table = settledThreePlayerTable();
    $table->payments()->where('amount', 500)->update(['paid_at' => now()]);

    $text = SettlementText::for($table);

    expect($text)->toContain('Bob pays Alice 500 (paid)');
    expect($text)->toContain('Carol pays Alice 1,000');
    expect($text)->not->toContain('Carol pays Alice 1,000 (paid)');
    expect($text)->toContain('1 of 2 paid');
});

it('says so when everyone broke even', function () {
    $table = Table::factory()->create();
    $a = Player::factory()->for($table)->create();
    $b = Player::factory()->for($table)->create();
    foreach ([$a, $b] as $player) {
        Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::BuyIn, 'amount' => 100]);
        Entry::factory()->for($table)->create(['player_id' => $player->id, 'type' => EntryType::CashOut, 'amount' => 100]);
    }
    $table = (new SettleTable)($table)->fresh();

    expect(SettlementText::for($table))->toContain('No payments needed');
});

it('offers the copy button to viewers and managers once settled, never while open', function () {
    $table = settledThreePlayerTable();

    $this->get("/{$table->table_hash}")->assertSee('Copy settlement as text');
    $this->get("/{$table->table_hash}/{$table->manager_hash}")->assertSee('Copy settlement as text');

    $open = Table::factory()->create();
    $this->get("/{$open->table_hash}")->assertDontSee('Copy settlement as text');
});
