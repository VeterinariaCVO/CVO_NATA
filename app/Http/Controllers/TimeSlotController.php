<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ================================================================
// TimeSlotController — Gestión de bloques de horario
// Usa sp_gestionar_horarios (Admin)
// ================================================================
class TimeSlotController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    // Devuelve los slots de un día de trabajo específico
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
    // El SP valida que el día exista y que start_time < end_time
    public function store(Request $request)
    {
        $request->validate([
            'working_day_id' => 'required|integer|exists:working_days,id',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
        ]);

        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'crear',
            null,                        // p_slot_id (no aplica en crear)
            $request->working_day_id,
            $request->start_time,
            $request->end_time,
            'available',                 // status por defecto
            1,                           // is_open por defecto
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
            null,                        // working_day_id (no cambia en editar)
            $request->start_time,
            $request->end_time,
            $request->status,
            $request->is_open,
        ]);

        return $this->success($resultado[0], 'Slot actualizado correctamente');
    }

    // ─── CERRAR SLOT ──────────────────────────────────────────────────────────
    // Deshabilita un slot individual (is_open = 0)
    public function cerrar($id)
    {
        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'cerrar',
            $id,
            null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Slot cerrado correctamente');
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    // El SP no permite eliminar slots reservados
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_horarios(?, ?, ?, ?, ?, ?, ?)', [
            'eliminar',
            $id,
            null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Slot eliminado correctamente');
    }

    // ─── DESHABILITAR TODOS LOS SLOTS DE UN DÍA ──────────────────────────────
    public function disableAllForDay($workingDayId)
    {
        DB::statement(
            'UPDATE time_slots SET is_open = 0, updated_at = NOW()
             WHERE working_day_id = ? AND status = "available"',
            [$workingDayId]
        );

        return $this->success(null, 'Todos los slots disponibles fueron deshabilitados');
    }

    // ─── HABILITAR TODOS LOS SLOTS DE UN DÍA ─────────────────────────────────
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


// ================================================================
// WorkingDayController — Gestión de días de trabajo
// Usa sp_gestionar_dias_trabajo (Admin)
// ================================================================
class WorkingDayController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    public function index()
    {
        $dias = DB::select(
            'SELECT * FROM working_days ORDER BY date ASC'
        );
        return $this->success($dias);
    }

    // ─── SHOW (con sus slots) ─────────────────────────────────────────────────
    public function show($id)
    {
        $dia = DB::select('SELECT * FROM working_days WHERE id = ?', [$id]);

        if (empty($dia)) {
            return $this->error('Día no encontrado', 404);
        }

        // También cargamos los slots de ese día
        $slots = DB::select(
            'SELECT * FROM time_slots WHERE working_day_id = ? ORDER BY start_time ASC',
            [$id]
        );

        $resultado        = $dia[0];
        $resultado->slots = $slots;

        return $this->success($resultado);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    // El SP valida que no exista ya un registro con esa fecha
    public function store(Request $request)
    {
        $request->validate([
            'date'    => 'required|date|unique:working_days,date',
            'is_open' => 'boolean',
        ]);

        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'crear',
            null,                    // p_day_id (no aplica en crear)
            $request->date,
            $request->is_open ?? 1,
        ]);

        return $this->success($resultado[0], 'Día de trabajo creado correctamente', 201);
    }

    // ─── CERRAR DÍA ───────────────────────────────────────────────────────────
    // Pone is_open = 0 en el día Y en todos sus slots disponibles
    public function cerrar($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'cerrar',
            $id,
            null,
            null,
        ]);

        return $this->success($resultado[0], 'Día cerrado correctamente');
    }

    // ─── ABRIR DÍA ────────────────────────────────────────────────────────────
    public function abrir($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'abrir',
            $id,
            null,
            null,
        ]);

        return $this->success($resultado[0], 'Día abierto correctamente');
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

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    // El SP elimina también todos los slots del día
    // Lanza error si hay citas activas ese día
    public function destroy($id)
    {
        $resultado = DB::select('CALL sp_gestionar_dias_trabajo(?, ?, ?, ?)', [
            'eliminar',
            $id,
            null,
            null,
        ]);

        return $this->success($resultado[0], 'Día de trabajo eliminado correctamente');
    }
}
