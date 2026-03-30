<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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
        $profile = Profile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );

        return new ProfileResource($profile->load('user'));
    }

    public function deactivate(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update(['is_active' => false]);

        // Poner el portfolio en privado si existe
        if ($user->portfolio) {
            $user->portfolio->update(['global_privacy' => 'private']);
        }

        // Revocar todos los tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Your account has been deactivated.']);
    }

    public function reactivate(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->is_active) {
            return response()->json(['message' => 'Account is already active.'], 422);
        }

        if ($user->deactivated_by_admin) {
            return response()->json(['message' => 'Tu cuenta fue desactivada. Contactá al administrador.'], 403);
        }

        $user->update(['is_active' => true]);

        // Restaurar privacidad del portfolio
        if ($user->portfolio) {
            $user->portfolio->update(['global_privacy' => 'public']);
        }

        return response()->json(['message' => 'Your account has been reactivated. You can now log in.']);
    }
}
