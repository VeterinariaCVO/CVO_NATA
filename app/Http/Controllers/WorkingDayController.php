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
        return $this->success(
            WorkingDayResource::collection(WorkingDay::with('timeSlots')->orderBy('date')->get())
        );
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

    public function destroy($id)
    {
        WorkingDay::findOrFail($id)->delete();
        return $this->success(null, 'Día laboral eliminado');
    }
}
