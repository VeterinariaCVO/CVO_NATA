<?php

namespace App\Http\Controllers;

use App\Models\WorkingDay;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class WorkingDayController extends Controller
{
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

            $isOpen = !in_array($current->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

            $workingDay = WorkingDay::firstOrCreate(
                ['date' => $dateStr],
                ['is_open' => $isOpen]
            );

            if ($workingDay->wasRecentlyCreated) {
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

    public function show(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->load('timeSlots');

        return response()->json($workingDay);
    }

    public function toggleOpen(WorkingDay $workingDay): JsonResponse
    {
        $newState = !$workingDay->is_open;

        $workingDay->update(['is_open' => $newState]);

        $workingDay->timeSlots()
            ->where('status', 'available')
            ->update(['is_open' => $newState]);

        if (!$newState) {
            $workingDay->timeSlots()
                ->where('status', 'reserved')
                ->with('appointment.pet.owner')
                ->get()
                ->each(function ($slot) {
                    $appointment = $slot->appointment;

                    if ($appointment) {
                        $appointment->update(['status' => 'cancelled']);
                        $slot->update(['status' => 'available', 'is_open' => false]);

                        $owner = $appointment->pet?->owner;
                        if ($owner) {
                            $owner->notify(new \App\Notifications\AppointmentCancelled($appointment));
                        }
                    }
                });
        }

        return response()->json([
            'message' => 'Estado del día actualizado.',
            'date'    => $workingDay->date,
            'is_open' => $workingDay->is_open,
        ]);
    }

    public function destroy(WorkingDay $workingDay): JsonResponse
    {
        $workingDay->delete();

        return response()->json(['message' => 'Día laboral eliminado.']);
    }

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
                'is_open'        => $workingDay->is_open,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $current->addMinutes(30);
        }

        TimeSlot::insert($slots);
    }
}
