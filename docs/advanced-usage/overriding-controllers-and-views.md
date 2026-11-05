---
title: Overriding controllers and views
sidebar_position: 4
---

# The controller

One controller, `Javaabu\Notifications\Http\Controllers\NotificationsController`, handles every route. Extend it and point the package at yours, for every `Route::notifications()`:

```php
// config/notifications.php
'controllers' => [
    'notifications' => App\Http\Controllers\NotificationsController::class,
],
```

or for one set of routes:

```php
Route::notifications(['controller' => App\Http\Controllers\AdminNotificationsController::class]);
```

or by binding it in the container:

```php
$this->app->bind(NotificationsController::class, App\Http\Controllers\NotificationsController::class);
```

Protected methods to override:

| Method | Does |
| --- | --- |
| `query(Request, Model $notifiable)` | The notifications listed (newest first), for the page, the bell and read-all |
| `findNotification(Request, string $id)` | Finds one notification |
| `notifiable(Request)` | Whose notifications are listed |
| `redirectUrl(Request, DatabaseNotification)` | Where opening a notification goes |
| `types(Request, Model)` | The type filter's options |
| `meta(Request, Model)` | What pages get to stay up to date |
| `strings()` | The Inertia page's text |

```php
namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Notifications\Http\Controllers\NotificationsController as BaseController;

class NotificationsController extends BaseController
{
    // Only the last 90 days
    protected function query(Request $request, Model $notifiable): Builder
    {
        return parent::query($request, $notifiable)->where('created_at', '>=', now()->subDays(90));
    }
}
```

# The model

Extend `Javaabu\Notifications\Models\DatabaseNotification` and set `notifications.models.notification`. The `Notifiable` trait, the controller, the bell and the policy all use it. Register your policy for your model if you change it.

# Blade views

Publish the views and edit the copies:

```bash
php artisan vendor:publish --tag=notifications-views
```

| View | |
| --- | --- |
| `notifications/index.blade.php` | The page |
| `notifications/partials/item.blade.php` | One notification, in the page and the bell |
| `components/bell.blade.php` | `<x-notifications::bell>` |
| `partials/icon.blade.php` | Notification icons |
| `mail/notification.blade.php`, `mail/theme.blade.php`, `mail/components/{html,text}/branded.blade.php` | [Branded mail](../basic-usage/branded-mail.md) |

Keep the `data-notifications-*` attributes in the bell; the script uses them.

# React pages and components

The React code is shipped through [javaabu/manifest](https://github.com/Javaabu/manifest), so any file can be overridden in `resources/vendor/notifications/js/…`:

```bash
php artisan manifest:override notifications js/Pages/Notifications/Index.tsx
php artisan manifest:override notifications js/components/NotificationListItem.tsx
```

Overrides win for the page and for imports inside the package, so overriding `NotificationListItem.tsx` changes it on the package's page too.

# Routes

`Route::notifications()` doesn't register anything you didn't ask for. Register only some routes by writing them yourself with the controller:

```php
Route::middleware(['web', 'auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/latest', [NotificationsController::class, 'latest'])->name('latest');
    Route::post('/read-all', [NotificationsController::class, 'readAll'])->name('read-all');
    Route::get('/{notification}', [NotificationsController::class, 'show'])->name('show');
});
```

The views link to every route by name, so keep the names of the ones they use.
