<?php

namespace Javaabu\Notifications\Concerns;

use Illuminate\Notifications\Notification;
use Javaabu\Notifications\BaseNotification;

/**
 * Stores which channels a notifiable wants, per notification type, in a JSON column
 * (`notification_preferences` by default):
 *
 * ```json
 * {"*": {"sms": false}, "order-shipped": {"sms": true, "mail": false}}
 * ```
 *
 * A type's own preference wins over `*`. Channels without a preference are on.
 */
trait ManagesNotificationPreferences
{
    public function initializeManagesNotificationPreferences(): void
    {
        $this->mergeCasts([$this->notificationPreferencesAttribute() => 'array']);
    }

    public function notificationPreferencesAttribute(): string
    {
        return config('notifications.preferences.attribute', 'notification_preferences');
    }

    public function wantsNotificationVia(string $channel, Notification $notification): bool
    {
        $type = $notification instanceof BaseNotification
            ? $notification::preferenceKey()
            : BaseNotification::preferenceKeyFor($notification::class);

        return $this->notificationPreference($type, $channel)
            ?? $this->notificationPreference('*', $channel)
            ?? true;
    }

    /**
     * The saved preference for a type and channel, or null when there's none
     */
    public function notificationPreference(string $type, string $channel): ?bool
    {
        $value = $this->notificationPreferences()[$type][$channel] ?? null;

        return $value === null ? null : (bool) $value;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function notificationPreferences(): array
    {
        $preferences = $this->getAttribute($this->notificationPreferencesAttribute());

        return is_array($preferences) ? $preferences : [];
    }

    /**
     * Turn a channel on or off for a type (`*` for every type), or clear it with null.
     * Call save() afterwards.
     */
    public function setNotificationPreference(string $type, string $channel, ?bool $enabled): static
    {
        $preferences = $this->notificationPreferences();

        unset($preferences[$type][$channel]);

        if ($enabled !== null) {
            $preferences[$type][$channel] = $enabled;
        }

        $preferences = array_filter($preferences);

        $this->setAttribute($this->notificationPreferencesAttribute(), $preferences ?: null);

        return $this;
    }
}
