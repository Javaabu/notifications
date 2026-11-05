<?php

namespace Javaabu\Notifications\Contracts;

/**
 * A model notifications can be about. Both methods are optional to implement through
 * this interface: BaseNotification calls them when they exist.
 */
interface NotificationSubject
{
    /**
     * Where clicking a notification about this model goes, when the message has no URL
     */
    public function notificationUrl(?object $notifiable = null): ?string;

    /**
     * The image shown for notifications about this model, when the message has none
     */
    public function notificationThumbnail(?object $notifiable = null): ?string;
}
