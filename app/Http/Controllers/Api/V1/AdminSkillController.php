<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminSkillRequest;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSkillController extends Controller
{
    /**
     * Lista todas las skills del catálogo, paginadas.
     * Soporta filtro por type y/o category.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = Skill::orderBy('type')->orderBy('category')->orderBy('name');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $skills = $query->paginate($perPage);

        return response()->json([
            'data' => $skills->map(fn (Skill $skill) => [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ]),
            'meta' => [
                'current_page' => $skills->currentPage(),
                'per_page'     => $skills->perPage(),
                'total'        => $skills->total(),
            ],
        ]);
    }

    /**
     * Agrega una nueva skill al catálogo dentro de una categoría existente.
     * El admin solo provee el nombre; el type se hereda de la categoría seleccionada.
     * No se permiten nuevas categorías.
     */
    public function store(StoreAdminSkillRequest $request): JsonResponse
    {
        // Derivar el type de la categoría existente (la categoría siempre tiene un único type)
        $type = Skill::where('category', $request->validated('category'))->value('type');

        $skill = Skill::create([
            'name'     => $request->validated('name'),
            'type'     => $type,
            'category' => $request->validated('category'),
        ]);

        return response()->json([
            'message' => 'Skill created successfully.',
            'data'    => [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ],
        ], 201);
    }

    /**
     * Devuelve las categorías disponibles para seleccionar al crear una skill.
     * El frontend usa esto para poblar el dropdown del formulario.
     */
    public function categories(): JsonResponse
    {
        $categories = Skill::select('type', 'category')
            ->distinct()
            ->orderBy('type')
            ->orderBy('category')
            ->get()
            ->map(fn ($s) => [
                'type'     => $s->type,
                'category' => $s->category,
            ]);

        return response()->json(['data' => $categories]);
    }
}
