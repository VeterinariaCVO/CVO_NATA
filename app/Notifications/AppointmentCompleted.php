<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notifica al dueño de la mascota cuando el veterinario
 * registra el expediente médico (RF-24).
 */
class AppointmentCompleted extends Notification implements ShouldBroadcast
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
            'type'           => 'appointment_completed',
            'title'          => 'Cita completada',
            'message'        => "La cita de {$this->appointment->pet?->name} del {$date} ha sido completada. Ya puedes consultar el expediente médico.",
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
            ->subject('Cita completada — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("La cita de tu mascota **{$this->appointment->pet?->name}** ha sido completada exitosamente.")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line("**Fecha:** {$this->toDatabase($notifiable)['date']}")
            ->line("Ya puedes consultar el expediente médico registrado por el veterinario.")
            ->action('Ver historial clínico', url('/client/mascotas/' . $this->appointment->pet?->id . '/historial'))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}
