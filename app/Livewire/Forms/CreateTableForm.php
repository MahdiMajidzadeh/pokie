<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class CreateTableForm extends Form
{
    public string $name = '';

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
