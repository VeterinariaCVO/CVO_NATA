<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AppointmentCancelled extends Notification implements ShouldBroadcastNow
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
            'type'           => 'appointment_cancelled',
            'title'          => 'Cita cancelada',
            'message'        => "La cita de {$this->appointment->pet?->name} del {$day?->date} ha sido cancelada.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable)
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return (new MailMessage)
            ->subject('Cita cancelada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Te informamos que la cita de tu mascota **{$this->appointment->pet?->name}** ha sido cancelada.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$day?->date}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Agendar nueva cita', url('/client/citas'))
            ->line('Si tienes dudas, no dudes en contactarnos.');
    }
}
