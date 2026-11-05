<?php

namespace Javaabu\Notifications;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Support\Translations;
use RuntimeException;

/**
 * Package-wide helpers: the model classes, channel aliases and notifiable locales
 */
class Notifications
{
    /** Whether the package's migrations run (call ignoreMigrations() in a provider's register() to stop them) */
    public static bool $runs_migrations = true;

    /** The FCM channel class from laravel-notification-channels/fcm */
    public static string $fcm_channel = 'NotificationChannels\\Fcm\\FcmChannel';

    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    /**
     * @return class-string<DatabaseNotification>
     */
    public static function notificationModel(): string
    {
        return config('notifications.models.notification', DatabaseNotification::class);
    }

    public static function table(): string
    {
        return config('notifications.table', 'notifications');
    }

    /**
     * Whether push notifications can be sent: laravel-notification-channels/fcm is installed
     */
    public static function fcmInstalled(): bool
    {
        return class_exists(static::$fcm_channel);
    }

    /**
     * The channel Laravel sends through for a channel name: `sms` is the SMS driver's
     * channel (javaabu/sms-notifications), `fcm` the FCM channel. Others are unchanged.
     */
    public static function resolveChannel(string $channel): string
    {
        return match ($channel) {
            'sms' => static::smsChannel(),
            'fcm' => static::$fcm_channel,
            default => $channel,
        };
    }

    /**
     * The channel class of the configured SMS driver (`sms.default`)
     */
    public static function smsChannel(): string
    {
        $driver = config('sms.default');
        $channel = config("sms.channels.{$driver}");

        if (! $channel) {
            throw new RuntimeException("No SMS channel is configured for the driver [{$driver}]. Check sms.default and sms.channels (javaabu/sms-notifications).");
        }

        return $channel;
    }

    /**
     * The locale to notify a notifiable in: its preferredLocale() (HasLocalePreference), or
     * the attribute in `notifications.locale_attribute`. With javaabu/translatable on, only
     * its active languages count. Null means the current locale.
     */
    public static function localeFor(mixed $notifiable): ?string
    {
        $locale = static::preferredLocale($notifiable);

        if ($locale === null || $locale === '') {
            return null;
        }

        if (! Translations::enabled()) {
            return $locale;
        }

        return Translations::isActive($locale)
            ? $locale
            : Translations::defaultLocale();
    }

    protected static function preferredLocale(mixed $notifiable): ?string
    {
        if ($notifiable instanceof HasLocalePreference) {
            return $notifiable->preferredLocale();
        }

        $attribute = config('notifications.locale_attribute', 'locale');

        if (! $attribute || ! $notifiable instanceof Model) {
            return null;
        }

        if (! $notifiable->hasAttribute($attribute)) {
            return null;
        }

        $locale = $notifiable->getAttribute($attribute);

        return is_string($locale) ? $locale : null;
    }
}
