<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * FR-5/FR-6/FR-8: add or rename a player. `editing_id` is set for a rename
 * so the uniqueness check ignores the player's own current row.
 */
class PlayerForm extends Form
{
    public ?int $editing_id = null;

    public string $name = '';

    public bool $with_buy_in = false;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $table = $this->getComponent()->table;

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                // MySQL's default collation (utf8mb4_0900_ai_ci) makes this
                // comparison case-insensitive at the database level, which
                // is what FR-6 asks for.
                Rule::unique('players', 'name')
                    ->where('table_id', $table->id)
                    ->ignore($this->editing_id),
            ],
            'with_buy_in' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'A player named ":input" is already on this table.',
        ];
    }
}
