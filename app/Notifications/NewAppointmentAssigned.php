<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
            'message'        => "Tienes una nueva cita para {$this->appointment->pet?->name} el {$day?->date} de {$slot?->start_time} a {$slot?->end_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'owner_name'     => $this->appointment->pet?->owner?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $day?->date,
            'start_time'     => $slot?->start_time,
            'end_time'       => $slot?->end_time,
            'status'         => $this->appointment->status,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable),
        ]);
    }
}
