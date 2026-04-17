<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectCategoryRequest;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;

class ProjectCategoryController extends Controller
{
    /**
     * Lista todas las categorías de proyecto disponibles.
     * Usado por el frontend para poblar filtros y el selector del modal.
     */
    public function index(): JsonResponse
    {
        $categories = ProjectCategory::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $categories]);
    }

    /**
     * Crea una nueva categoría de proyecto (solo admin).
     */
    public function store(StoreProjectCategoryRequest $request): JsonResponse
    {
        $category = ProjectCategory::create($request->validated());

        return response()->json([
            'message' => 'Project category created successfully.',
            'data'    => ['id' => $category->id, 'name' => $category->name],
        ], 201);
    }
}
