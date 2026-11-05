<?php

namespace Javaabu\Notifications\Channels;

use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;
use Javaabu\Notifications\BaseNotification;

/**
 * Laravel's database channel, which also saves the subject a BaseNotification is about in
 * the `subject_type` and `subject_id` columns
 */
class DatabaseChannel extends BaseDatabaseChannel
{
    /**
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);

        if (! $notification instanceof BaseNotification) {
            return $payload;
        }

        if (! config('notifications.subject_columns', true)) {
            return $payload;
        }

        $subject = $notification->subjectFor($notifiable);

        return [
            ...$payload,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
        ];
    }
}
