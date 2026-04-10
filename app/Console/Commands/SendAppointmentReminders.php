<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Comando programado que envía recordatorios 24 horas antes de cada cita.
 *
 * Registro en routes/console.php (Laravel 11):
 *
 *   use Illuminate\Support\Facades\Schedule;
 *
 *   Schedule::command('appointments:send-reminders')->dailyAt('08:00');
 *
 * O en app/Console/Kernel.php si usas Laravel 10:
 *
 *   protected function schedule(Schedule $schedule): void
 *   {
 *       $schedule->command('appointments:send-reminders')->dailyAt('08:00');
 *   }
 */
class SendAppointmentReminders extends Command
{
    protected $signature   = 'appointments:send-reminders';
    protected $description = 'Envía recordatorios de cita a los clientes con cita confirmada para mañana';

   public function handle(): void
{
    $tomorrow = Carbon::tomorrow()->toDateString();

    $appointments = Appointment::query()
        ->whereIn('status', ['confirmed', 'pending'])
        ->whereHas('timeSlot.workingDay', function ($q) use ($tomorrow) {
            $q->where('date', $tomorrow);
        })
        ->with(['timeSlot.workingDay', 'pet.owner', 'service'])
        ->get();

    if ($appointments->isEmpty()) {
        $this->info('No hay citas para mañana. Nada que enviar.');
        return;
    }

    $sent = 0; // 👈 contador real

    foreach ($appointments as $appointment) {
        $owner = $appointment->pet?->owner;

        if (! $owner) {
            $this->warn("Cita #{$appointment->id} sin propietario, omitida.");
            continue;
        }

        // ✅ EVITAR DUPLICADOS
        $alreadySent = $owner->notifications()
            ->where('data->appointment_id', $appointment->id)
            ->where('data->type', 'appointment_reminder')
            ->exists();

        if ($alreadySent) {
            $this->info("Ya enviado → Cita #{$appointment->id}");
            continue;
        }

        // ✅ ENVIAR NOTIFICACIÓN
        $owner->notify(new AppointmentReminder($appointment));

        $sent++; // 👈 solo cuenta los enviados

        $this->info("Recordatorio enviado → Cita #{$appointment->id} | {$owner->name}");
    }

    $this->info("Total enviados: {$sent}");
}
}
