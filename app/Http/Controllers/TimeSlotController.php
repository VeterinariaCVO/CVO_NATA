<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use App\Models\WorkingDay;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TimeSlotController extends Controller
{
    /**
     * Listar todos los time slots de un día laboral específico
     */
    public function index(WorkingDay $workingDay): JsonResponse
    {
        $slots = $workingDay->timeSlots()->orderBy('start_time')->get();

        return response()->json($slots);
    }

    /**
     * Mostrar un time slot específico
     */
    public function show(TimeSlot $timeSlot): JsonResponse
    {
        return response()->json($timeSlot);
    }

    /**
     * Habilitar o deshabilitar un time slot
     */
    public function toggleOpen(TimeSlot $timeSlot): JsonResponse
    {
        // No permitir habilitar un slot que ya está reservado
        if ($timeSlot->status === 'reserved' && !$timeSlot->is_open) {
            return response()->json([
                'message' => 'No se puede habilitar un slot reservado.',
            ], 422);
        }

        $timeSlot->update(['is_open' => !$timeSlot->is_open]);

        return response()->json([
            'message'    => 'Estado del slot actualizado.',
            'start_time' => $timeSlot->start_time,
            'end_time'   => $timeSlot->end_time,
            'is_open'    => $timeSlot->is_open,
        ]);
    }

    /**
     * Cambiar el status de un time slot (available <-> reserved)
     */
    public function updateStatus(Request $request, TimeSlot $timeSlot): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:available,reserved',
        ]);

        if (!$timeSlot->is_open) {
            return response()->json([
                'message' => 'No se puede modificar un slot deshabilitado.',
            ], 422);
        }

        $timeSlot->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Status del slot actualizado.',
            'slot'    => $timeSlot,
        ]);
    }

    /**
     * Deshabilitar todos los slots de un día de golpe
     */
    public function disableAllForDay(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->timeSlots()
            ->where('status', 'available')
            ->update(['is_open' => false]);

        return response()->json([
            'message' => "Todos los slots disponibles del día {$workingDay->date} fueron deshabilitados.",
        ]);
    }

    /**
     * Habilitar todos los slots de un día de golpe
     */
    public function enableAllForDay(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->timeSlots()
            ->where('status', 'available')
            ->update(['is_open' => true]);

        return response()->json([
            'message' => "Todos los slots disponibles del día {$workingDay->date} fueron habilitados.",
        ]);
    }
}
