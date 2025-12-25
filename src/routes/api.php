<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TvController;
use App\Http\Controllers\Api\TvOptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/openapi.yaml', function () {
    return response()->file(base_path('openapi.yaml'), [
        'Content-Type' => 'application/yaml',
    ]);
});

Route::get('/tv-options', [TvOptionController::class, 'index']);
Route::get('/tv-options/{tvOption}', [TvOptionController::class, 'show']);

Route::get('/tvs', [TvController::class, 'index']);
Route::post('/tvs', [TvController::class, 'store']);
Route::get('/tvs/{tv}', [TvController::class, 'show']);
Route::put('/tvs/{tv}', [TvController::class, 'update']);
Route::patch('/tvs/{tv}', [TvController::class, 'update']);
Route::delete('/tvs/{tv}', [TvController::class, 'destroy']);
