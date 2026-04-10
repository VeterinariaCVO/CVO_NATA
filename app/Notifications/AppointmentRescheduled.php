<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Se dispara cuando se reagenda una cita (RF-16).
 *
 * Destinatarios:
 *   — Cliente (dueño de la mascota): se entera del nuevo horario
 *   — Recepcionista y Admin: se enteran del cambio
 */
class AppointmentRescheduled extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_rescheduled',
            'title'          => 'Cita reagendada',
            'message'        => "La cita de {$this->appointment->pet?->name} ha sido reagendada para el {$day?->date} de {$slot?->start_time} a {$slot?->end_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
            'start_time'     => $slot?->start_time,
            'end_time'       => $slot?->end_time,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return (new MailMessage)
            ->subject('Cita reagendada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("La cita de la mascota **{$this->appointment->pet?->name}** ha sido reagendada.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Nueva fecha:** {$day?->date}")
            ->line("**Nuevo horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Ver detalle de la cita', url('/citas/' . $this->appointment->id))
            ->line('Si tienes dudas, no dudes en contactarnos.');
    }
}
