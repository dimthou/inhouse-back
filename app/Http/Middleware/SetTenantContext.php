<?php

namespace App\Http\Middleware;

use App\Modules\Tenant\Support\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || empty($user->tenant_id)) {
            return new JsonResponse([
                'message' => 'Tenant context is missing for the authenticated user.',
            ], 403);
        }

        app(TenantContext::class)->setTenantId($user->tenant_id);

        return $next($request);
    }
}
