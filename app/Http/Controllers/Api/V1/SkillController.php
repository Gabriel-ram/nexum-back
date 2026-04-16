<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\PortfolioSkill;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Returns the authenticated user's active skills grouped by type and category.
     */
    public function index(Request $request): JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['data' => ['tecnica' => (object) [], 'blanda' => (object) []]]);
        }

        $query = PortfolioSkill::with('skill')
            ->where('portfolio_id', $portfolio->id);

        if ($request->boolean('include_inactive')) {
            $query->where('is_active', false);
        } else {
            $query->where('is_active', true);
        }

        $grouped = ['tecnica' => [], 'blanda' => []];

        foreach ($query->get() as $portfolioSkill) {
            $type     = $portfolioSkill->skill->type;
            $category = $portfolioSkill->skill->category;

            $grouped[$type][$category][] = new SkillResource($portfolioSkill);
        }

        return response()->json(['data' => $grouped]);
    }

    /**
     * Returns the full skill catalog grouped by type and category (no user data).
     */
    public function catalog(): JsonResponse
    {
        $grouped = ['tecnica' => [], 'blanda' => []];

        foreach (Skill::orderBy('category')->orderBy('name')->get() as $skill) {
            $grouped[$skill->type][$skill->category][] = [
                'id'       => $skill->id,
                'name'     => $skill->name,
                'type'     => $skill->type,
                'category' => $skill->category,
            ];
        }

        return response()->json(['data' => $grouped]);
    }

    /**
     * Adds a skill to the user's portfolio.
     * If the skill was previously deactivated, it reactivates it instead of creating a duplicate.
     */
    public function store(StoreSkillRequest $request): SkillResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found. Create your portfolio first.'], 404);
        }

        $validated = $request->validated();
        $skill     = Skill::find($validated['skill_id']);

        $existing = PortfolioSkill::where('portfolio_id', $portfolio->id)
            ->where('skill_id', $skill->id)
            ->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json(['message' => 'This skill is already in your profile.'], 422);
            }

            // Reactivate previously deactivated skill
            $existing->update([
                'is_active' => true,
                'level'     => $skill->type === 'tecnica' ? $validated['level'] : null,
            ]);

            return new SkillResource($existing->fresh()->load('skill'));
        }

        $portfolioSkill = PortfolioSkill::create([
            'portfolio_id' => $portfolio->id,
            'skill_id'     => $skill->id,
            'level'        => $skill->type === 'tecnica' ? $validated['level'] : null,
            'is_active'    => true,
        ]);

        return (new SkillResource($portfolioSkill->load('skill')))->response()->setStatusCode(201);
    }

    /**
     * Updates the level of a technical skill. Soft skills cannot be edited.
     */
    public function update(UpdateSkillRequest $request, PortfolioSkill $portfolioSkill): SkillResource|JsonResponse
    {
        if ($portfolioSkill->portfolio_id !== $request->user()->portfolio?->id) {
            abort(403);
        }

        if (! $portfolioSkill->is_active) {
            abort(422, 'Cannot edit an inactive skill.');
        }

        if ($portfolioSkill->skill->type === 'blanda') {
            abort(422, 'Las habilidades blandas no tienen nivel y no se pueden editar.');
        }

        $portfolioSkill->update(['level' => $request->validated()['level']]);

        return new SkillResource($portfolioSkill->fresh()->load('skill'));
    }

    /**
     * Soft-deletes a skill from the user's portfolio (marks as inactive).
     */
    public function destroy(Request $request, PortfolioSkill $portfolioSkill): SkillResource|JsonResponse
    {
        if ($portfolioSkill->portfolio_id !== $request->user()->portfolio?->id) {
            abort(403);
        }

        $portfolioSkill->update(['is_active' => false]);

        return new SkillResource($portfolioSkill->fresh()->load('skill'));
    }
}
