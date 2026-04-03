<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource|JsonResponse
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        return new ProfileResource($profile->load('user'));
    }

    public function update(ProfileRequest $request): ProfileResource
    {
        $validated = $request->validated();

        $userFields = collect($validated)->only(['first_name', 'last_name'])->all();
        if (! empty($userFields)) {
            $request->user()->update($userFields);
        }

        $profileFields = collect($validated)->except(['first_name', 'last_name'])->all();
        $profile = Profile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $profileFields
        );

        return new ProfileResource($profile->load('user'));
    }

}
