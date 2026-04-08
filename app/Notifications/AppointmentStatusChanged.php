<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentStatusChanged extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {

        $canales = ['database', 'broadcast', 'mail'];


        if (in_array($this->appointment->status, ['cancelled', 'confirmed'])) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    // 4. Método auxiliar para mantener el código limpio y no repetir el arreglo
    private function getStatusLabel(): string
    {
        $labels = [
            'pending'     => 'pendiente',
            'confirmed'   => 'confirmada',
            'in_progress' => 'en curso',
            'completed'   => 'completada',
            'cancelled'   => 'cancelada',
        ];

        return $labels[$this->appointment->status] ?? $this->appointment->status;
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

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable)
        ]);
    }

    public function toMail(object $notifiable)
    {
        $label = $this->getStatusLabel();

        return (new MailMessage)
            ->subject('Actualización de cita en Veterinaria del Oriente') // Asunto personalizado
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Te informamos que el estado de la cita para tu mascota {$this->appointment->pet?->name} ha cambiado.")
            ->line("El nuevo estado es: **{$label}**.")
            ->action('Ver detalles en el sistema', url('/citas/'.$this->appointment->id))
            ->line('Gracias por confiar el cuidado de tu mascota en nuestras manos.');
    }
}
