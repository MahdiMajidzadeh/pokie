<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuperadminController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (session()->get('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        }

        $passwordConfigured = filled(config('superadmin.password'));

        return view('superadmin.login', ['passwordConfigured' => $passwordConfigured]);
    }

    public function login(Request $request): RedirectResponse
    {
        $password = config('superadmin.password');
        if (! filled($password)) {
            return redirect()->route('superadmin.login')->with('error', 'Superadmin is not configured.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! hash_equals((string) $password, $validated['password'])) {
            return redirect()->route('superadmin.login')->with('error', 'Invalid password.');
        }

        session()->put('superadmin', true);

        return redirect()->intended(route('superadmin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        session()->forget('superadmin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login')->with('success', 'Logged out.');
    }

    public function dashboard(): View
    {
        $tables = Table::query()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.dashboard', ['tables' => $tables]);
    }
}
