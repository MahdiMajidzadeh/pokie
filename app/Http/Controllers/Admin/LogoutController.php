<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminSession;
use Illuminate\Http\RedirectResponse;

class LogoutController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        AdminSession::logout();

        return redirect()->route('home');
    }
}
