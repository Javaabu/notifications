---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

```bash
composer require javaabu/notifications
```

The service provider is discovered automatically.

# Migrating

Database notifications use Laravel's `notifications` table, with two more columns for the model a notification is about (`subject_type`, `subject_id`). The package loads its migrations itself:

```bash
php artisan migrate
```

- If the app has no `notifications` table yet, the first migration creates Laravel's.
- The second adds the `subject` columns to the table, new or existing.

If your notifiable models have UUID, ULID or string keys and the package is creating the table, set `notifiable_key_type` before migrating:

```bash
php artisan vendor:publish --tag=notifications-config
```

```php
// config/notifications.php
'notifiable_key_type' => 'uuid', // integer (default), uuid, ulid or string
```

To change the migrations, publish them and stop the package from loading its copies:

```bash
php artisan vendor:publish --tag=notifications-migrations
```

```php
// AppServiceProvider::register()
\Javaabu\Notifications\Notifications::ignoreMigrations();
```

# Preparing your notifiable models

Use the package's `Notifiable` trait **instead of** Laravel's `Illuminate\Notifications\Notifiable`. It has the same methods (`notify()`, `notifications()`, `unreadNotifications()`, …), returns the package's notification model, and broadcasts on a channel that works with any guard.

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Notifications\Concerns\ManagesNotificationPreferences;
use Javaabu\Notifications\Concerns\Notifiable;
use Javaabu\Notifications\Contracts\HasNotificationPreferences;
use Javaabu\SmsNotifications\Notifiable\HasSmsNumber;
use Javaabu\SmsNotifications\Notifiable\SmsNotifiable;

class User extends Authenticatable implements HasNotificationPreferences, SmsNotifiable
{
    use HasSmsNumber;
    use ManagesNotificationPreferences;
    use Notifiable;

    // The number SMS notifications go to
    public function routeNotificationForSms(): string|array|null
    {
        return $this->phone;
    }

    // The device token push notifications go to (laravel-notification-channels/fcm)
    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token;
    }
}
```

Only `Notifiable` is required. The rest is for SMS ([javaabu/sms-notifications](https://github.com/Javaabu/sms-notifications)), push notifications and [preferences](basic-usage/channels-and-preferences.md#preferences). `ManagesNotificationPreferences` keeps the preferences in a JSON column; add it to your users table:

```php
$table->json('notification_preferences')->nullable();
```

Notifiables are notified in their own language: Laravel's `HasLocalePreference`, or a `locale` attribute. See [Languages](basic-usage/writing-notifications.md#languages).

# Routes

Register the notification routes inside your app's routes, with the middleware, prefix and guard you use:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::notifications(); // /notifications, named notifications.*, behind `auth`
});

// An admin portal on another guard
Route::middleware('web')->prefix('admin')->name('admin.')->group(function () {
    Route::notifications(['guard' => 'web_admin']); // /admin/notifications, admin.notifications.*
});
```

See [The notifications page](basic-usage/notifications-page.md) for every route and option.

# Permissions

Everyone can see and manage their own notifications without a permission. The package registers two permissions for seeing and deleting other users' notifications, with [javaabu/permissions](https://github.com/Javaabu/permissions). Write them to the database, as on every deploy:

```bash
php artisan permissions:sync
```

| Permission | Allows |
| --- | --- |
| `view_others_notifications` | Listing and opening other users' notifications |
| `delete_others_notifications` | Deleting other users' notifications |

See [Permissions and policies](advanced-usage/permissions-and-policies.md).

# The bell and the page

## Blade

Put the bell in your layout:

```blade
<x-notifications::bell />
```

The page and the bell load a small script through [javaabu/manifest](https://github.com/Javaabu/manifest), which adds it to your Vite build. It needs no npm packages of its own, so `php artisan manifest:check` doesn't ask a Blade app for React or `@javaabu/forms`. The views use Tailwind classes with Onu's tokens (`bg-card`, `text-muted-foreground`, …); add the package's views to your Tailwind sources:

```css
/* resources/css/app.css */
@source '../../vendor/javaabu/notifications/resources/views/**/*.blade.php';
```

The page extends `layouts.app` and fills its `content` section. Change both in config (`layout`, `layout_section`).

## Inertia React

Switch the pages to Inertia:

```dotenv
NOTIFICATIONS_VIEWS=inertia
```

Resolve package pages with manifest's `resolvePage()` (see javaabu/manifest), then share the bell's data with every page:

```php
// app/Http/Middleware/HandleInertiaRequests.php
use Javaabu\Notifications\Support\BellData;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'notifications' => fn () => BellData::make($request->user()),
    ];
}
```

```tsx
import { NotificationBell } from "@javaabu/notifications";

<NotificationBell bell={props.notifications} />
```

Install the npm packages the components use (`php artisan manifest:check` lists them once `@inertiajs/react` is in your `package.json`, and prints the command), and add the package's components to your Tailwind sources:

```css
@source '../../vendor/javaabu/notifications/resources/js/**/*.tsx';
```

# Live updates

With [Laravel Reverb](https://reverb.laravel.com) installed and set as the default broadcaster, new notifications reach the bell straight away; Laravel Echo must be on `window.Echo`. Without it, the bell polls every 30 seconds. See [Live updates](advanced-usage/live-updates.md).

# Mail branding

Set your logo, colours and footer once:

```php
// config/notifications.php
'mail' => [
    'branding' => [
        'name' => 'Javaabu',
        'url' => 'https://javaabu.com',
        'logo' => 'https://javaabu.com/images/logo.png',
        'colors' => ['primary' => '#0f766e'],
        'footer' => 'Javaabu Pvt. Ltd., Malé, Maldives',
    ],
],
```

See [Branded mail](basic-usage/branded-mail.md).

# Publishing

| Tag | What |
| --- | --- |
| `notifications-config` | `config/notifications.php` |
| `notifications-migrations` | The migrations |
| `notifications-views` | The Blade views, to `resources/views/vendor/notifications` |

Strings are JSON translations: change any of them by adding the same key to your app's `lang/en.json` or `lang/dv.json`. The React page and components are overridden through javaabu/manifest; see [Overriding controllers and views](advanced-usage/overriding-controllers-and-views.md).
