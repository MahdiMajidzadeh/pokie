<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class SettlementForm extends Form
{
    public ?int $player_id = null;

    public string $amount = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $playerIds = $this->component->table->players->pluck('id');

        return [
            'player_id' => ['required', 'integer', Rule::in($playerIds)],
            'amount' => ['required', 'numeric'],
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
