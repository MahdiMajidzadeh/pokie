<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\EntryType;
use App\Models\Entry;
use App\Support\Ledger;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * FR-11..FR-19: a single buy-in or cash-out. `editing_id` is set when
 * editing an existing entry, so the cash-out cap check (FR-18) can exclude
 * the amount the entry being edited currently holds.
 */
class EntryForm extends Form
{
    public ?int $editing_id = null;

    public ?int $player_id = null;

    public string $type = EntryType::BuyIn->value;

    public ?int $amount = null;

    public ?string $note = null;

    public bool $mark_left = false;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $table = $this->getComponent()->table;
        $max = (int) config('ptable.amount_max');

        return [
            'player_id' => [
                'required',
                'integer',
                Rule::exists('players', 'id')->where('table_id', $table->id),
            ],
            'type' => ['required', Rule::in([EntryType::BuyIn->value, EntryType::CashOut->value])],
            'amount' => [
                'required',
                'integer',
                'min:1',
                "max:{$max}",
                function (string $attribute, mixed $value, \Closure $fail) use ($table): void {
                    if ($this->type !== EntryType::CashOut->value) {
                        return;
                    }

                    $onTable = Ledger::for($table)->onTable;

                    // FR-18's cap is against the state *before* this entry;
                    // when editing an existing cash-out, its own current
                    // amount is already subtracted out of on_table, so add
                    // it back before comparing.
                    if ($this->editing_id) {
                        $existing = Entry::find($this->editing_id);

                        if ($existing && $existing->type === EntryType::CashOut) {
                            $onTable += $existing->amount;
                        }
                    }

                    if ($value > $onTable) {
                        $fail('Only '.number_format(max(0, $onTable)).' is left on the table.');
                    }
                },
            ],
            'note' => ['nullable', 'string', 'max:120'],
            'mark_left' => ['boolean'],
        ];
    }
}
