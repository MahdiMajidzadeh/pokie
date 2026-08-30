<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Shared by buy-in and payback recording — both had identical validation
 * rules as separate FormRequests. Amount sign normalization (buy-in negative,
 * payback positive) stays in the owning component's action.
 */
class MoneyForm extends Form
{
    public ?int $player_id = null;

    public string $amount = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $playerIds = $this->component->table->players->pluck('id');

        return [
            'player_id' => ['required', 'integer', Rule::in($playerIds)],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'player_id.in' => 'The selected player does not belong to this table.',
        ];
    }
}
