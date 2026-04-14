<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    // Todos los roles usan vw_pets, pero con filtros distintos
    public function index(Request $request)
    {
        $user   = Auth::user();
        $where  = [];
        $params = [];

        // CLIENTE → solo ve sus propias mascotas activas
        if ($user->isCliente()) {
            $where[]  = 'dueno_id = ?';
            $params[] = $user->id;
            $where[]  = 'activa = 1';
        }

        // Filtros opcionales (Admin/Empleado)
        if ($request->filled('owner_id')) {
            $where[]  = 'dueno_id = ?';
            $params[] = $request->owner_id;
        }

        if ($request->filled('search')) {
            $where[]  = 'nombre LIKE ?';
            $params[] = '%' . $request->search . '%';
        }

        // Construimos el SQL final
        $sql = 'SELECT * FROM vw_pets';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nombre ASC';

        $mascotas = DB::select($sql, $params);
        return $this->success($mascotas);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $mascota = DB::select('SELECT * FROM vw_pets WHERE mascota_id = ?', [$id]);

        if (empty($mascota)) {
            return $this->error('Mascota no encontrada', 404);
        }

        return $this->success($mascota[0]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    // Admin/Empleado → sp_registrar_mascota (sin límite)
    // Cliente        → sp_registrar_mascota_cliente (límite 8 mascotas)
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'          => 'required|string|max:255',
            'species'       => 'required|string|max:100',
            'breed'         => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:100',
            'special_marks' => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'sex'           => 'nullable|in:male,female',
            'age'           => 'nullable|integer|min:0',
            'photo'         => 'nullable|image|max:2048',
            'owner_id'      => 'nullable|integer|exists:users,id',
        ]);

        // Guardar foto si viene (la ruta se pasa al SP)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pets', 'public');
        }

        if ($user->isCliente()) {
            // El SP valida que no tenga más de 8 mascotas activas
            $resultado = DB::select('CALL sp_registrar_mascota_cliente(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $request->name,
                $request->species,
                $request->breed,
                $request->color,
                $request->special_marks,
                $request->weight,
                $request->sex,
                $request->age,
                $user->id,              // owner_id siempre es el cliente logueado
            ]);
        } else {
            // Admin o Empleado pueden asignar cualquier owner_id
            $resultado = DB::select('CALL sp_registrar_mascota(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $request->name,
                $request->species,
                $request->breed,
                $request->color,
                $request->special_marks,
                $request->weight,
                $request->sex,
                $request->age,
                $photoPath,
                $request->owner_id,
            ]);
        }

        return $this->success($resultado[0], 'Mascota registrada exitosamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    // Admin/Empleado → sp_gestionar_mascota('editar')
    // Cliente        → sp_editar_mascota() (valida que sea su mascota)
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'name'          => 'nullable|string|max:255',
            'species'       => 'nullable|string|max:100',
            'breed'         => 'nullable|string|max:100',
            'color'         => 'nullable|string|max:100',
            'special_marks' => 'nullable|string|max:255',
            'weight'        => 'nullable|numeric|min:0',
            'sex'           => 'nullable|in:male,female',
            'age'           => 'nullable|integer|min:0',
            'photo'         => 'nullable|image|max:2048',
        ]);

        // Actualizar foto si viene nueva
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('pets', 'public');
        }

        if ($user->isCliente()) {
            // sp_editar_mascota valida que la mascota le pertenece al cliente
            $resultado = DB::select('CALL sp_editar_mascota(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $id,
                $request->name,
                $request->species,
                $request->breed,
                $request->color,
                $request->special_marks,
                $request->weight,
                $request->sex,
                $request->age,
                $user->id,           // p_cliente_id para validar ownership
            ]);
        } else {
            // Admin o Empleado pueden editar cualquier mascota
            $resultado = DB::select('CALL sp_gestionar_mascota(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                'editar',
                $id,
                $request->name,
                $request->species,
                $request->breed,
                $request->color,
                $request->special_marks,
                $request->weight,
                $request->sex,
                $request->age,
                $photoPath,
                null,               // active (no cambia en editar normal)
            ]);
        }

        return $this->success($resultado[0], 'Mascota actualizada exitosamente');
    }

    // ─── ACTIVAR / DESACTIVAR (solo Admin) ───────────────────────────────────
    public function toggleActive(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:activar,desactivar',
        ]);

        $resultado = DB::select('CALL sp_gestionar_mascota(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $request->accion,
            $id,
            null, null, null, null, null, null, null, null, null, null,
        ]);

        return $this->success($resultado[0]);
    }

    // ─── DESTROY (solo Admin) ─────────────────────────────────────────────────
    public function destroy($id)
    {
        // Recuperar la foto antes de eliminar para borrarla del storage
        $mascota = DB::select('SELECT foto FROM vw_pets WHERE mascota_id = ?', [$id]);

        if (!empty($mascota) && $mascota[0]->foto) {
            Storage::disk('public')->delete($mascota[0]->foto);
        }

        $resultado = DB::select('CALL sp_gestionar_mascota(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'eliminar',
            $id,
            null, null, null, null, null, null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Mascota eliminada exitosamente');
    }
}
