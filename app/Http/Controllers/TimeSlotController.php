<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeSlotController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    public function index($workingDayId)
    {
        $slots = DB::select(
            'SELECT * FROM time_slots WHERE working_day_id = ? ORDER BY start_time ASC',
            [$workingDayId]
        );
        return $this->success($slots);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $slot = DB::select('SELECT * FROM time_slots WHERE id = ?', [$id]);

        if (empty($slot)) {
            return $this->error('Slot no encontrado', 404);
        }

        return $this->success($slot[0]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'working_day_id' => 'required|integer|exists:working_days,id',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
        ]);

        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'crear',
            null,
            $request->working_day_id,
            $request->start_time,
            $request->end_time,
            'available',
            1,
        ]);

        return $this->success($resultado[0], 'Slot creado correctamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'status'     => 'nullable|in:available,reserved',
            'is_open'    => 'nullable|boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'editar',
            $id,
            null,
            $request->start_time,
            $request->end_time,
            $request->status,
            $request->is_open,
        ]);

        return $this->success($resultado[0], 'Slot actualizado correctamente');
    }

    // ─── CERRAR ───────────────────────────────────────────────────────────────
    public function cerrar($id)
    {
        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'cerrar', $id, null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Slot cerrado correctamente');
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'eliminar', $id, null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Slot eliminado correctamente');
    }

    // ─── DESHABILITAR TODOS ───────────────────────────────────────────────────
    public function disableAllForDay($workingDayId)
    {
        DB::statement(
            'UPDATE time_slots SET is_open = 0, updated_at = NOW()
             WHERE working_day_id = ? AND status = "available"',
            [$workingDayId]
        );

        return $this->success(null, 'Todos los slots disponibles fueron deshabilitados');
    }

    // ─── HABILITAR TODOS ──────────────────────────────────────────────────────
    public function enableAllForDay($workingDayId)
    {
        DB::statement(
            'UPDATE time_slots SET is_open = 1, updated_at = NOW()
             WHERE working_day_id = ? AND status = "available"',
            [$workingDayId]
        );

        return $this->success(null, 'Todos los slots disponibles fueron habilitados');
    }
}
