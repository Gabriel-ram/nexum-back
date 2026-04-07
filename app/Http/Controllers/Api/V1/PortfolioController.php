<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Http\Requests\PortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            cloudinary()->uploadApi()->destroy($portfolio->avatar_path);
        }

        $result = cloudinary()->uploadApi()->upload($request->file('avatar')->getRealPath(), [
            'folder' => 'nexun/avatars',
        ]);

        $portfolio = Portfolio::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['avatar_path' => $result['public_id']]
        );

        return new PortfolioResource($portfolio->load('user'));
    }
}
