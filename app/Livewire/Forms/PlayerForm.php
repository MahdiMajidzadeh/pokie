<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Form;

class PlayerForm extends Form
{
    public string $name = '';

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('players', 'name')->where('table_id', $this->component->table->id),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'name.unique' => 'A player with this name already exists at this table.',
        ];
    }
}
