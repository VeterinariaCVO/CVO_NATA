<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Notifica a admins y recepcionistas cuando un nuevo cliente
 * se registra por su cuenta en el sistema (RF-01).
 */
class NewClientRegistered extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public User $client) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'      => 'new_client_registered',
            'title'     => 'Nuevo cliente registrado',
            'message'   => "{$this->client->name} se ha registrado como nuevo cliente en el sistema.",
            'client_id' => $this->client->id,
            'name'      => $this->client->name,
            'email'     => $this->client->email,
            'phone'     => $this->client->phone,
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
            ->subject('Nuevo cliente registrado — CVO')
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Un nuevo cliente se ha registrado en el sistema.")
            ->line("**Nombre:** {$this->client->name}")
            ->line("**Correo:** {$this->client->email}")
            ->line("**Teléfono:** {$this->client->phone}")
            ->action('Ver perfil del cliente', url('/clientes/' . $this->client->id))
            ->line('Puedes contactarlo o esperar a que agende su primera cita.');
    }
}
