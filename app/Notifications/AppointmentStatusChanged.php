<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AppointmentStatusChanged extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        $canales = ['database', 'broadcast'];

        if (in_array($this->appointment->status, ['in_progress', 'completed'])) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    private function getStatusLabel(): string
    {
        return match($this->appointment->status) {
            'pending'     => 'pendiente',
            'confirmed'   => 'confirmada',
            'in_progress' => 'en curso',
            'completed'   => 'completada',
            'cancelled'   => 'cancelada',
            default       => $this->appointment->status,
        };
    }

    public function toDatabase(object $notifiable): array
    {
        $label = $this->getStatusLabel();

        return [
            'type'           => 'appointment_status_changed',
            'title'          => 'Estado de cita actualizado',
            'message'        => "La cita de {$this->appointment->pet?->name} ahora está {$label}.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'status'         => $this->appointment->status,
            'status_label'   => $label,
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
        $label = $this->getStatusLabel();

        return (new MailMessage)
            ->subject('Actualización de cita — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("El estado de la cita de tu mascota **{$this->appointment->pet?->name}** ha cambiado.")
            ->line("**Nuevo estado:** {$label}")
            ->action('Ver detalle de la cita', url('/citas/' . $this->appointment->id))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}
