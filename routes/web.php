<?php

use App\Http\Controllers\Web\StatsAuthController;
use App\Http\Controllers\Web\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/stats/login', [StatsAuthController::class, 'create'])->name('stats.login');
Route::post('/stats/login', [StatsAuthController::class, 'store'])->name('stats.login.store');
Route::post('/stats/logout', [StatsAuthController::class, 'destroy'])->name('stats.logout');

Route::get('/stats', StatsController::class)->middleware('stats.auth')->name('stats.index');
