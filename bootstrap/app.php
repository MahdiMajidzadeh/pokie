<?php

use App\Http\Middleware\EnsureAdminEnabled;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\NoIndex;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.enabled' => EnsureAdminEnabled::class,
            'admin' => EnsureSuperAdmin::class,
            'noindex' => NoIndex::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // An abort(404)/403 thrown inside a route throws past the NoIndex/
        // EnsureAdminEnabled middleware's own "after" code (an exception
        // unwinds the call stack instead of returning through it), so every
        // error response is covered here instead — the whole app avoids
        // search engine exposure (requirement.md §4.2/§4.4 AR-19).
        $exceptions->respond(function ($response) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

            return $response;
        });
    })->create();
