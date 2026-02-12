<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Player;
use App\Models\Settlement;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $table = Table::factory();

        return [
            'table_id' => $table,
            'player_id' => Player::factory()->for($table),
            'amount' => fake()->randomFloat(2, -50, 50),
        ];
    }
}
