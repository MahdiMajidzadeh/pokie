<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\AdminSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AR-3: if the admin env vars aren't configured, `/admin/*` doesn't exist —
 * a 404, never a login form that reveals the feature exists. AR-21: admin
 * routes require HTTPS in production, rejected outright (not redirected)
 * since credentials are being posted. AR-19: noindex.
 */
class EnsureAdminEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminSession::enabled()) {
            abort(404);
        }

        if (app()->isProduction() && ! $request->secure()) {
            abort(404);
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
