<?php

namespace Javaabu\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Broadcast;

/**
 * Whether new notifications are broadcast (over Laravel Reverb), the channels they use,
 * and how often the bell polls when they aren't
 */
class Broadcasting
{
    /** The class whose presence means laravel/reverb is installed */
    public static string $reverb_class = 'Laravel\\Reverb\\ReverbServiceProvider';

    /**
     * `notifications.broadcasting.enabled`: true, false, or 'auto' (when laravel/reverb is
     * installed and the default broadcaster isn't null or log)
     */
    public static function enabled(): bool
    {
        $enabled = config('notifications.broadcasting.enabled', 'auto');

        if ($enabled !== 'auto') {
            return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        if (! class_exists(static::$reverb_class)) {
            return false;
        }

        return ! in_array(config('broadcasting.default'), [null, 'null', 'log'], true);
    }

    /**
     * Milliseconds between checks for new notifications when the bell polls
     */
    public static function pollInterval(): int
    {
        return max((int) config('notifications.broadcasting.poll_interval', 30000), 1000);
    }

    public static function channelPrefix(): string
    {
        return (string) config('notifications.broadcasting.channel', 'notifications');
    }

    /**
     * The private channel of a notifiable: `notifications.App-Models-User.1`
     */
    public static function channelFor(Model $notifiable): string
    {
        $type = static::encodeType($notifiable->getMorphClass());

        return static::channelPrefix() . ".{$type}.{$notifiable->getKey()}";
    }

    /**
     * Authorize `notifications.{type}.{id}` for the notifiable itself, whichever guard it uses
     */
    public static function registerChannels(): void
    {
        $options = array_filter(['guards' => config('notifications.broadcasting.guards')]);

        Broadcast::channel(static::channelPrefix() . '.{type}.{id}', function (Model $user, string $type, string $id) {
            return static::encodeType($user->getMorphClass()) === $type
                && (string) $user->getKey() === $id;
        }, $options);
    }

    /**
     * A morph type that's safe in a channel name: `App\Models\User` → `App-Models-User`
     */
    public static function encodeType(string $type): string
    {
        return str_replace(['\\', '.'], ['-', '='], $type);
    }
}
