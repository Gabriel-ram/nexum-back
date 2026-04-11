<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\JsonResponse;

class FeaturedProfilesController extends Controller
{
    private const FEATURED_EMAILS = [
        'ana.garcia@portfolio.test',
        'carlos.mendez@portfolio.test',
        'sofia.romero@portfolio.test',
    ];

    public function index(): JsonResponse
    {
        $users = User::with('portfolio')
            ->whereIn('email', self::FEATURED_EMAILS)
            ->get();

        $profiles = $users->map(function (User $user) {
            $portfolio = $user->portfolio;

            return [
                'first_name'     => $user->first_name,
                'last_name'      => $user->last_name,
                'location'       => $portfolio?->location,
                'avatar_url'     => $portfolio?->avatar_path
                    ? Cloudinary::image($portfolio->avatar_path)->toUrl()
                    : null,
                'projects_count' => 0,
            ];
        });

        return response()->json(['data' => $profiles]);
    }
}
