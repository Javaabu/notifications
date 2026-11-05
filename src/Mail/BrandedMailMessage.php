<?php

namespace Javaabu\Notifications\Mail;

use Illuminate\Notifications\Messages\MailMessage;
use Javaabu\Notifications\Messages\NotificationMessage;

/**
 * A mail message in the branded theme and layout (`notifications.mail`)
 */
class BrandedMailMessage extends MailMessage
{
    public function __construct()
    {
        $this->viewData['locale'] = app()->getLocale();

        if (! config('notifications.mail.branded', true)) {
            return;
        }

        $this->markdown = 'notifications::mail.notification';
        $this->theme = config('notifications.mail.theme', 'notifications::mail.theme');
    }

    /**
     * The language the mail's own words (greeting, salutation, footer) are in, and whether it's right to left
     */
    public function locale(string $locale): static
    {
        $this->viewData['locale'] = $locale;

        return $this;
    }

    public static function fromMessage(NotificationMessage $message, ?string $url = null): static
    {
        $mail = (new static)
            ->subject($message->title)
            ->level($message->level);

        if ($message->greeting !== null) {
            $mail->greeting($message->greeting);
        }

        foreach ($message->lines as $line) {
            $mail->line($line);
        }

        if ($message->action_text !== null && $url !== null) {
            $mail->action($message->action_text, $url);
        }

        if ($message->salutation !== null) {
            $mail->salutation($message->salutation);
        }

        return $mail;
    }
}
