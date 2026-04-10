<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notifica a todos los veterinarios activos cuando una cita
 * queda confirmada por recepcionista o admin (RF-17b).
 */
class AppointmentConfirmedVet extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_confirmed_vet',
            'title'          => 'Nueva cita confirmada',
            'message'        => "Tienes una cita confirmada: {$this->appointment->pet?->name} ({$this->appointment->service?->name}) el {$day?->date} a las {$slot?->start_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'owner_name'     => $this->appointment->pet?->owner?->name,
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
            ->subject('Nueva cita confirmada en tu agenda — CVO')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Se ha confirmado una nueva cita en la agenda.")
            ->line("**Mascota:** {$this->appointment->pet?->name}")
            ->line("**Propietario:** {$this->appointment->pet?->owner?->name}")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$day?->date}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Ver mis citas', url('/veterinario/citas'))
            ->line('Recuerda revisar tu agenda antes de iniciar el día.');
    }
}
