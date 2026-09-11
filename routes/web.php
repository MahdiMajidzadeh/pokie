<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LogoutController;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Admin\Tables as AdminTables;
use App\Livewire\Home;
use App\Livewire\Tables\Show as TableShow;
use App\Support\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

// AR-19: admin routes noindex; AR-3: 404 entirely when unconfigured;
// AR-21: HTTPS-only in production (see EnsureAdminEnabled).
Route::prefix('admin')->name('admin.')->middleware('admin.enabled')->group(function () {
    Route::get('login', AdminLogin::class)->name('login');

    Route::middleware('admin')->group(function () {
        Route::get('/', AdminTables::class)->name('tables');
        Route::post('logout', LogoutController::class)->name('logout');
    });
});

// requirement.md §4.2: both table routes render the same component, which
// derives viewer/manager/admin role itself from which hash(es) are present
// and whether an admin session is active (AR-11).
Route::middleware('noindex')->group(function () {
    Route::get('/{tableHash}', TableShow::class)
        ->where('tableHash', Hash::routePattern())
        ->name('table.view');

    Route::get('/{tableHash}/{managerHash}', TableShow::class)
        ->where(['tableHash' => Hash::routePattern(), 'managerHash' => Hash::routePattern()])
        ->name('table.manage');
});
