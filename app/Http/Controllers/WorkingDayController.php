<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkingDayResource;
use App\Http\Traits\ApiResponse;
use App\Models\WorkingDay;
use Illuminate\Http\Request;

class WorkingDayController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $days = WorkingDay::with('timeSlots')
            ->where('date', '>', now()->toDateString()) // excluir hoy y anteriores
            ->orderBy('date')
            ->get();

        return $this->success(WorkingDayResource::collection($days));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'    => 'required|date|unique:working_days,date',
            'is_open' => 'boolean',
        ]);

        $day = WorkingDay::create($data);

        return $this->success(new WorkingDayResource($day), 'Día laboral creado', 201);
    }

    public function show($id)
    {
        $day = WorkingDay::with('timeSlots')->findOrFail($id);
        return $this->success(new WorkingDayResource($day));
    }

    public function update(Request $request, $id)
    {
        $day  = WorkingDay::findOrFail($id);
        $data = $request->validate([
            'date'    => 'sometimes|date|unique:working_days,date,' . $id,
            'is_open' => 'boolean',
        ]);

        $day->update($data);

        return $this->success(new WorkingDayResource($day), 'Día laboral actualizado');
    }

    // En WorkingDayController

    public function toggle($id)
    {
        $day = WorkingDay::with('timeSlots.appointment')->findOrFail($id);

        $newState = !$day->is_open;
        $day->update(['is_open' => $newState]);

        // Si se cierra, cancelar todas las citas de ese día
        if (!$newState) {
            foreach ($day->timeSlots as $slot) {
                if ($slot->appointment) {
                    $slot->appointment->update(['status' => 'cancelled']);
                }
            }
        }

        $label = $newState ? 'Día habilitado' : 'Día deshabilitado';
        return $this->success(new WorkingDayResource($day->fresh()), $label);
    }

    public function destroy($id)
    {
        WorkingDay::findOrFail($id)->delete();
        return $this->success(null, 'Día laboral eliminado');
    }
}
