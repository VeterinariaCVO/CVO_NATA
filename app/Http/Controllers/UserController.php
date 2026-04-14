<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    // Admin ve todos los usuarios con su rol
    public function index()
    {
        $users = DB::select('SELECT u.*, r.name AS rol_nombre
                             FROM users u
                             JOIN roles r ON r.id = u.role_id
                             ORDER BY u.name');

        return $this->success($users);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $user = DB::select('SELECT u.*, r.name AS rol_nombre
                            FROM users u
                            JOIN roles r ON r.id = u.role_id
                            WHERE u.id = ?', [$id]);

        if (empty($user)) {
            return $this->error('Usuario no encontrado', 404);
        }

        return $this->success($user[0]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    // Admin crea cualquier usuario usando sp_crear_usuario
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6',
            'role_id'    => 'required|integer|exists:roles,id',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:255',
            'gender'     => 'nullable|in:masculino,femenino,otro',
            'birth_date' => 'nullable|date',
        ]);

        // Hasheamos el password antes de enviarlo al SP
        $passwordHash = Hash::make($request->password);

        // sp_crear_usuario valida que el email no esté duplicado y que el rol exista
        $resultado = DB::select('CALL sp_crear_usuario(?, ?, ?, ?, ?, ?, ?, ?)', [
            $request->name,
            $request->email,
            $passwordHash,
            $request->role_id,
            $request->phone,
            $request->address,
            $request->gender,
            $request->birth_date,
        ]);

        return $this->success($resultado[0], 'Usuario creado correctamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    // Admin edita/activa/desactiva/elimina usando sp_gestionar_usuario
    public function update(Request $request, $id)
    {
        $request->validate([
            'accion'     => 'required|in:editar,activar,desactivar',
            'name'       => 'nullable|string|max:255',
            'email'      => 'nullable|email',
            'role_id'    => 'nullable|integer|exists:roles,id',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:255',
            'gender'     => 'nullable|in:masculino,femenino,otro',
            'birth_date' => 'nullable|date',
            'active'     => 'nullable|boolean',
        ]);

        // sp_gestionar_usuario usa COALESCE: si mandas NULL en un campo, no lo cambia
        $resultado = DB::select('CALL sp_gestionar_usuario(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $request->accion,
            $id,
            $request->name,
            $request->email,
            $request->role_id,
            $request->phone,
            $request->address,
            $request->gender,
            $request->birth_date,
            $request->active,
        ]);

        return $this->success($resultado[0], 'Usuario actualizado correctamente');
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_usuario(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'eliminar',
            $id,
            null, null, null, null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Usuario eliminado correctamente');
    }

    // ─── CLIENTES ─────────────────────────────────────────────────────────────
    // Lista solo usuarios con role_id = 3
    public function clients()
    {
        $clients = DB::select('SELECT u.*, r.name AS rol_nombre
                               FROM users u
                               JOIN roles r ON r.id = u.role_id
                               WHERE u.role_id = 3
                               ORDER BY u.name');

        return $this->success($clients);
    }

    public function showClient($id)
    {
        $client = DB::select('SELECT u.*, r.name AS rol_nombre
                              FROM users u
                              JOIN roles r ON r.id = u.role_id
                              WHERE u.id = ? AND u.role_id = 3', [$id]);

        if (empty($client)) {
            return $this->error('Cliente no encontrado', 404);
        }

        return $this->success($client[0]);
    }

    // ─── EMPLEADOS ────────────────────────────────────────────────────────────
    // Lista empleados (role_id 2) y veterinarios (role_id 4)
    public function employees()
    {
        $employees = DB::select('SELECT u.*, r.name AS rol_nombre
                                 FROM users u
                                 JOIN roles r ON r.id = u.role_id
                                 WHERE u.role_id IN (2, 4)
                                 ORDER BY u.name');

        return $this->success($employees);
    }

    public function showEmployee($id)
    {
        $employee = DB::select('SELECT u.*, r.name AS rol_nombre
                                FROM users u
                                JOIN roles r ON r.id = u.role_id
                                WHERE u.id = ? AND u.role_id IN (2, 4)', [$id]);

        if (empty($employee)) {
            return $this->error('Empleado no encontrado', 404);
        }

        return $this->success($employee[0]);
    }
}
