<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->get('superadmin')) {
            return redirect()->route('superadmin.login')->with('error', 'Please log in as superadmin.');
        }

        return $next($request);
    }
}
