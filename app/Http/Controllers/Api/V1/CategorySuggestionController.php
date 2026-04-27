<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategorySuggestionRequest;
use App\Http\Resources\CategorySuggestionResource;
use App\Models\CategorySuggestion;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class CategorySuggestionController extends Controller
{
    public function store(StoreCategorySuggestionRequest $request, Project $project): CategorySuggestionResource|JsonResponse
    {
        if ($project->portfolio->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $suggestion = CategorySuggestion::create([
            'user_id'       => $request->user()->id,
            'project_id'    => $project->id,
            'name'          => $request->validated()['name'],
            'justification' => $request->validated()['justification'] ?? null,
            'status'        => 'pending',
        ]);

        return (new CategorySuggestionResource($suggestion))->response()->setStatusCode(201);
    }
}
