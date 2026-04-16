<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewAppointmentAssigned extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return [
            'type'           => 'new_appointment_assigned',
            'title'          => 'Nueva cita asignada',
            'message'        => "Nueva cita para {$this->appointment->pet?->name} el {$day?->date} de {$slot?->start_time} a {$slot?->end_time}.",
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
        'data' => array_merge($this->toDatabase($notifiable), [
            'created_at' => now()->toIso8601String(),
        ])
    ]);
}

}
