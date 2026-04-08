<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WalkInAssigned extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        $pet   = $this->appointment->pet;
        $owner = $pet?->owner;
        $slot  = $this->appointment->timeSlot;

        return [
            'type'           => 'walk_in_assigned',
            'title'          => 'Atención sin cita asignada',
            'message'        => "Se te ha asignado una atención sin cita para {$pet?->name} ({$owner?->name}). Servicio: {$this->appointment->service?->name}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $pet?->name,
            'owner_name'     => $owner?->name,
            'service'        => $this->appointment->service?->name,
            'time_slot'      => $slot ? [
                'date'       => $slot->workingDay?->date,
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
            ] : null,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable),
        ]);
    }
}
