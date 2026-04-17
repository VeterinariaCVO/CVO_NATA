<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
{
    $user = auth()->user();

    if (!$user || !in_array($user->role_id, array_map('intval', $roles))) {
        abort(403, 'Acceso Denegado');
    }

    return $next($request);
}
}
