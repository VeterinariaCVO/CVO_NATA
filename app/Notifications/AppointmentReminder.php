<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Recordatorio de cita enviado automáticamente 24 horas antes.
 * No se dispara manualmente — lo gestiona SendAppointmentReminders.
 */
class AppointmentReminder extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return [
            'type'           => 'appointment_reminder',
            'title'          => 'Recordatorio de cita',
            'message'        => "Recuerda que mañana tienes una cita para {$this->appointment->pet?->name} a las {$slot?->start_time}. ¿Confirmas tu asistencia?",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
            'start_time'     => $slot?->start_time,
            'end_time'       => $slot?->end_time,
        ];
    }

       public function toBroadcast($notifiable): BroadcastMessage
{
    return new BroadcastMessage([
        'data' => array_merge($this->toDatabase($notifiable), [
            'created_at' => now()->toIso8601String(),
        ])
    ]);
}


    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return (new MailMessage)
            ->subject('Recordatorio de cita — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Te recordamos que mañana tienes una cita programada.")
            ->line("**Mascota:** {$this->appointment->pet?->name}")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$day?->date}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->line('---')
            ->line('¿Vas a poder asistir?')
            ->action('Confirmar asistencia', url('/client/citas/' . $this->appointment->id . '/confirmar'))
            ->line('Si no puedes asistir, cancela tu cita con anticipación para liberar el horario.')
            ->salutation('¡Hasta mañana! — Veterinaria del Oriente');
    }
}
