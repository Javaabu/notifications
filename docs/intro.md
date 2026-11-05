---
title: Introduction
sidebar_position: 1.0
---

# Notifications

`javaabu/notifications` builds on Laravel's notifications. You write what a notification says once, and the package turns it into an email, a database notification, a broadcast, a Firebase push message and an SMS. Each notifiable gets the channels it can receive and hasn't turned off.

```php
use Javaabu\Notifications\BaseNotification;
use Javaabu\Notifications\Messages\NotificationMessage;

class OrderShippedNotification extends BaseNotification
{
    protected array $channels = ['database', 'mail', 'sms'];

    protected ?string $icon = 'send';

    public function __construct(
        public Order $order,
    ) {}

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

It also gives you:

- **A notifications page** that lists, filters, marks read or unread, and deletes notifications, in Blade ([javaabu/forms](https://github.com/Javaabu/forms)) and Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms) with [Onu](https://www.npmjs.com/package/@javaabu/onu)).
- **A bell** that shows the unread count and the latest notifications: `<x-notifications::bell />` in Blade, `<NotificationBell />` in React.
- **Live updates** over [Laravel Reverb](https://reverb.laravel.com) when it's installed, and polling when it isn't.
- **Click redirects**: opening a notification marks it read and goes to its URL.
- **Model associations**: each notification can be about a model (a `subject` morph), with an icon, a colour and a thumbnail.
- **Branded mail**: one config for your logo, colours and footer, and a mail theme that uses it, right to left for Dhivehi.
- **Preferences**: notifiables choose their channels per notification type.
- **Languages**: every notification is sent in the notifiable's language. With [javaabu/translatable](https://github.com/Javaabu/translatable) installed, database notifications are stored in every active language.
- **Permissions**: everyone manages their own notifications; support staff can be given `view_others_notifications` and `delete_others_notifications` through [javaabu/permissions](https://github.com/Javaabu/permissions).
- Any guard and any user model, overridable controllers, views and policy, and English and Dhivehi strings.
