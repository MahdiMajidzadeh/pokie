<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TableStatus;
use App\Models\Table;
use App\Support\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Table> */
class TableFactory extends Factory
{
    protected $model = Table::class;

    public function definition(): array
    {
        return [
            'table_hash' => Hash::generate(),
            'manager_hash' => Hash::generate(),
            'name' => 'Poker night — '.$this->faker->dayOfWeek(),
            'default_buy_in' => $this->faker->randomElement([null, 100, 500, 1000]),
            'status' => TableStatus::Open,
            'settled_at' => null,
        ];
    }

    public function settled(): static
    {
        return $this->state(fn () => [
            'status' => TableStatus::Settled,
            'settled_at' => now(),
        ]);
    }
}
