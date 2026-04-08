<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
       $user = Auth::user();
       if(!$user || $user->role_id != (int)$role){
            abort(403, 'Acceso Denegado');
        }

        return $next($request);
    }
}
