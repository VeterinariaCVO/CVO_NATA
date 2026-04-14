<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkingDayController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    public function index()
    {
        $dias = DB::select('SELECT * FROM working_days ORDER BY date ASC');
        return $this->success($dias);
    }

    // ─── SHOW (con sus slots) ─────────────────────────────────────────────────
    public function show($id)
    {
        $dia = DB::select('SELECT * FROM working_days WHERE id = ?', [$id]);

        if (empty($dia)) {
            return $this->error('Día no encontrado', 404);
        }

        $slots = DB::select(
            'SELECT * FROM time_slots WHERE working_day_id = ? ORDER BY start_time ASC',
            [$id]
        );

        $resultado        = $dia[0];
        $resultado->slots = $slots;

        return $this->success($resultado);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'date'    => 'required|date|unique:working_days,date',
            'is_open' => 'boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'crear',
            null,
            $request->date,
            $request->is_open ?? 1,
        ]);

        return $this->success($resultado[0], 'Día de trabajo creado correctamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'date'    => 'nullable|date',
            'is_open' => 'nullable|boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'editar',
            $id,
            $request->date,
            $request->is_open,
        ]);

        return $this->success($resultado[0], 'Día actualizado correctamente');
    }

    // ─── CERRAR ───────────────────────────────────────────────────────────────
    public function cerrar($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'cerrar', $id, null, null,
        ]);

        return $this->success($resultado[0], 'Día cerrado correctamente');
    }

    // ─── ABRIR ────────────────────────────────────────────────────────────────
    public function abrir($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'abrir', $id, null, null,
        ]);

        return $this->success($resultado[0], 'Día abierto correctamente');
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'eliminar', $id, null, null,
        ]);

        return $this->success($resultado[0], 'Día de trabajo eliminado correctamente');
    }
}
