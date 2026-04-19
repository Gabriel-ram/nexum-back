<?php

use App\Http\Controllers\Api\V1\WorkExperienceController;
use App\Http\Controllers\Api\V1\ActivityLogController;
use App\Http\Controllers\Api\V1\Admin\ProjectCategoryController as AdminProjectCategoryController;
use App\Http\Controllers\Api\V1\AdminSkillController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CertificationController;
use App\Http\Controllers\Api\V1\FeaturedProfilesController;
use App\Http\Controllers\Api\V1\ProjectCategoryController;
use App\Http\Controllers\Api\V1\ProjectFileController;
use App\Http\Controllers\Api\V1\SkillController;
use App\Http\Controllers\Api\V1\PortfolioController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public: featured profiles for landing page
    Route::get('/featured-profiles', [FeaturedProfilesController::class, 'index']);

    // Public: categorías de proyecto (para el selector del modal de creación de proyectos)
    Route::get('/project-categories', [ProjectCategoryController::class, 'index']);

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
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus']);
        Route::get('/activity-log', [ActivityLogController::class, 'index']);

        // Gestión del catálogo de skills
        Route::get('/skills/categories', [AdminSkillController::class, 'categories']);
        Route::get('/skills', [AdminSkillController::class, 'index']);
        Route::post('/skills', [AdminSkillController::class, 'store']);

        // Gestión de categorías de proyecto
        Route::post('/project-categories', [ProjectCategoryController::class, 'store']);
        Route::get('/project-categories', [AdminProjectCategoryController::class, 'index']);
        Route::patch('/project-categories/{category}', [AdminProjectCategoryController::class, 'update']);
        Route::patch('/project-categories/{category}/toggle-status', [AdminProjectCategoryController::class, 'toggleStatus']);
    });

    // HU-7 + HU-8: Portfolio del usuario autenticado
    Route::prefix('portfolio')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [PortfolioController::class, 'show']);
        Route::put('/', [PortfolioController::class, 'update']);
        Route::post('/avatar', [PortfolioController::class, 'updateAvatar']);

        // HU-4: Habilidades del portfolio
        Route::prefix('skills')->group(function () {
            Route::get('/', [SkillController::class, 'index']);
            Route::post('/', [SkillController::class, 'store']);
            Route::put('/{portfolioSkill}', [SkillController::class, 'update']);
            Route::delete('/{portfolioSkill}', [SkillController::class, 'destroy']);
        });

        // HU-11: Certificaciones
        Route::prefix('certifications')->group(function () {
            Route::get('/', [CertificationController::class, 'index']);
            Route::post('/', [CertificationController::class, 'store']);
            Route::put('/{certification}', [CertificationController::class, 'update']);
            Route::post('/{certification}/image', [CertificationController::class, 'updateImage']);
            Route::delete('/{certification}', [CertificationController::class, 'destroy']);
        });
    });

    // HU-9: Gestión de proyectos
    Route::middleware('auth:sanctum')->group(function () {
        // Catálogo de skills del portfolio (para agregar habilidades al perfil)
        Route::get('/skills/catalog', [SkillController::class, 'catalog']);

        // Skills técnicas disponibles para asociar a proyectos
        // Debe ir antes de /projects/{project} para que 'skills' no se tome como ID
        Route::get('/projects/skills', [ProjectController::class, 'skillsCatalog']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

        // Archivos (imágenes y PDFs) de un proyecto
        Route::get('/projects/{project}/files', [ProjectFileController::class, 'index']);
        Route::post('/projects/{project}/files', [ProjectFileController::class, 'store']);
        Route::delete('/projects/{project}/files/{file}', [ProjectFileController::class, 'destroy']);

        // HU-12: Experiencia laboral
        Route::get('/work-experiences', [WorkExperienceController::class, 'index']);
        Route::post('/work-experiences', [WorkExperienceController::class, 'store']);
        Route::put('/work-experiences/{id}', [WorkExperienceController::class, 'update']);
        Route::delete('/work-experiences/{id}', [WorkExperienceController::class, 'destroy']);
    });

});
