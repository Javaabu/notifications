<?php

use Javaabu\Notifications\Http\Controllers\NotificationsController;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Policies\NotificationPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    |
    | The channels a BaseNotification is sent through when it doesn't list its
    | own ($channels): database, mail, sms (javaabu/sms-notifications), fcm
    | (laravel-notification-channels/fcm) or any Laravel channel. A channel is
    | skipped when the notifiable can't receive it (no email address, no phone
    | number) or has turned it off. Notifiables can't turn off the required
    | channels. broadcast is added by itself when live updates are on.
    |
    */

    'channels' => ['database', 'mail'],

    'required_channels' => ['database'],

    /*
    |--------------------------------------------------------------------------
    | Preferences
    |--------------------------------------------------------------------------
    |
    | The JSON column the ManagesNotificationPreferences trait keeps a
    | notifiable's channel choices in.
    |
    */

    'preferences' => [
        'attribute' => 'notification_preferences',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale
    |--------------------------------------------------------------------------
    |
    | Notifiables are notified in their preferredLocale() (Laravel's
    | HasLocalePreference), or in this attribute when they don't implement it.
    | null turns the attribute off.
    |
    | With javaabu/translatable installed and translations on, only its active
    | languages are used, and database notifications keep their texts in every
    | active language so each reader sees their own.
    |
    */

    'locale_attribute' => 'locale',

    'translations' => true,

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | The icon and colour of notifications that don't set their own. Colours
    | are primary, secondary, success, info, warning, danger or neutral.
    |
    */

    'defaults' => [
        'icon' => 'bell',
        'color' => 'primary',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail branding
    |--------------------------------------------------------------------------
    |
    | Every BaseNotification mail uses the branded layout and theme. Set your
    | logo (an absolute URL), colours and footer once here. Mailables can use
    | the same look with `<x-mail::branded>` and the theme below.
    |
    */

    'mail' => [
        'branded' => true,

        'theme' => 'notifications::mail.theme',

        'branding' => [
            'name' => env('APP_NAME', 'Laravel'),
            'url' => env('APP_URL', 'http://localhost'),
            'logo' => null,
            'logo_height' => 48,
            'colors' => [
                'primary' => '#0f766e',
                'primary_text' => '#ffffff',
                'background' => '#f4f5f7',
                'body' => '#ffffff',
                'text' => '#1f2937',
                'muted' => '#6b7280',
                'success' => '#15803d',
                'error' => '#b91c1c',
            ],
            'footer' => null,
        ],

        'rtl_locales' => ['dv', 'ar', 'fa', 'he', 'ur'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | notifiable_key_type is the key type of your notifiables (integer, uuid,
    | ulid or string), used when the package creates the notifications table.
    | subject_columns saves the model a notification is about in subject_type
    | and subject_id.
    |
    */

    'table' => 'notifications',

    'notifiable_key_type' => 'integer',

    'subject_columns' => true,

    'models' => [
        'notification' => DatabaseNotification::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | The bell
    |--------------------------------------------------------------------------
    |
    | How many notifications the latest-notifications component shows.
    |
    */

    'latest_limit' => 10,

    'per_page' => 20,

    /*
    |--------------------------------------------------------------------------
    | Live updates
    |--------------------------------------------------------------------------
    |
    | New notifications are broadcast on a private channel per notifiable when
    | enabled is true, or when it's 'auto' and laravel/reverb is installed with
    | a real broadcaster as the default. Otherwise the bell polls the latest
    | route every poll_interval ms. guards are the guards that authorize the
    | channels (null: the defaults).
    |
    */

    'broadcasting' => [
        'enabled' => env('NOTIFICATIONS_BROADCASTING', 'auto'),
        'channel' => 'notifications',
        'guards' => null,
        'poll_interval' => 30000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | The pages the notification routes render: blade (javaabu/forms
    | components) or inertia (React pages shipped through javaabu/manifest).
    | Requests that expect JSON always get JSON.
    |
    */

    'views' => env('NOTIFICATIONS_VIEWS', 'blade'),

    /*
    | The Blade layout the pages extend, and the section they fill.
    */

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    /*
    |--------------------------------------------------------------------------
    | Controllers and authorization
    |--------------------------------------------------------------------------
    |
    | Replace the controller with a subclass, or pass `controller` to
    | Route::notifications(). Everyone manages their own notifications; the
    | permissions view_others_notifications and delete_others_notifications
    | allow it for others'. The policy here is the package default; apps can
    | also override it with config('permissions.policies') or Gate::policy().
    | Turn off register_permissions to register your own.
    |
    */

    'controllers' => [
        'notifications' => NotificationsController::class,
    ],

    'policy' => NotificationPolicy::class,

    'register_permissions' => true,

];
