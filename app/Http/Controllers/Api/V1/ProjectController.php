<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return ProjectResource::collection(collect());
        }

        $query = $portfolio->projects()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $sortField = in_array($request->input('sort_by'), ['title', 'created_at'])
            ? $request->input('sort_by')
            : 'created_at';

        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $projects = $query->orderBy($sortField, $sortDir)->get();

        return ProjectResource::collection($projects);
    }

    public function store(ProjectRequest $request): ProjectResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found. Create your portfolio first.'], 404);
        }

        $project = $portfolio->projects()->create($request->validated());

        return new ProjectResource($project->load('category'));
    }

    public function update(ProjectRequest $request, Project $project): ProjectResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio || $project->portfolio_id !== $portfolio->id) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $project->update($request->validated());

        return new ProjectResource($project->load('category'));
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio || $project->portfolio_id !== $portfolio->id) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }
}
