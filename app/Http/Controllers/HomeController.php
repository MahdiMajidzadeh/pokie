<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableRequest;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const RECENT_TABLES_COOKIE = 'pokie_recent_tables';

    public function index(Request $request): View
    {
        $recentTables = json_decode($request->cookie(self::RECENT_TABLES_COOKIE, '[]'), true) ?: [];

        return view('home', [
            'recentTables' => $recentTables,
        ]);
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
