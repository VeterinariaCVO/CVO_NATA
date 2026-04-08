<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AppointmentCreated extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_created',
            'title'          => 'Cita registrada',
            'message'        => "Cita para {$this->appointment->pet?->name} el {$day?->date} de {$slot?->start_time} a {$slot?->end_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
            'start_time'     => $slot?->start_time,
            'status'         => $this->appointment->status,
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
            ->subject('Cita registrada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Se ha registrado una cita para tu mascota **{$this->appointment->pet?->name}**.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$day?->date}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Ver mis citas', url('/client/citas'))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}
