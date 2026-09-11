<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AR-8: gates every /admin/* route (other than the login form itself) on an
 * active admin session.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminSession::isActive()) {
            return redirect()->route('admin.login');
        }

        AdminSession::touch();

        return $next($request);
    }
}
