<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProjectCategoryRequest;
use App\Http\Resources\Admin\ProjectCategoryAdminResource;
use App\Models\ProjectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 5), 100);

        $query = ProjectCategory::orderBy('name');

        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->input('search')) . '%']);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return ProjectCategoryAdminResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function update(UpdateProjectCategoryRequest $request, ProjectCategory $category): ProjectCategoryAdminResource
    {
        $category->update(['name' => $request->validated('name')]);

        return new ProjectCategoryAdminResource($category->fresh());
    }

    public function toggleStatus(ProjectCategory $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Category {$status} successfully.",
            'data'    => new ProjectCategoryAdminResource($category),
        ]);
    }
}
