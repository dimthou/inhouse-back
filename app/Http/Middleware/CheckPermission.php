<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user || !$user->hasPermission($permission)) {
            $messages = [
                'inventory.create' => 'You do not have permission to create inventory items.',
                'inventory.edit' => 'You do not have permission to update inventory items.',
                'inventory.delete' => 'You do not have permission to delete inventory items.',
            ];

            return response()->json([
                'message' => $messages[$permission] ?? 'Forbidden. Missing required permission.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
