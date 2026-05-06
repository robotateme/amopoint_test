<?php

use App\Http\Controllers\JokeController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/jokes', [JokeController::class, 'index']);

Route::options('/api/visits', [VisitController::class, 'options']);
Route::post('/api/visits', [VisitController::class, 'store']);

Route::get('/stats', StatsController::class)->middleware('stats.auth');
