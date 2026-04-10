<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Notifications\NewClientRegistered;
use App\Notifications\UserRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => $request->address,
            'role_id'  => 3,
            'active'   => true,
        ]);

        // Notificación de bienvenida al cliente
        $user->notify(new UserRegistered($user));

        // Notificar a recepcionistas y admin del nuevo registro
        User::whereIn('role_id', [1, 2])->where('active', true)->get()
            ->each(fn($u) => $u->notify(new NewClientRegistered($user)));

        return $this->success(
            new UserResource($user->load('role')),
            'Usuario registrado correctamente',
            201
        );
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error('Correo o contraseña incorrectos', 401);
        }

        if (!$user->active) {
            return $this->error('Tu cuenta está desactivada. Contacta al administrador.', 403);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Correo o contraseña incorrectos', 401);
        }

        $user  = Auth::user()->load('role');
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user'  => new UserResource($user),
            'role'  => $user->role_id,
            'token' => $token,
        ], 'Login exitoso');
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->success(null, 'Sesión cerrada correctamente');
    }

    public function me()
    {
        return $this->success(
            new UserResource(auth()->user()->load('role')),
            'Usuario autenticado'
        );
    }
}
