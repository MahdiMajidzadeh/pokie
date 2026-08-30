<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class SuperadminLoginForm extends Form
{
    public string $password = '';

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
