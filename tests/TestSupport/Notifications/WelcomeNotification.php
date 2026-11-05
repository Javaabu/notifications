<?php

namespace Javaabu\Notifications\Tests\TestSupport\Notifications;

use Javaabu\Notifications\BaseNotification;
use Javaabu\Notifications\Messages\NotificationMessage;

class WelcomeNotification extends BaseNotification
{
    public function message(object $notifiable): NotificationMessage
    {
        return NotificationMessage::make(__('Welcome'))
            ->body(__('Thanks for joining.'));
    }
}
