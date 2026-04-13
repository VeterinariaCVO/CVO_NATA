<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Acceso Denegado');
        }

        $rolesPermitidos = array_map('intval', explode(',', $role));

        if (!in_array($user->role_id, $rolesPermitidos)) {
            abort(403, 'Acceso Denegado');
        }

        return $next($request);
    }
}
