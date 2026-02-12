<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BuyIn;
use App\Models\Player;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyIn>
 */
class BuyInFactory extends Factory
{
    protected $model = BuyIn::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $table = Table::factory();

        return [
            'table_id' => $table,
            'player_id' => Player::factory()->for($table),
            'amount' => fake()->randomFloat(2, -100, -1),
        ];
    }
}
