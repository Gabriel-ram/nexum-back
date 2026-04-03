<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Http\Requests\PortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function show(Request $request): PortfolioResource|JsonResponse
    {
        $portfolio = $request->user()->portfolio;

        if (! $portfolio) {
            return response()->json(['message' => 'Portfolio not found.'], 404);
        }

        return new PortfolioResource($portfolio->load('user'));
    }

    public function update(PortfolioRequest $request): PortfolioResource
    {
        $validated = $request->validated();

        $userFields = collect($validated)->only(['first_name', 'last_name'])->all();
        if (! empty($userFields)) {
            $request->user()->update($userFields);
        }

        $portfolioFields = collect($validated)->except(['first_name', 'last_name'])->all();

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id],
            $portfolioFields
        );

        return new PortfolioResource($portfolio->load('user'));
    }

    public function updateAvatar(AvatarRequest $request): PortfolioResource
    {
        $portfolio = $request->user()->portfolio;

        if ($portfolio?->avatar_path) {
            Storage::disk('public')->delete($portfolio->avatar_path);
        }

        $avatarPath = $request->file('avatar')->store('avatars', 'public');

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['avatar_path' => $avatarPath]
        );

        return new PortfolioResource($portfolio->load('user'));
    }
}
