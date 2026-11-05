<?php

namespace Javaabu\Notifications;

use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Channels\DatabaseChannel as IlluminateDatabaseChannel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Notifications\Channels\DatabaseChannel;
use Javaabu\Notifications\Policies\NotificationPolicy;
use Javaabu\Notifications\Routing\NotificationRoutes;
use Javaabu\Notifications\Support\Broadcasting;
use Javaabu\Permissions\Permissions;

class NotificationsServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/notifications.php', 'notifications');

        $this->app->bind(IlluminateDatabaseChannel::class, DatabaseChannel::class);
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'notifications');

        $this->registerFrontendAssets();
        $this->registerMailComponents();
        $this->registerRouteMacros();
        $this->registerPermissions();
        $this->registerBroadcastChannels();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
            $this->registerPublishing();
        }
    }

    /**
     * The Inertia pages (notifications::Notifications/Index), the components apps import as
     * `@javaabu/notifications`, and the script the Blade page and bell load
     */
    protected function registerFrontendAssets(): void
    {
        $this->registerAssets('notifications', __DIR__ . '/../resources', function (Package $package) {
            $package
                ->script('notifications', 'js/blade/notifications.ts')
                ->js()
                ->pages()
                ->npm([
                    '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
                    '@javaabu/onu' => '^0.2.1',
                ], peer_dependencies: [
                    'react' => '^18.3 || ^19.0',
                    'react-dom' => '^18.3 || ^19.0',
                    '@inertiajs/react' => '^2.0 || ^3.0',
                ]);
        });
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

    protected function registerRouteMacros(): void
    {
        Route::macro('notifications', function (array $options = []) {
            NotificationRoutes::register($this, $options);
        });
    }

    /**
     * view_others_notifications and delete_others_notifications (everyone manages their own
     * notifications without a permission), and the default policy, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (! config('notifications.register_permissions', true)) {
            return;
        }

        Permissions::register('notifications', [
            'view_others_notifications' => 'View other users\' notifications',
            'delete_others_notifications' => 'Delete other users\' notifications',
        ], label: 'Notifications');

        Permissions::policy(Notifications::notificationModel(), config('notifications.policy', NotificationPolicy::class));
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
