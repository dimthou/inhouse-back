<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(): AnonymousResourceCollection
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->slug)[0];
        });

        return PermissionResource::collection($permissions->flatten());
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): PermissionResource
    {
        return new PermissionResource($permission->load('roles'));
    }

    /**
     * Get permissions grouped by module.
     */
    public function grouped(): JsonResponse
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->slug)[0];
        });

        $grouped = [];
        foreach ($permissions as $module => $perms) {
            $grouped[$module] = PermissionResource::collection($perms);
        }

        return response()->json(['data' => $grouped]);
    }
}
