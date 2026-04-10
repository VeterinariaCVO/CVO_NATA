<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notifica al dueño de la mascota cuando el veterinario
 * registra el expediente médico (RF-24).
 */
class AppointmentCompleted extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_completed',
            'title'          => 'Cita completada',
            'message'        => "La cita de {$this->appointment->pet?->name} del {$day?->date} ha sido completada. Ya puedes consultar el expediente médico.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
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
            ->subject('Cita completada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("La cita de tu mascota **{$this->appointment->pet?->name}** ha sido completada exitosamente.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$day?->date}")
            ->line("Ya puedes consultar el expediente médico registrado por el veterinario.")
            ->action('Ver historial clínico', url('/client/mascotas/' . $this->appointment->pet?->id . '/historial'))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}
