<?php

namespace Javaabu\Notifications\Contracts;

use Illuminate\Notifications\Notification;

/**
 * A notifiable that chooses which channels it's notified through
 *
 * BaseNotification asks it about every channel except the notification's required ones.
 * The `ManagesNotificationPreferences` trait implements it with a JSON column.
 */
interface HasNotificationPreferences
{
    public function wantsNotificationVia(string $channel, Notification $notification): bool;
}
