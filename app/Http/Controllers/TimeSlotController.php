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
        ]);

        $slot->update($data);

        return $this->success(new TimeSlotResource($slot), 'Horario actualizado');
    }

    public function destroy($id)
    {
        TimeSlot::findOrFail($id)->delete();
        return $this->success(null, 'Horario eliminado');
    }
}
