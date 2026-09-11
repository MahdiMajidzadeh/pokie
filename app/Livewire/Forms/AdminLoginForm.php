<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class AdminLoginForm extends Form
{
    public string $username = '';

    public string $password = '';

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
