<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Notifications\UserRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class  ApiAuthController extends Controller
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

        $user->notify(new UserRegistered($user));

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

        public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'email'         => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'         => 'sometimes|nullable|string|max:20',
            'gender'        => 'sometimes|nullable|in:masculino,femenino,otro',
            'birth_date'    => 'sometimes|nullable|date',
            'address'       => 'sometimes|nullable|string|max:255',
            'profile_photo' => 'sometimes|nullable|image|max:2048',
        ]);

        // Manejo de foto si se sube
        if ($request->hasFile('profile_photo')) {
            // Eliminar foto anterior si existe
            if ($user->profile_photo) {
                Storage::delete($user->profile_photo);
            }
            $validated['profile_photo'] = $request->file('profile_photo')
                                                   ->store('profile-photos', 'public');
        }

        $user->update($validated);

        return $this->success(
            new UserResource($user->load('role')),
            'Perfil actualizado correctamente'
        );
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('La contraseña actual es incorrecta.', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Contraseña actualizada correctamente');
    }
}
