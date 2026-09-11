<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

/**
 * FR-1: table name (optional) and an optional default buy-in amount.
 */
class CreateTableForm extends Form
{
    public ?string $name = null;

    public ?int $default_buy_in = null;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:80'],
            'default_buy_in' => ['nullable', 'integer', 'min:1', 'max:'.config('ptable.amount_max')],
        ];
    }
}
