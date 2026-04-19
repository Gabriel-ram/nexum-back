<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkExperienceRequest;
use App\Http\Resources\WorkExperienceResource;
use App\Models\WorkExperience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkExperienceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $experiences = WorkExperience::where('user_id', $request->user()->id)
            ->with('skills')
            ->orderByDesc('start_date')
            ->get();

        return WorkExperienceResource::collection($experiences);
    }

    public function store(WorkExperienceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $skillIds  = $validated['skill_ids'] ?? [];
        unset($validated['skill_ids']);

        $validated['start_date'] = $validated['start_date'] . '-01';
        if (! empty($validated['end_date'])) {
            $validated['end_date'] = $validated['end_date'] . '-01';
        }

        $experience = WorkExperience::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        if (! empty($skillIds)) {
            $experience->skills()->sync($skillIds);
        }

        return (new WorkExperienceResource($experience->load('skills')))->response()->setStatusCode(201);
    }

    public function update(WorkExperienceRequest $request, $id): WorkExperienceResource|JsonResponse
    {
        $experience = WorkExperience::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $experience) {
            return response()->json(['message' => 'Work experience not found.'], 404);
        }

        $validated = $request->validated();
        $skillIds  = array_key_exists('skill_ids', $validated) ? $validated['skill_ids'] : null;
        unset($validated['skill_ids']);

        $validated['start_date'] = $validated['start_date'] . '-01';
        if (array_key_exists('end_date', $validated)) {
            $validated['end_date'] = $validated['end_date'] ? $validated['end_date'] . '-01' : null;
        }

        $experience->update($validated);

        if ($skillIds !== null) {
            $experience->skills()->sync($skillIds);
        }

        return new WorkExperienceResource($experience->load('skills'));
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $experience = WorkExperience::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $experience) {
            return response()->json(['message' => 'Work experience not found.'], 404);
        }

        $experience->delete();

        return response()->json(['message' => 'Work experience deleted successfully.']);
    }
}
