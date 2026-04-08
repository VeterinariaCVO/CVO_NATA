<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRegistered extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'user_registered',
            'title'   => 'Bienvenido a CVO',
            'message' => "Hola {$this->user->name}, tu cuenta ha sido creada exitosamente.",
            'user_id' => $this->user->id,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toDatabase($notifiable)
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenido a Veterinaria del Oriente')
            ->greeting("¡Hola, {$this->user->name}!")
            ->line('Tu cuenta ha sido creada exitosamente en nuestro sistema.')
            ->line('Ya puedes iniciar sesión y agendar citas para tus mascotas.')
            ->action('Iniciar sesión', url('/login'))
            ->line('Gracias por confiar en Veterinaria del Oriente.');
    }
}
