<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/tables', [HomeController::class, 'store'])->name('tables.store');

Route::get('/t/{token}', [TableController::class, 'show'])->name('table.show');
Route::get('/t/{token}/{manager_token}', [TableController::class, 'showManager'])->name('table.manager');

Route::post('/t/{token}/{manager_token}/players', [TableController::class, 'storePlayer'])->name('table.players.store');
Route::post('/t/{token}/{manager_token}/buy-ins', [TableController::class, 'storeBuyIn'])->name('table.buy-ins.store');
Route::post('/t/{token}/{manager_token}/paybacks', [TableController::class, 'storePayback'])->name('table.paybacks.store');
