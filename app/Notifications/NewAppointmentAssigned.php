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
            $date = $day?->date ? \Carbon\Carbon::parse($day->date)->format('d/m/Y') : 'N/A';

        return [
            'type'           => 'new_appointment_assigned',
            'title'          => 'Nueva cita asignada',
            'message'        => "Nueva cita para {$this->appointment->pet?->name} el {$date} de {$slot?->start_time} a {$slot?->end_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $date,
            'start_time'     => $slot?->start_time,
            'status'         => $this->appointment->status,
        ];
    }

     public function toBroadcast($notifiable): BroadcastMessage
{
    $data = $this->toDatabase($notifiable);

    return new BroadcastMessage([
        'type'       => $data['type'],
        'title'      => $data['title'],
        'message'    => $data['message'],
        'created_at' => now()->format('Y-m-d H:i'),
        'data'       => $data,
    ]);
}


}
