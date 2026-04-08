<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderByRaw('read_at IS NOT NULL')
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'notifications' => NotificationResource::collection($notifications),
            'unread_count'  => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function unread()
    {
        $notifications = Auth::user()
            ->unreadNotifications()
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'notifications' => NotificationResource::collection($notifications),
            'unread_count'  => $notifications->count(),
        ]);
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(
            new NotificationResource($notification),
            'Notificación marcada como leída'
        );
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
        return $this->success(null, 'Todas las notificaciones marcadas como leídas');
    }

    public function destroy(string $id)
    {
        Auth::user()->notifications()->findOrFail($id)->delete();
        return $this->success(null, 'Notificación eliminada');
    }
}
