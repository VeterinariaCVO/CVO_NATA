<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimeSlotResource;
use App\Http\Traits\ApiResponse;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = TimeSlot::with('workingDay');

        if ($request->filled('working_day_id')) {
            $query->where('working_day_id', $request->working_day_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por is_open si se envía el parámetro
        if ($request->filled('is_open')) {
            $query->where('is_open', filter_var($request->is_open, FILTER_VALIDATE_BOOLEAN));
        }

        // Nunca devolver slots de hoy o días pasados
        $query->whereHas('workingDay', function ($q) {
            $q->where('date', '>', now()->toDateString());
        });

        return $this->success(
            TimeSlotResource::collection($query->orderBy('start_time')->get())
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'working_day_id' => 'required|exists:working_days,id',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
            'is_open'        => 'sometimes|boolean', // ← agregado
        ]);

        $slot = TimeSlot::create($data);

        return $this->success(new TimeSlotResource($slot->load('workingDay')), 'Horario creado', 201);
    }

    public function show($id)
    {
        return $this->success(
            new TimeSlotResource(TimeSlot::with('workingDay')->findOrFail($id))
        );
    }

    public function update(Request $request, $id)
    {
        $slot = TimeSlot::findOrFail($id);

        $data = $request->validate([
            'start_time' => 'sometimes|date_format:H:i',
            'end_time'   => 'sometimes|date_format:H:i|after:start_time',
            'status'     => 'sometimes|in:available,reserved',
            'is_open'    => 'sometimes|boolean', // ← agregado
        ]);

        $slot->update($data);

        return $this->success(new TimeSlotResource($slot), 'Horario actualizado');
    }

    public function toggle($id)
    {
        $slot = TimeSlot::with('appointments')->findOrFail($id);

        $newState = !$slot->is_open;
        $slot->update(['is_open' => $newState]);

        // Si se cierra y tiene citas reservadas, cancelarlas
        if (!$newState && $slot->appointments->isNotEmpty()) {
            $slot->appointments()
                ->where('status', 'reserved')
                ->update(['status' => 'cancelled']);
        }

        $label = $newState ? 'Horario habilitado' : 'Horario deshabilitado';
        return $this->success(new TimeSlotResource($slot->fresh()), $label);
    }

    public function destroy($id)
    {
        TimeSlot::findOrFail($id)->delete();
        return $this->success(null, 'Horario eliminado');
    }
}
