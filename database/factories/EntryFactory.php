<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Entry> */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        $player = Player::factory()->create();

        return [
            'table_id' => $player->table_id,
            'player_id' => $player->id,
            'type' => EntryType::BuyIn,
            'amount' => $this->faker->randomElement([100, 200, 500, 1000]),
            'note' => null,
        ];
    }

    public function buyIn(): static
    {
        return $this->state(fn () => ['type' => EntryType::BuyIn]);
    }

    public function cashOut(): static
    {
        return $this->state(fn () => ['type' => EntryType::CashOut]);
    }
}
