<?php

namespace Javaabu\Notifications;

use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Support\ServiceProvider;
use Javaabu\Notifications\Channels\DatabaseChannel;
use Javaabu\Notifications\Support\Broadcasting;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notifications.php', 'notifications');

        $this->app->bind(IlluminateDatabaseChannel::class, DatabaseChannel::class);
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'notifications');

        $this->registerMailComponents();
        $this->registerBroadcastChannels();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
            $this->registerPublishing();
        }
    }

    /**
     * Adds the package's mail components (`<x-mail::branded>`) after the app's own
     */
    protected function registerMailComponents(): void
    {
        $this->callAfterResolving(Markdown::class, function (Markdown $markdown) {
            $markdown->loadComponentsFrom([
                ...config('mail.markdown.paths', []),
                __DIR__ . '/../resources/views/mail/components',
            ]);
        });
    }

    /**
     * Authorize each notifiable's private channel when broadcasting is on (Reverb installed, or forced)
     */
    protected function registerBroadcastChannels(): void
    {
        if (! Broadcasting::enabled()) {
            return;
        }

        Broadcasting::registerChannels();
    }

    protected function registerMigrations(): void
    {
        if (Notifications::$runs_migrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/notifications.php' => config_path('notifications.php'),
        ], 'notifications-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'notifications-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/notifications'),
        ], 'notifications-views');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/notifications'),
        ], 'notifications-translations');
    }
}
