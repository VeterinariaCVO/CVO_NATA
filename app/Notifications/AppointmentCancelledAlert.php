<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCancelledAlert extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Appointment $appointment,
        public ?User $cancelledBy = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $slot = $this->appointment->timeSlot;
        $day = $slot?->workingDay;
        $actor = $this->cancelledBy?->name ?? 'un usuario';

        return [
            'type' => 'appointment_cancelled_alert',
            'title' => 'Cita cancelada',
            'message' => "{$actor} canceló la cita de {$this->appointment->pet?->name} del {$day?->date}.",
            'appointment_id' => $this->appointment->id,
            'pet_name' => $this->appointment->pet?->name,
            'owner_name' => $this->appointment->pet?->owner?->name,
            'service' => $this->appointment->service?->name,
            'date' => $day?->date,
            'start_time' => $slot?->start_time,
            'cancelled_by_id' => $this->cancelledBy?->id,
            'cancelled_by_name' => $actor,
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
        $day = $slot?->workingDay;
        $actor = $this->cancelledBy?->name ?? 'un usuario';

        return (new MailMessage)
            ->subject('Cita cancelada — CVO')
            ->greeting("Hola, {$notifiable->name}")
            ->line("{$actor} canceló una cita en el sistema.")
            ->line("Mascota: {$this->appointment->pet?->name}")
            ->line("Cliente: {$this->appointment->pet?->owner?->name}")
            ->line("Servicio: {$this->appointment->service?->name}")
            ->line("Fecha: {$day?->date}")
            ->line("Horario: {$slot?->start_time} - {$slot?->end_time}")
            ->action('Ver citas', url('/citas'));
    }
}
