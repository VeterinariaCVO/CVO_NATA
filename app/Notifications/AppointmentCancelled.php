<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AppointmentCancelled extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_cancelled',
            'title'          => 'Cita cancelada',
            'message'        => "La cita de {$this->appointment->pet?->name} del {$date} ha sido cancelada.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $date,
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
            ->subject('Cita cancelada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Te informamos que la cita de tu mascota **{$this->appointment->pet?->name}** ha sido cancelada.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$this->toDatabase($notifiable)['date']}")
            ->line("**Horario:** {$slot?->start_time} — {$slot?->end_time}")
            ->action('Agendar nueva cita', url('/client/citas'))
            ->line('Si tienes dudas, no dudes en contactarnos.');
    }
}
