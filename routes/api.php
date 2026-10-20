<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CatatanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CatatanController::class, 'index']);

    Route::get('/posts', [CatatanController::class, 'index']);
    Route::post('/posts', [CatatanController::class, 'store']);
    Route::get('/posts/{id}', [CatatanController::class, 'show']);
});
