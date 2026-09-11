<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Player;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Player> */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'table_id' => Table::factory(),
            'name' => $this->faker->unique()->firstName(),
            'has_left' => false,
            'position' => 0,
        ];
    }
}
