<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'  => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Activity::with('causer:id,first_name,last_name,email')
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('causer_type', 'App\Models\User')
                  ->where('causer_id', $request->user_id);
        }

        $perPage = $request->input('per_page', 20);
        $logs    = $query->paginate($perPage);

        return response()->json($logs);
    }
}
