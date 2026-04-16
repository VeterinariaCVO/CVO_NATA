<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notifica a recepcionistas y admins cuando un cliente
 * agenda una nueva cita (queda en estado Pendiente) (RF-15).
 */
class AppointmentPendingAlert extends Notification implements ShouldBroadcast
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
         $date = $day?->date ? \Carbon\Carbon::parse($day->date)->format('d/m/Y') : 'N/A';

        return [
            'type'           => 'appointment_pending_alert',
            'title'          => 'Nueva cita pendiente',
            'message'        => "El cliente {$this->appointment->pet?->owner?->name} agendó una cita para {$this->appointment->pet?->name} el {$date}. Requiere confirmación.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'owner_name'     => $this->appointment->pet?->owner?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $date,
            'start_time'     => $slot?->start_time,
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



    public function toMail(object $notifiable): MailMessage
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;

        return (new MailMessage)
            ->subject('Nueva cita pendiente de confirmación — CVO')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Un cliente ha agendado una nueva cita que requiere confirmación.")
            ->line("**Cliente:** {$this->appointment->pet?->owner?->name}")
            ->line("**Mascota:** {$this->appointment->pet?->name}")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$this->toDatabase($notifiable)['date']}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Confirmar cita', url('/citas/' . $this->appointment->id))
            ->line('Por favor, confirma o gestiona esta cita a la brevedad.');
    }
}
