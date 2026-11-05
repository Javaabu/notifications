<?php

namespace Javaabu\Notifications\Concerns;

use Illuminate\Notifications\RoutesNotifications;
use Javaabu\Notifications\Support\Broadcasting;

/**
 * Use instead of Laravel's `Illuminate\Notifications\Notifiable`: the same, with the package's
 * notification model and a broadcast channel that works with any guard
 */
trait Notifiable
{
    use HasDatabaseNotifications;
    use RoutesNotifications;

    /**
     * The private channel new notifications are broadcast on: `notifications.{type}.{id}`
     */
    public function receivesBroadcastNotificationsOn(mixed $notification = null): string
    {
        return Broadcasting::channelFor($this);
    }
}
