---
name: javaabu-notifications
description: Write and send Laravel notifications with javaabu/notifications. Use whenever the project depends on javaabu/notifications and the task writes or changes a notification, chooses its channels (mail, database, SMS, FCM push, broadcast), adds notification preferences, makes a model notifiable, shows the notification bell or the notifications page in Blade or Inertia React, links notifications to models, brands notification mail, sends notifications in Dhivehi, or tests notifications.
license: MIT
metadata:
  author: Javaabu
---

# Notifications with javaabu/notifications

A notification says what it has to say once, in `message()`, and the package turns it into every channel's format. The docs ship with the package: `vendor/javaabu/notifications/llms.txt` lists every page in `vendor/javaabu/notifications/docs/`.

## Writing a notification

```php
namespace App\Notifications;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Javaabu\Notifications\BaseNotification;
use Javaabu\Notifications\Messages\NotificationMessage;

class OrderShippedNotification extends BaseNotification implements ShouldQueue
{
    protected array $channels = ['database', 'mail', 'sms', 'fcm'];

    protected ?string $icon = 'send';

    public function __construct(
        public Order $order,
    ) {}

    public static function label(): string
    {
        return __('Order shipped');
    }

    public function message(object $notifiable): NotificationMessage
    {
        return NotificationMessage::make(__('Your order has shipped'))
            ->line(__('Order :number is on its way.', ['number' => $this->order->number]))
            ->action(__('Track order'), route('orders.show', $this->order))
            ->subject($this->order)
            ->success();
    }
}
```

```php
$user->notify(new OrderShippedNotification($order));
```

- `message()` runs in the notifiable's locale (`HasLocalePreference`, or its `locale` attribute): put every string through `__()`.
- Message methods: `title`, `line`, `lines`, `body`, `action`, `url`, `icon`, `color`, `thumbnail`, `subject`, `success`, `error`, `level`, `greeting`, `salutation`, `data`, `sms` (short SMS text), `mail(fn (MailMessage $mail) => …)`, `fcm(fn (FcmMessage $m) => …)`.
- Icons are Onu semantic names (`bell`, `mail`, `send`, `user`, `calendar`, `success`, `warning`, `error`, `info`, `message`, `megaphone`). Colours: `primary`, `secondary`, `success`, `info`, `warning`, `danger`, `neutral`.
- `->subject($model)` saves the model as a morph. Implement `Javaabu\Notifications\Contracts\NotificationSubject` (`notificationUrl()`, `notificationThumbnail()`) on models notifications are often about, and skip `->url()` in each notification.

## Notifiable models

```php
use Javaabu\Notifications\Concerns\ManagesNotificationPreferences;
use Javaabu\Notifications\Concerns\Notifiable;
use Javaabu\Notifications\Contracts\HasNotificationPreferences;
use Javaabu\SmsNotifications\Notifiable\HasSmsNumber;
use Javaabu\SmsNotifications\Notifiable\SmsNotifiable;

class User extends Authenticatable implements HasNotificationPreferences, SmsNotifiable
{
    use HasSmsNumber;
    use ManagesNotificationPreferences; // notification_preferences JSON column
    use Notifiable;                     // instead of Illuminate\Notifications\Notifiable

    public function routeNotificationForSms(): string|array|null
    {
        return $this->phone;
    }

    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token;
    }
}
```

## Channels and preferences

`via()` keeps a listed channel when the notifiable can receive it (email, phone, FCM token and the FCM package installed, a `notifications()` relation) and hasn't turned it off. `database` is required (`notifications.required_channels`). `broadcast` is added after `database` when live updates are on. `sms` goes through javaabu/sms-notifications' configured driver.

```php
$user->setNotificationPreference('order-shipped', 'mail', false)->save(); // OrderShippedNotification::preferenceKey()
$user->setNotificationPreference('*', 'sms', false)->save();               // every type
```

## The page and the bell

```php
// routes/web.php, inside the web group; once per guard
Route::notifications();
Route::notifications(['guard' => 'web_admin', 'prefix' => 'admin/notifications', 'name' => 'admin.notifications']);
```

```blade
<x-notifications::bell />
<x-notifications::bell guard="web_admin" routes="admin.notifications" />
```

```php
// HandleInertiaRequests::share(), with notifications.views = inertia
'notifications' => fn () => \Javaabu\Notifications\Support\BellData::make($request->user()),
```

```tsx
import { NotificationBell } from "@javaabu/notifications";

{props.notifications ? <NotificationBell bell={props.notifications} /> : null}
```

Link to a notification with `route('notifications.show', $notification->id)` (or `links.show` in JSON): it marks it read and redirects. Live updates use Reverb when installed (Echo on `window.Echo`), polling otherwise. A Blade app needs no npm packages for the page or bell beyond Vite; the React page and components need `@javaabu/forms`, `@javaabu/onu`, React and `@inertiajs/react` (`php artisan manifest:check` lists them for Inertia apps).

## Mail

Brand once in `config/notifications.php` → `mail.branding` (`name`, `url`, `logo` as an absolute URL, `colors.primary`, `footer`). Dhivehi mail is right to left by itself. Mailables: `public $theme = 'notifications::mail.theme';` and `<x-mail::branded>` instead of `<x-mail::message>`.

## Testing

```php
Notification::fake();
$user->notify(new OrderShippedNotification($order));
Notification::assertSentTo($user, OrderShippedNotification::class, fn ($notification, array $channels) => $channels === ['database', 'mail']);

$user = User::factory()->create(['locale' => 'dv']);
$user->notifyNow(new OrderShippedNotification($order), ['database']);
$this->assertEquals('ތިބާގެ އޯޑަރު ފޮނުވައިފި', $user->notifications()->first()->title());
```

More: [common mistakes](references/common-mistakes.md).
