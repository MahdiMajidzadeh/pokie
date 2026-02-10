<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/tables', [HomeController::class, 'store'])->name('tables.store');

Route::get('/t/{token}', [TableController::class, 'show'])->name('table.show');
Route::get('/t/{token}/{manager_token}', [TableController::class, 'showManager'])->name('table.manager');

Route::post('/t/{token}/{manager_token}/players', [TableController::class, 'storePlayer'])->name('table.players.store');
Route::post('/t/{token}/{manager_token}/buy-ins', [TableController::class, 'storeBuyIn'])->name('table.buy-ins.store');
Route::post('/t/{token}/{manager_token}/paybacks', [TableController::class, 'storePayback'])->name('table.paybacks.store');
Route::post('/t/{token}/{manager_token}/settlements', [TableController::class, 'storeSettlement'])->name('table.settlements.store');
Route::delete('/t/{token}/{manager_token}/buy-ins/{id}', [TableController::class, 'destroyBuyIn'])->name('table.buy-ins.destroy');
Route::delete('/t/{token}/{manager_token}/paybacks/{id}', [TableController::class, 'destroyPayback'])->name('table.paybacks.destroy');
Route::delete('/t/{token}/{manager_token}/settlements/{id}', [TableController::class, 'destroySettlement'])->name('table.settlements.destroy');

Route::prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('login', [SuperadminController::class, 'showLogin'])->name('login');
    Route::post('login', [SuperadminController::class, 'login']);
    Route::post('logout', [SuperadminController::class, 'logout'])->name('logout')->middleware('superadmin');
    Route::get('/', [SuperadminController::class, 'dashboard'])->name('dashboard')->middleware('superadmin');
});