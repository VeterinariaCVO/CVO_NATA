<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    use ApiResponse;

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    // Devuelve el perfil del usuario logueado
    public function show()
    {
        $user = DB::select(
            'SELECT u.*, r.name AS rol_nombre
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?',
            [Auth::id()]
        );

        return $this->success($user[0]);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    // CLIENTE → usa sp_editar_perfil_cliente
    // ADMIN/EMPLEADO → actualización directa (pueden cambiar más campos)
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'       => 'nullable|string|max:255',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:255',
            'gender'     => 'nullable|in:masculino,femenino,otro',
            'birth_date' => 'nullable|date',
        ]);

        if ($user->isCliente()) {
            // sp_editar_perfil_cliente valida que el email no esté en uso por otro usuario
            $resultado = DB::select('CALL sp_editar_perfil_cliente(?, ?, ?, ?, ?, ?, ?)', [
                $user->id,
                $request->name,
                $request->email,
                $request->phone,
                $request->address,
                $request->gender,
                $request->birth_date,
            ]);

            return $this->success($resultado[0], 'Perfil actualizado correctamente');
        }

        // Admin/Empleado/Veterinario → actualización directa con COALESCE manual
        DB::statement(
            'UPDATE users SET
                name       = COALESCE(?, name),
                email      = COALESCE(?, email),
                phone      = COALESCE(?, phone),
                address    = COALESCE(?, address),
                gender     = COALESCE(?, gender),
                birth_date = COALESCE(?, birth_date),
                updated_at = NOW()
             WHERE id = ?',
            [
                $request->name,
                $request->email,
                $request->phone,
                $request->address,
                $request->gender,
                $request->birth_date,
                $user->id,
            ]
        );

        return $this->success(null, 'Perfil actualizado correctamente');
    }

    // ─── CAMBIAR PASSWORD ─────────────────────────────────────────────────────
    public function changePassword(Request $request)
    {
        $request->validate([
            'password_actual' => 'required|string',
            'password_nuevo'  => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        // Verificar que el password actual sea correcto
        if (!Hash::check($request->password_actual, $user->password)) {
            return $this->error('El password actual es incorrecto', 400);
        }

        DB::statement(
            'UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?',
            [Hash::make($request->password_nuevo), $user->id]
        );

        return $this->success(null, 'Password actualizado correctamente');
    }

    // ─── DESTROY (eliminar cuenta propia) ────────────────────────────────────
    public function destroy()
    {
        $user = Auth::user();

        // Cerrar sesión primero
        $user->currentAccessToken()->delete();

        DB::statement('DELETE FROM users WHERE id = ?', [$user->id]);

        return $this->success(null, 'Cuenta eliminada correctamente');
    }
}
