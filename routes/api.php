<?php

use App\Http\Controllers\PolicyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('policies')->group(function () {
    Route::get('/', [PolicyController::class, 'index']); // List all policies
    Route::post('/', [PolicyController::class, 'store']); // Create a new policy
    Route::get('/{id}', [PolicyController::class, 'show']); // Fetch a policy by ID
    Route::put('/{id}', [PolicyController::class, 'update']); // Update a policy
    Route::delete('/{id}', [PolicyController::class, 'destroy']); // Delete a policy
});
