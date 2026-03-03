<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::with('permissions')->withCount('users')->get();
        return RoleResource::collection($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(RoleRequest $request): RoleResource
    {
        $role = Role::create($request->validated());

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');
            $role->permissions()->sync($permissions);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions', 'users'));
    }

    /**
     * Update the specified role.
     */
    public function update(RoleRequest $request, Role $role): RoleResource
    {
        $role->update($request->validated());

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');
            $role->permissions()->sync($permissions);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Prevent deletion of critical roles
        if (in_array($role->slug, ['admin', 'manager', 'staff', 'viewer'])) {
            return response()->json([
                'message' => 'Cannot delete system roles'
            ], 403);
        }

        $role->delete();
        return response()->json(null, 204);
    }

    /**
     * Assign permissions to role.
     */
    public function assignPermissions(Request $request, Role $role): RoleResource
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,slug'
        ]);

        $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');
        $role->permissions()->syncWithoutDetaching($permissions);

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Revoke permissions from role.
     */
    public function revokePermissions(Request $request, Role $role): RoleResource
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,slug'
        ]);

        $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');
        $role->permissions()->detach($permissions);

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Sync permissions to role (replace all).
     */
    public function syncPermissions(Request $request, Role $role): RoleResource
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,slug'
        ]);

        $permissions = Permission::whereIn('slug', $request->permissions)->pluck('id');
        $role->permissions()->sync($permissions);

        return new RoleResource($role->load('permissions'));
    }
}
