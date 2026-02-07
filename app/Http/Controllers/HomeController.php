<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $table = Table::create([
            'name' => $validated['name'],
            'token' => Str::random(32),
            'manager_token' => Str::random(32),
        ]);

        return redirect()->route('table.manager', [
            'token' => $table->token,
            'manager_token' => $table->manager_token,
        ]);
    }
}
