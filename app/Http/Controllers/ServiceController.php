<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    use ApiResponse;

    // ─── INDEX (público / cliente / empleado) ─────────────────────────────────
    // Solo devuelve servicios activos
    public function index()
    {
        $servicios = DB::select(
            'SELECT * FROM services WHERE active = 1 ORDER BY name ASC'
        );
        return $this->success($servicios);
    }

    // ─── INDEX ADMIN ──────────────────────────────────────────────────────────
    // Devuelve todos (activos e inactivos)
    public function indexAdmin()
    {
        $servicios = DB::select('SELECT * FROM services ORDER BY name ASC');
        return $this->success($servicios);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $servicio = DB::select('SELECT * FROM services WHERE id = ?', [$id]);

        if (empty($servicio)) {
            return $this->error('Servicio no encontrado', 404);
        }

        return $this->success($servicio[0]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    // El SP valida que no exista otro servicio con el mismo nombre
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5',
            'active'           => 'boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_servicios(?, ?, ?, ?, ?, ?, ?)', [
            'crear',
            null,                          // p_service_id (no aplica en crear)
            $request->name,
            $request->description,
            $request->price,
            $request->duration_minutes,
            $request->active ?? 1,
        ]);

        return $this->success($resultado[0], 'Servicio creado correctamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    // El SP usa COALESCE: campos que no mandes no cambian
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'             => 'nullable|string|max:255',
            'description'      => 'nullable|string',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:5',
            'active'           => 'nullable|boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_servicios(?, ?, ?, ?, ?, ?, ?)', [
            'editar',
            $id,
            $request->name,
            $request->description,
            $request->price,
            $request->duration_minutes,
            $request->active,
        ]);

        return $this->success($resultado[0], 'Servicio actualizado correctamente');
    }

    // ─── ACTIVAR / DESACTIVAR ─────────────────────────────────────────────────
    public function toggleActive(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:activar,desactivar',
        ]);

        $resultado = DB::select('CALL sp_gestionar_servicios(?, ?, ?, ?, ?, ?, ?)', [
            $request->accion,
            $id,
            null, null, null, null, null,
        ]);

        return $this->success($resultado[0]);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_servicios(?, ?, ?, ?, ?, ?, ?)', [
            'eliminar',
            $id,
            null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Servicio eliminado correctamente');
    }
}
