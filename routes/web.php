<?php

declare(strict_types=1);

use App\Livewire\Home;
use App\Livewire\Superadmin;
use App\Livewire\Tables;
use Illuminate\Support\Facades\Route;

Route::get('/', Home\Index::class)->name('home');

Route::get('/t/{token}', Tables\Show::class)->name('table.show');
Route::get('/t/{token}/{managerToken}', Tables\Show::class)->name('table.manager');

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('login', Superadmin\Login::class)->name('login');
    Route::get('/', Superadmin\Dashboard::class)->name('dashboard')->middleware('superadmin');
});
