<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    use ApiResponse;

    // ─── INDEX ────────────────────────────────────────────────────────────────
    // Cada rol ve una vista diferente de la BD
    public function index(Request $request)
    {
        $user = Auth::user();

        // CLIENTE → solo ve sus propias citas (vw_citas_cliente)
        if ($user->isCliente()) {
            $citas = DB::select(
                'SELECT * FROM vw_citas_cliente WHERE cliente_id = ?',
                [$user->id]
            );
            return $this->success($citas);
        }

        // EMPLEADO → agenda activa desde hoy (vw_agenda_empleado)
        if ($user->isEmpleado()) {
            $citas = DB::select('SELECT * FROM vw_agenda_empleado');
            return $this->success($citas);
        }

        // ADMIN → todas las citas con filtros opcionales (vw_citas)
        // Explicación: construimos el WHERE dinámicamente según los filtros
        $where  = [];   // aquí guardamos las condiciones  ej: "status = ?"
        $params = [];   // aquí guardamos los valores      ej: "confirmed"

        if ($request->filled('status')) {
            // El admin puede filtrar por uno o varios status separados por coma
            $statuses    = explode(',', $request->status);
            $marcadores  = implode(',', array_fill(0, count($statuses), '?'));
            $where[]     = "status IN ($marcadores)";
            $params      = array_merge($params, $statuses);
        }

        if ($request->filled('pet_id')) {
            $where[]  = 'mascota_id = ?';
            $params[] = $request->pet_id;
        }

        if ($request->filled('fecha')) {
            $where[]  = 'fecha = ?';
            $params[] = $request->fecha;
        }

        // Unimos las condiciones con AND si hay alguna
        $sql = 'SELECT * FROM vw_citas';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY fecha DESC, start_time ASC';

        $citas = DB::select($sql, $params);
        return $this->success($citas);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        $cita = DB::select('SELECT * FROM vw_citas WHERE cita_id = ?', [$id]);

        if (empty($cita)) {
            return $this->error('Cita no encontrada', 404);
        }

        return $this->success($cita[0]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    // Admin/Empleado → sp_gestionar_cita('crear')
    // Cliente        → sp_agendar_cita()
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pet_id'       => 'required|integer|exists:pets,id',
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'service_id'   => 'required|integer|exists:services,id',
            'notes'        => 'nullable|string',
            'is_walk_in'   => 'boolean',
        ]);

        if ($user->isCliente()) {
            // El trigger trg_validacion_cita valida el slot automáticamente
            $resultado = DB::select('CALL sp_agendar_cita(?, ?, ?, ?, ?)', [
                $request->pet_id,
                $request->time_slot_id,
                $request->service_id,
                $request->notes,
                $user->id,
            ]);

            return $this->success($resultado[0], 'Cita agendada correctamente', 201);
        }

        // Admin o Empleado
        // El trigger trg_walkin_status pone 'in_progress' si is_walk_in = 1
        $resultado = DB::select('CALL sp_gestionar_cita(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'crear',
            null,                                  // p_cita_id (no aplica en crear)
            $request->pet_id,
            $request->time_slot_id,
            $request->service_id,
            $request->status ?? 'pending',
            $request->is_walk_in ?? 0,
            $request->notes,
            $user->id,                             // created_by
        ]);

        return $this->success($resultado[0], 'Cita registrada correctamente', 201);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    // Detecta qué acción hacer según los datos que llegan
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // CONFIRMAR → Empleado/Admin cambian pending → confirmed
        if ($request->input('accion') === 'confirmar') {
            $resultado = DB::select('CALL sp_confirmar_cita(?)', [$id]);
            return $this->success($resultado[0], 'Cita confirmada');
        }

        // COMPLETAR → Empleado/Admin cambian confirmed/in_progress → completed
        if ($request->input('accion') === 'completar') {
            $resultado = DB::select('CALL sp_completar_cita(?)', [$id]);
            return $this->success($resultado[0], 'Cita completada');
        }

        // REAGENDAR → cambia el time_slot (Empleado o Cliente)
        if ($request->filled('time_slot_id')) {
            // Si es cliente pasamos su id para que el SP valide que es su cita
            // Si es Admin/Empleado pasamos NULL (sin validación de ownership)
            $owner_id = $user->isCliente() ? $user->id : null;

            $resultado = DB::select('CALL sp_reagendar_cita(?, ?, ?)', [
                $id,
                $request->time_slot_id,
                $owner_id,
            ]);

            return $this->success($resultado[0], 'Cita reagendada');
        }

        // EDITAR notas o status (Admin/Empleado)
        $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'notes'  => 'nullable|string',
        ]);

        $resultado = DB::select('CALL sp_gestionar_cita(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'editar',
            $id,
            null, null, null,          // pet_id, slot_id, service_id (no cambian en editar)
            $request->status,
            null,                      // is_walk_in (no cambia en editar)
            $request->notes,
            null,                      // created_by (no cambia)
        ]);

        return $this->success($resultado[0], 'Cita actualizada');
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    // Admin/Empleado → sp_gestionar_cita('cancelar')
    // Cliente        → sp_cancelar_cita() (valida que sea su cita)
    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->isCliente()) {
            $resultado = DB::select('CALL sp_cancelar_cita(?, ?)', [
                $id,
                $user->id,   // el SP verifica que la cita le pertenece
            ]);

            return $this->success($resultado[0], 'Cita cancelada correctamente');
        }

        // Admin o Empleado: el trigger trg_liberar_slot libera el slot solo
        $resultado = DB::select('CALL sp_gestionar_cita(?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            'cancelar',
            $id,
            null, null, null, null, null, null, null,
        ]);

        return $this->success($resultado[0], 'Cita cancelada correctamente');
    }

    // ─── REPORTE (solo Admin) ─────────────────────────────────────────────────
    // Devuelve el agregado por día desde vw_reporte_citas
    public function reporte()
    {
        $reporte = DB::select('SELECT * FROM vw_reporte_citas');
        return $this->success($reporte);
    }
}
