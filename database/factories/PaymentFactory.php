<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Player;
use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $table = Table::factory()->create();
        $from = Player::factory()->for($table)->create();
        $to = Player::factory()->for($table)->create();

        return [
            'table_id' => $table->id,
            'from_player_id' => $from->id,
            'to_player_id' => $to->id,
            'amount' => $this->faker->numberBetween(1, 1000),
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['paid_at' => now()]);
    }
}
