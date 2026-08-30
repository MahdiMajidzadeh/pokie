<?php

declare(strict_types=1);

namespace App\Livewire\Home;

use App\Livewire\Forms\CreateTableForm;
use App\Models\Table;
use App\Support\RecentTables;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public array $recentTables = [];

    public CreateTableForm $form;

    public function mount(): void
    {
        $this->recentTables = RecentTables::read();
    }

    public function createTable(): void
    {
        $validated = $this->form->validate();

        $table = Table::create([
            'name' => $validated['name'],
            'token' => Str::random(32),
            'manager_token' => Str::random(32),
        ]);

        $this->redirect(route('table.manager', [
            'token' => $table->token,
            'managerToken' => $table->manager_token,
        ]));
    }

    public function render()
    {
        return view('livewire.home.index')->extends('layouts.app');
    }
}
