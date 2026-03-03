<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::with('roles');

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->input('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): UserResource
    {
        $userData = $request->validated();
        $userData['password'] = Hash::make($userData['password']);

        $user = User::create($userData);

        // Assign default role if provided
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        } else {
            // Assign default 'viewer' role
            $user->assignRole('viewer');
        }

        return new UserResource($user->load('roles'));
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(UserRequest $request, User $user): UserResource
    {
        $userData = $request->validated();

        // Only update password if provided
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        $user->update($userData);

        return new UserResource($user->load('roles'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(AssignRoleRequest $request, User $user): UserResource
    {
        $user->syncRoles($request->roles);
        return new UserResource($user->load('roles'));
    }

    /**
     * Add a single role to user.
     */
    public function addRole(Request $request, User $user): UserResource
    {
        $request->validate([
            'role' => 'required|exists:roles,slug'
        ]);

        $user->assignRole($request->role);
        return new UserResource($user->load('roles'));
    }

    /**
     * Remove a role from user.
     */
    public function removeRole(Request $request, User $user): UserResource
    {
        $request->validate([
            'role' => 'required|exists:roles,slug'
        ]);

        $user->removeRole($request->role);
        return new UserResource($user->load('roles'));
    }

    /**
     * Get user's permissions.
     */
    public function permissions(User $user): JsonResponse
    {
        $permissions = $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->pluck('slug')
            ->values();

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'permissions' => $permissions
            ]
        ]);
    }

    /**
     * Get current authenticated user with roles and permissions.
     */
    public function me(Request $request): UserResource
    {
        $user = $request->user()->load('roles.permissions');
        return new UserResource($user);
    }
}
