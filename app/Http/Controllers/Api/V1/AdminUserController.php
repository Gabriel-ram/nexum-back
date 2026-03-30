<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function toggleStatus(User $user): JsonResponse
    {
        // Prevenir que el admin se desactive a sí mismo
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'You cannot change the status of your own account.'], 422);
        }

        if ($user->is_active) {
            $user->update([
                'is_active'            => false,
                'deactivated_by_admin' => true,
            ]);

            // Revocar todos los tokens activos
            $user->tokens()->delete();

            return response()->json([
                'message' => 'User deactivated successfully.',
                'user'    => ['id' => $user->id, 'email' => $user->email, 'is_active' => false],
            ]);
        }

        $user->update([
            'is_active'            => true,
            'deactivated_by_admin' => false,
        ]);

        return response()->json([
            'message' => 'User reactivated successfully.',
            'user'    => ['id' => $user->id, 'email' => $user->email, 'is_active' => true],
        ]);
    }
}
