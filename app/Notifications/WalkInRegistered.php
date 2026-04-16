<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Alerta a todos los veterinarios activos (y admin) cuando
 * se registra una atención sin cita previa (RF-20).
 */
class WalkInRegistered extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'walk_in_registered',
            'title'          => 'Paciente walk-in',
            'message'        => "Se ha registrado una atención sin cita para {$this->appointment->pet?->name} ({$this->appointment->service?->name}). Atención inmediata requerida.",
            'appointment_id' => $this->appointment->id,
            'pet_name'       => $this->appointment->pet?->name,
            'service'        => $this->appointment->service?->name,
            'owner_name'     => $this->appointment->pet?->owner?->name,
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


    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Paciente walk-in — Veterinaria del Oriente')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Se ha registrado una atención sin cita previa que requiere tu atención.")
            ->line("**Mascota:** {$this->appointment->pet?->name}")
            ->line("**Propietario:** {$this->appointment->pet?->owner?->name}")
            ->line("**Servicio:** {$this->appointment->service?->name}")
            ->line('La atención ya se encuentra en estado **En curso**.')
            ->action('Ver detalle de la cita', url('/citas/' . $this->appointment->id))
            ->line('Por favor, atiende al paciente a la brevedad.');
    }
}
