# Common mistakes with javaabu/notifications

## Writing every channel by hand

```php
// Wrong: the same text three times, and they drift apart
public function toMail($notifiable) { return (new MailMessage)->subject('Order shipped')->line('…'); }
public function toArray($notifiable) { return ['title' => 'Order shipped', 'body' => '…']; }
public function toSms($notifiable): string { return 'Order shipped …'; }

// Right
public function message(object $notifiable): NotificationMessage
{
    return NotificationMessage::make(__('Order shipped'))->line(__('…'));
}
```

## Filtering channels yourself

```php
// Wrong: BaseNotification already skips channels without a route and channels turned off
public function via(object $notifiable): array
{
    return $notifiable->phone ? ['database', 'sms'] : ['database'];
}

// Right
protected array $channels = ['database', 'sms'];
```

## Laravel's Notifiable trait

```php
// Wrong: notifications() returns Laravel's model, without the package's texts, subject and tone
use Illuminate\Notifications\Notifiable;

// Right
use Javaabu\Notifications\Concerns\Notifiable;
```

## Untranslated text, or translating outside message()

```php
// Wrong: always English, and built once in the sender's locale
public function __construct(public Order $order)
{
    $this->title = "Order {$order->number} shipped";
}

// Right: message() runs in each notifiable's locale
public function message(object $notifiable): NotificationMessage
{
    return NotificationMessage::make(__('Order :number shipped', ['number' => $this->order->number]));
}
```

## Linking straight to the URL

```blade
{{-- Wrong: the notification stays unread --}}
<a href="{{ $notification->actionUrl() }}">{{ $notification->title() }}</a>

{{-- Right --}}
<a href="{{ route('notifications.show', $notification->id) }}">{{ $notification->title() }}</a>
```

## Reading `data` directly

```php
// Wrong: misses the reader's language and the defaults
$notification->data['title'];

// Right
$notification->title();
$notification->icon();
```

## Registering routes outside a web group, or once for every guard

```php
// Wrong: no session, and admins land on the user routes
Route::notifications();             // in routes/api.php

// Right: one set per guard, inside web
Route::middleware('web')->group(fn () => Route::notifications());
Route::middleware('web')->group(fn () => Route::notifications(['guard' => 'web_admin', 'prefix' => 'admin/notifications', 'name' => 'admin.notifications']));
```

## Lucide or Font Awesome icon names

```php
// Wrong: not Onu names, so React shows a fallback icon
->icon('fa-truck')

// Right
->icon('send')
```

## An SVG or relative logo in mail

```php
// Wrong: many clients don't show SVG, and a relative URL doesn't resolve in an inbox
'logo' => '/images/logo.svg',

// Right
'logo' => 'https://example.com/images/logo.png',
```
