<?php

declare(strict_types=1);

use App\Http\Middleware\SuperadminMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('redirects to login when unauthenticated', function () {
    Route::get('/test-protected', fn () => 'ok')->middleware('superadmin');

    $response = $this->get('/test-protected');
    $response->assertRedirect(route('superadmin.login'));
    $response->assertSessionHas('error');
});

it('allows request when authenticated', function () {
    Route::get('/test-protected', fn () => 'ok')->middleware('superadmin');

    $this->withSession(['superadmin' => true]);

    $response = $this->get('/test-protected');
    $response->assertStatus(200);
    $response->assertSee('ok');
});
