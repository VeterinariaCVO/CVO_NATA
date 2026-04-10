<?php

namespace App\Http\Controllers;

use App\Models\WorkingDay;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class WorkingDayController extends Controller
{
    /**
     * Listar todos los días laborales (con sus time slots)
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkingDay::with('timeSlots');

        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('date', $request->month)
                ->whereYear('date', $request->year);
        }

        $workingDays = $query->orderBy('date')->get();

        return response()->json($workingDays);
    }

    /**
     * Generar un mes completo de días laborales con sus time slots
     */
    public function generateMonth(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer|min:2024',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year  = $request->year;
        $month = $request->month;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $created = [];
        $skipped = [];

        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();

            // Sábado = 6, Domingo = 0
            $isOpen = !in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

            $workingDay = WorkingDay::firstOrCreate(
                ['date' => $dateStr],
                ['is_open' => $isOpen]
            );

            if ($workingDay->wasRecentlyCreated) {
                // Generar time slots solo si el día fue creado nuevo
                $this->generateTimeSlotsForDay($workingDay);
                $created[] = $dateStr;
            } else {
                $skipped[] = $dateStr;
            }

            $current->addDay();
        }

        return response()->json([
            'message' => "Mes {$month}/{$year} generado correctamente.",
            'created' => $created,
            'skipped' => $skipped,
        ], 201);
    }

    /**
     * Mostrar un día laboral específico con sus time slots
     */
    public function show(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->load('timeSlots');

        return response()->json($workingDay);
    }

    /**
     * Habilitar o deshabilitar un día laboral
     */
    public function toggleOpen(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->update(['is_open' => !$workingDay->is_open]);

        return response()->json([
            'message'  => 'Estado del día actualizado.',
            'date'     => $workingDay->date,
            'is_open'  => $workingDay->is_open,
        ]);
    }

    /**
     * Eliminar un día laboral (y sus time slots por cascade)
     */
    public function destroy(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->delete();

        return response()->json(['message' => 'Día laboral eliminado.']);
    }

    /**
     * Helper privado: genera los time slots de 30 en 30 min (09:00 - 18:00)
     */
    private function generateTimeSlotsForDay(WorkingDay $workingDay): void
    {
        $start = Carbon::createFromTimeString('09:00');
        $end   = Carbon::createFromTimeString('18:00');

        $slots = [];
        $now   = now();

        $current = $start->copy();

        while ($current->lt($end)) {
            $slotEnd = $current->copy()->addMinutes(30);

            $slots[] = [
                'working_day_id' => $workingDay->id,
                'start_time'     => $current->format('H:i:s'),
                'end_time'       => $slotEnd->format('H:i:s'),
                'status'         => 'available',
                'is_open'        => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $current->addMinutes(30);
        }

        TimeSlot::insert($slots);
    }
}
