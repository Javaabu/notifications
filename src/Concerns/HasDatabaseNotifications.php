<?php

namespace Javaabu\Notifications\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Notifications;

/**
 * Laravel's HasDatabaseNotifications, with the package's notification model (`notifications.models.notification`)
 */
trait HasDatabaseNotifications
{
    /**
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notifications::notificationModel(), 'notifiable')->latest();
    }

    /**
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function readNotifications(): MorphMany
    {
        return $this->notifications()->read();
    }

    /**
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function unreadNotifications(): MorphMany
    {
        return $this->notifications()->unread();
    }
}
