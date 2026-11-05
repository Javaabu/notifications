# Notifications

[![Tests](https://github.com/Javaabu/notifications/actions/workflows/tests.yml/badge.svg)](https://github.com/Javaabu/notifications/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/Javaabu/notifications/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/notifications)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/notifications.svg)](https://packagist.org/packages/javaabu/notifications)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

Notifications for Laravel, built on Laravel's own. Define what a notification says once and send it by mail, database, broadcast, FCM push and SMS ([javaabu/sms-notifications](https://github.com/Javaabu/sms-notifications)), through the channels each notifiable can receive and hasn't turned off, in its own language. With a notifications page and a live bell in Blade ([javaabu/forms](https://github.com/Javaabu/forms)) and Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms) and Onu), branded mail, Reverb or polling, and Dhivehi built in.

```php
class OrderShippedNotification extends BaseNotification
{
    protected array $channels = ['database', 'mail', 'sms'];

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
Route::notifications(); // the notifications page, the bell's JSON, mark read, delete
```

```blade
<x-notifications::bell />
```

```tsx
<NotificationBell bell={props.notifications} />
```

## Documentation

You'll find the documentation on [https://docs.javaabu.com/docs/notifications](https://docs.javaabu.com/docs/notifications), and in [`docs/`](docs/intro.md). AI assistants can start from [`llms.txt`](llms.txt); Laravel Boost picks up the guideline and skill in `resources/boost`.

## Testing

```bash
composer test
pnpm install && pnpm test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
