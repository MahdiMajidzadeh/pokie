<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Forms\CreateTableForm;
use App\Models\Table;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * S1 — the landing page (FR-1..FR-4).
 */
#[Title('pTable — settle up after poker night')]
class Home extends Component
{
    public CreateTableForm $form;

    public function create(): void
    {
        $this->form->validate();

        $key = 'create-table:'.request()->ip();
        $max = (int) config('ptable.create_rate_limit_per_hour');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('form.name', "Too many tables created from this connection. Try again in {$minutes} minute(s).");

            return;
        }

        RateLimiter::hit($key, 3600);

        $table = Table::createWithHashes([
            'name' => filled($this->form->name) ? $this->form->name : $this->defaultName(),
            'default_buy_in' => $this->form->default_buy_in,
        ]);

        $this->redirect(
            route('table.manage', ['tableHash' => $table->table_hash, 'managerHash' => $table->manager_hash])
        );
    }

    protected function defaultName(): string
    {
        return 'Poker night — '.now()->format('j M');
    }

    public function render()
    {
        return view('livewire.home');
    }
}
