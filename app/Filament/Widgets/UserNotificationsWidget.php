<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\UserNotification;

class UserNotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-notifications-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -5;

    public function getUnreadNotifications()
    {
        return UserNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function markAsRead($notificationId)
    {
        $notification = UserNotification::find($notificationId);
        if ($notification && $notification->user_id === auth()->id()) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        UserNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
