<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payback;
use App\Models\Player;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payback>
 */
class PaybackFactory extends Factory
{
    protected $model = Payback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $table = Table::factory();

        return [
            'table_id' => $table,
            'player_id' => Player::factory()->for($table),
            'amount' => fake()->randomFloat(2, 1, 100),
        ];
    }
}
