<?php

use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {

        // HU-1: Registro y verificación de email
        Route::post('/register', [AuthController::class, 'register']);

        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');

        // HU-2: Login y logout
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login');

        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');

        // HU-3: Recuperación de contraseña
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:6,1');

        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    });

    // HU-5A + HU-6: Rutas de administración
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        Route::get('/activity-log', [ActivityLogController::class, 'index']);
    });

    // HU-7 + HU-8: Perfil del usuario autenticado
    Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });

});
