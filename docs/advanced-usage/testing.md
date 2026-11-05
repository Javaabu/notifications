---
title: Testing
sidebar_position: 6
---

Notifications are Laravel notifications, so Laravel's testing tools work.

# Which channels

```php
use Illuminate\Support\Facades\Notification;

Notification::fake();

$user->notify(new OrderShippedNotification($order));

Notification::assertSentTo($user, OrderShippedNotification::class, function (OrderShippedNotification $notification, array $channels) {
    return $channels === ['database', 'mail'];
});
```

`via()` is a plain method, so you can also check the channels for a notifiable directly, including preferences:

```php
$user->setNotificationPreference('order-shipped', 'mail', false)->save();

$this->assertEquals(['database'], (new OrderShippedNotification($order))->via($user));
```

The `sms` channel appears as the SMS driver's channel class (`config('sms.channels.dhiraagu')`), and `fcm` as `NotificationChannels\Fcm\FcmChannel`.

# What it says

Build any channel's format without sending:

```php
$notification = new OrderShippedNotification($order);

$this->assertEquals('Your order has shipped', $notification->toMail($user)->subject);
$this->assertStringContainsString('ORD-1', $notification->toSms($user));
$this->assertEquals('send', $notification->toDatabase($user)['icon']);

$html = (string) $notification->toMail($user)->render(); // the branded HTML
```

Or send it to the database and read it back:

```php
$user->notifyNow(new OrderShippedNotification($order), ['database']);

$notification = $user->notifications()->first();

$this->assertEquals('Your order has shipped', $notification->title());
$this->assertTrue($notification->subject->is($order));
```

# Languages

Give the notifiable a locale and assert in Thaana:

```php
$user = User::factory()->create(['locale' => 'dv']);

$this->assertEquals('ތިބާގެ އޯޑަރު ފޮނުވައިފި', (new OrderShippedNotification($order))->toMail($user)->subject);
```

# The routes

```php
$this->actingAs($user)
    ->getJson(route('notifications.latest'))
    ->assertJsonPath('meta.unread_count', 1);

$this->actingAs($user)
    ->get(route('notifications.show', $notification->id))
    ->assertRedirect(route('orders.show', $order));
```

On a second guard, remember that `actingAs($admin, 'web_admin')` also makes `web_admin` the default guard for the rest of the test.

The Blade page and bell load a script through javaabu/manifest; call `Javaabu\Manifest\Facades\Manifest::fake()` in tests that render them without a Vite build.
