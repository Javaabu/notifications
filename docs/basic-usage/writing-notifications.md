---
title: Writing notifications
sidebar_position: 1
---

A notification extends `Javaabu\Notifications\BaseNotification` and says what it has to say once, in `message()`. The package builds every channel's format from it.

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

    protected ?string $color = 'success';

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
            ->line(__('It should reach you in 2 days.'))
            ->action(__('Track order'), route('orders.show', $this->order))
            ->subject($this->order)
            ->data(['order_id' => $this->order->id]);
    }
}
```

Send it the Laravel way:

```php
$user->notify(new OrderShippedNotification($order));

Notification::send($users, new OrderShippedNotification($order));

Notification::route('mail', 'customer@example.com')->notify(new OrderShippedNotification($order));
```

Implement `ShouldQueue` to send it from the queue, as with any Laravel notification. `BaseNotification` already uses `Queueable`.

# The message

`NotificationMessage::make($title)` starts a message. Every method returns the message.

| Method | Used for |
| --- | --- |
| `title(string)` | The mail subject, the title in the bell and on the page, the push title, the first line of the SMS |
| `line(?string)` | Adds a paragraph to the body. Empty lines are skipped. |
| `lines(array)` | Adds several paragraphs |
| `body(?string)` | Replaces the body with one paragraph |
| `action(string $text, string $url)` | The mail button, and where opening the notification goes |
| `url(?string)` | Where opening the notification goes, without a mail button |
| `icon(?string)` | The icon in the bell and on the page (see [Icons](database-notifications.md#icons-colours-and-thumbnails)) |
| `color(?string)` | `primary`, `secondary`, `success`, `info`, `warning`, `danger` or `neutral` |
| `thumbnail(?string)` | An image URL shown instead of the icon, and in push notifications |
| `subject(?Model)` | The model the notification is about (see [Database notifications](database-notifications.md)) |
| `level(string)`, `success()`, `error()` | The mail button colour. `success()` and `error()` also set the colour to `success` / `danger` unless you set one. |
| `greeting(?string)`, `salutation(?string)` | The mail's first and last lines (the defaults are "Hello!" and "Regards," with your app's name) |
| `data(array)` | Extra data kept with database notifications and sent with push notifications |
| `sms(?string)` | The SMS text, instead of the title, body and URL |
| `mail(?Closure)` | Change the `MailMessage`: `fn (MailMessage $mail) => $mail->cc('…')->attach('…')` |
| `fcm(?Closure)` | Change the `FcmMessage`: `fn (FcmMessage $message) => $message->custom([...])` |

`message()` receives the notifiable, so a message can depend on who reads it:

```php
public function message(object $notifiable): NotificationMessage
{
    return NotificationMessage::make(__('New comment on :title', ['title' => $this->post->title]))
        ->body($this->comment->body)
        ->url(route('posts.show', $this->post) . '#comment-' . $this->comment->id)
        ->greeting(__('Hello :name,', ['name' => $notifiable->name]));
}
```

# What each channel gets

| Channel | Built by | What's sent |
| --- | --- | --- |
| `mail` | `toMail()` | A [branded](branded-mail.md) `MailMessage`: the title as subject, the lines, the action button, the greeting and salutation |
| `database` | `toDatabase()` | The title, body, action, icon, colour, thumbnail, level and data, and the subject in its own columns |
| `broadcast` | `toBroadcast()` | The title, body, icon and colour, as the `notification.created` type. Added by itself when [live updates](../advanced-usage/live-updates.md) are on. |
| `sms` | `toSms()` | `sms()`, or the title, body and URL separated by blank lines |
| `fcm` | `toFcm()` | A push notification with the title, body and thumbnail, and the id, type, URL and data as string data |

Any of them can still be overridden the Laravel way, for a channel that needs something the message can't say:

```php
public function toMail(object $notifiable): MailMessage
{
    return parent::toMail($notifiable)->attach(storage_path("invoices/{$this->order->id}.pdf"));
}
```

`toArray()` returns the same as the database payload without the per-language texts, so `Notification::fake()` assertions and custom channels can use it.

# Icons and colours for a type

Set `$icon` and `$color` on the class for every notification of that type; the message's own `icon()` and `color()` win over them. Without either, `notifications.defaults` is used (`bell`, `primary`).

# A label for the type

`label()` names the type on the notifications page's filter, and anywhere you list notification types, for example a preferences page. By default it's the class name without "Notification", as words: `OrderShippedNotification` → "Order shipped". Override it to translate it:

```php
public static function label(): string
{
    return __('Order shipped');
}
```

# Languages

Each notifiable is notified in its own language. `message()` runs inside that locale, so `__()` returns the notifiable's language, and the mail layout's own words (greeting, salutation, footer) and its direction follow it.

The locale comes from:

1. Laravel's `HasLocalePreference`: `preferredLocale()` on the notifiable.
2. Otherwise, the attribute in `notifications.locale_attribute` (`locale` by default), when the model has it.
3. Otherwise, the app's current locale.

```php
class User extends Authenticatable implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return $this->language ?? 'dv';
    }
}
```

With [javaabu/translatable](../advanced-usage/translations.md) installed, only its active languages count, and database notifications are stored in all of them.
