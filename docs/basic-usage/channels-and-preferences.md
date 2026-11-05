---
title: Channels and preferences
sidebar_position: 2
---

# Choosing channels

A notification lists the channels it may go through in `$channels`. Without it, `notifications.channels` is used (`['database', 'mail']`).

```php
protected array $channels = ['database', 'mail', 'sms', 'fcm'];
```

When the notification is sent, `via()` keeps a channel only if:

1. **The notifiable can receive it.** `mail` needs an email address (`routeNotificationForMail()` or an `email` attribute), `sms` a phone number (`routeNotificationForSms()`), `fcm` a device token (`routeNotificationForFcm()`) and laravel-notification-channels/fcm installed, `database` a notifiable with `notifications()` (not an on-demand notifiable). Any other channel needs `routeNotificationFor{Channel}()` to return something.
2. **The notifiable wants it**, unless the channel is required. See [Preferences](#preferences).

Then, when live updates are on, `broadcast` is added after `database`, so the bell hears about it.

A user with an email address and no phone number, sent the notification above, gets `database` and `mail`.

Override `via()` (or `canReceive()` / `wants()`) when you need different rules. Laravel's `shouldSend($notifiable, $channel)` works too.

# SMS

The `sms` channel sends through [javaabu/sms-notifications](https://github.com/Javaabu/sms-notifications): it resolves to the channel of the driver in `sms.default` (Dhiraagu or Twilio), so install and configure that driver as its docs say. The notifiable implements `SmsNotifiable` and uses `HasSmsNumber`:

```php
use Javaabu\SmsNotifications\Notifiable\HasSmsNumber;
use Javaabu\SmsNotifications\Notifiable\SmsNotifiable;

class User extends Authenticatable implements SmsNotifiable
{
    use HasSmsNumber;
    use Notifiable;

    public function routeNotificationForSms(): string|array|null
    {
        return $this->phone;
    }
}
```

The text is the title, the body and the URL, separated by blank lines. Keep SMS short with `sms()`:

```php
NotificationMessage::make(__('Your order has shipped'))
    ->line(__('Order :number is on its way.', ['number' => $order->number]))
    ->sms(__('Order :number has shipped: :url', ['number' => $order->number, 'url' => route('orders.show', $order)]));
```

# Push notifications (FCM)

Install the channel, then configure Firebase as its docs say:

```bash
composer require laravel-notification-channels/fcm
```

Give the notifiable a device token and add `fcm` to the notification's channels:

```php
public function routeNotificationForFcm(): ?string
{
    return $this->fcm_token;
}
```

The push notification has the title, the body and the thumbnail as its image. Its data has the notification `id`, the `type` (the class), the `url` it opens, and every value from the message's `data()`, as strings (arrays are JSON). Change it with `fcm()`:

```php
use NotificationChannels\Fcm\FcmMessage;

NotificationMessage::make(__('Your order has shipped'))
    ->fcm(fn (FcmMessage $message) => $message->custom([
        'android' => ['notification' => ['channel_id' => 'orders']],
    ]));
```

Without the package installed, `fcm` is skipped.

# Preferences

A notifiable that implements `Javaabu\Notifications\Contracts\HasNotificationPreferences` is asked about every channel that isn't required:

```php
public function wantsNotificationVia(string $channel, Notification $notification): bool;
```

The `ManagesNotificationPreferences` trait implements it with a JSON column (`notification_preferences`, change it with `notifications.preferences.attribute`). Preferences are kept per notification type, with `*` for every type:

```php
$user->setNotificationPreference('order-shipped', 'mail', false) // no order-shipped mail
    ->setNotificationPreference('*', 'sms', false)                // no SMS at all…
    ->setNotificationPreference('order-shipped', 'sms', true)     // …except order-shipped
    ->save();

$user->notificationPreference('order-shipped', 'mail'); // false
$user->notificationPreference('welcome', 'mail');       // null: no preference, so on
$user->setNotificationPreference('order-shipped', 'mail', null)->save(); // clear it
```

A type's own preference wins over `*`, and a channel without a preference is on. The type key is the class name without "Notification", in kebab case: `OrderShippedNotification::preferenceKey()` is `order-shipped`.

A preferences form can list the types you send with `label()` and `preferenceKey()`:

```php
$types = [OrderShippedNotification::class, InvoiceDueNotification::class];

foreach ($types as $type) {
    echo $type::label(); // "Order shipped"
    $user->notificationPreference($type::preferenceKey(), 'mail');
}
```

## Required channels

Notifiables can't turn off the channels in `notifications.required_channels` (`['database']` by default), so the bell and the notifications page always have every notification. A notification can have its own:

```php
protected ?array $required_channels = ['database', 'mail'];
```
