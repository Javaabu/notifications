---
title: Translations
sidebar_position: 2
---

Without anything installed, every notification is sent in its notifiable's language (see [Languages](../basic-usage/writing-notifications.md#languages)) and stored in that language.

With [javaabu/translatable](https://github.com/Javaabu/translatable) v3 installed and at least one language active, two things change:

1. **Only active languages are used.** A notifiable whose locale isn't one of translatable's active languages is notified in translatable's default language.
2. **Database notifications are stored in every active language.** The title, body and action text are rendered once per language (with `message()` running in each), and kept under `translations` in the data. The bell, the page and `title()`, `body()` and `actionText()` show the reader's current language, so an English-speaking admin and a Dhivehi-speaking user see the same notification in their own language.

```php
$notification->title();     // in the current locale
$notification->title('dv'); // in Dhivehi
$notification->data['translations']; // ['en' => ['title' => …, 'body' => …, 'action_text' => …], 'dv' => [...]]
```

Mail, SMS and push notifications are sent in the notifiable's language only.

Write `message()` with `__()` so every language has its text:

```php
public function message(object $notifiable): NotificationMessage
{
    return NotificationMessage::make(__('Your order has shipped'))
        ->line(__('Order :number is on its way.', ['number' => $this->order->number]));
}
```

Data that doesn't change with the language (URLs, icons, `data()`) is stored once.

# Turning it off

```php
// config/notifications.php
'translations' => false,
```

The package then works as if translatable weren't installed. It's detected with `Javaabu\Notifications\Support\Translations::enabled()`, which is false when the package isn't installed, `translatable.enabled` is off, there are no active languages, or `notifications.translations` is false.

# Testing

```php
use Javaabu\Translatable\Testing\InteractsWithLanguages;

class OrderNotificationsTest extends TestCase
{
    use InteractsWithLanguages;

    #[Test]
    public function it_stores_the_dhivehi_title(): void
    {
        $this->setUpLanguages(['en', 'dv']);

        $user->notifyNow(new OrderShippedNotification($order), ['database']);

        $this->assertEquals('ތިބާގެ އޯޑަރު ފޮނުވައިފި', $user->notifications()->first()->title('dv'));
    }
}
```
