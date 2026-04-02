<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Actions\ResolveIntegrationUser;
use App\Domain\Integration\Models\IntegrationApp;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SRS.md INT-F-005, INT-F-008
 */
class IntegrationUserController extends Controller
{
    public function __construct(
        private readonly ResolveIntegrationUser $resolveIntegrationUser,
    ) {}

    /**
     * Resolve the currently authenticated user via session (same-origin).
     */
    public function me(Request $request): JsonResponse
    {
        /** @var IntegrationApp $app */
        $app = $request->attributes->get('integration_app');
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'No authenticated user'], 401);
        }

        $data = $this->resolveIntegrationUser->execute($user, $app);

        if ($data === null) {
            return response()->json(['error' => 'Insufficient scopes'], 403);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Resolve a user by ID or email (server-to-server).
     */
    public function resolve(Request $request): JsonResponse
    {
        /** @var IntegrationApp $app */
        $app = $request->attributes->get('integration_app');

        $request->validate([
            'user_id' => ['required_without:email', 'nullable', 'integer'],
            'email' => ['required_without:user_id', 'nullable', 'email'],
        ]);

        $user = null;

        if ($request->filled('user_id')) {
            $user = User::find($request->integer('user_id'));
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->string('email'))->first();
        }

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $data = $this->resolveIntegrationUser->execute($user, $app);

        if ($data === null) {
            return response()->json(['error' => 'Insufficient scopes'], 403);
        }

        return response()->json(['data' => $data]);
    }
}
