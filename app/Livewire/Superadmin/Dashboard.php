<?php

declare(strict_types=1);

namespace App\Livewire\Superadmin;

use App\Models\Table;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    #[Computed]
    public function tables()
    {
        return Table::query()
            ->withCount(['players', 'buyIns', 'paybacks', 'settlements'])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function logout(): void
    {
        session()->forget('superadmin');
        session()->invalidate();
        session()->regenerateToken();

        session()->flash('success', 'Logged out.');
        $this->redirect(route('superadmin.login'));
    }

    public function render()
    {
        return view('livewire.superadmin.dashboard')->extends('layouts.app');
    }
}
