<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    /**
     * Lista todos los proyectos activos (no archivados) del usuario autenticado.
     * El ordenamiento y filtrado por categoría se delegan al frontend.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return ProjectResource::collection(collect());
        }

        $projects = $portfolio->projects()
            ->where('archived', false)
            ->with(['category', 'skills', 'files'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Crea un proyecto y asocia la categoría y skills seleccionadas.
     */
    public function store(ProjectRequest $request): ProjectResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found. Create your portfolio first.'], 404);
        }

        $validated = $request->validated();
        $skillIds  = $validated['skill_ids'] ?? [];
        unset($validated['skill_ids']);

        $project = $portfolio->projects()->create($validated);

        if (! empty($skillIds)) {
            $project->skills()->sync($skillIds);
        }

        return (new ProjectResource($project->refresh()->load(['category', 'skills', 'files'])))->response()->setStatusCode(201);
    }

    /**
     * Actualiza los datos del proyecto, su categoría y sincroniza sus skills.
     */
    public function update(ProjectRequest $request, Project $project): ProjectResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio || $project->portfolio_id !== $portfolio->id) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $validated = $request->validated();
        $skillIds  = array_key_exists('skill_ids', $validated) ? $validated['skill_ids'] : null;
        unset($validated['skill_ids']);

        $project->update($validated);

        if ($skillIds !== null) {
            $project->skills()->sync($skillIds);
        }

        return new ProjectResource($project->load(['category', 'skills', 'files']));
    }

    /**
     * Archiva el proyecto (soft delete). No se elimina físicamente.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio || $project->portfolio_id !== $portfolio->id) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        $project->update(['archived' => true]);

        return response()->json(['message' => 'Project archived successfully.']);
    }

    /**
     * Catálogo de skills técnicas disponibles para asociar a proyectos.
     * El frontend llama a este endpoint antes de abrir el modal de crear/editar.
     */
    public function skillsCatalog(): JsonResponse
    {
        $grouped = [];

        foreach (Skill::where('type', 'tecnica')
            ->orderBy('category')
            ->orderBy('name')
            ->get() as $skill) {
            $grouped[$skill->category][] = [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ];
        }

        return response()->json(['data' => $grouped]);
    }
}
