<?php

namespace App\Notifications;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Appointment $appointment)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $slot = $this->appointment->timeSlot;
        $day  = $slot?->workingDay;
        $date = $day?->date ? Carbon::parse($day->date)->format('d/m/Y') : 'N/A';

        return [
            'type'           => 'appointment_created',
            'title'          => 'Cita registrada',
            'message'        => "Cita para {$this->appointment->pet?->name} el {$date} de {$slot?->start_time} a {$slot?->end_time}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'date'           => $date,
            'start_time'     => $slot?->start_time,
            'status'         => $this->appointment->status,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
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
        $date = $this->toDatabase($notifiable)['date'];

        return (new MailMessage)
            ->subject('Cita registrada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Hemos registrado la cita de tu mascota {$this->appointment->pet?->name}.")
            ->line("Servicio: {$this->appointment->service?->name}")
            ->line("Fecha: {$date}")
            ->line("Horario: {$slot?->start_time} — {$slot?->end_time}")
            ->action('Ver mis citas', url('/client/citas'))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}