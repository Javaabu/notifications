---
title: Live updates
sidebar_position: 1
---

The bell and the notifications page hear about new notifications in one of two ways.

# Over Reverb

When `notifications.broadcasting.enabled` is `auto` (the default), live updates are on when `laravel/reverb` is installed and the default broadcaster isn't `null` or `log`. Set it to `true` to use another broadcaster (Pusher, Ably), or `false` to always poll:

```dotenv
NOTIFICATIONS_BROADCASTING=true
```

When they're on:

- Every notification that goes to the `database` channel also goes to `broadcast`. The bell and page get the `notification.created` broadcast type with the title, body, icon and colour, then fetch the latest notifications themselves.
- It's broadcast on the notifiable's private channel, `notifications.{type}.{id}`, for example `notifications.user.1` (with a morph map) or `notifications.App-Models-User.1`.
- The package authorizes that channel for that notifiable only, whatever its guard: a user and an admin with the same id get different channels.
- The front end listens with `window.Echo.private(channel).notification(…)`. Set up Laravel Echo as Laravel's broadcasting docs say, and make sure the broadcasting auth route works for every guard that has a bell:

```php
// bootstrap/app.php
->withBroadcasting(__DIR__ . '/../routes/channels.php', ['middleware' => ['web', 'auth:web,web_admin']])
```

Set `notifications.broadcasting.guards` to the guards that authorize the channel when it isn't the default.

Broadcasts are queued like any broadcast event, so run a queue worker, or use the `sync` queue.

# By polling

Without broadcasting, or when `window.Echo` isn't there, the bell and page ask the latest route every `notifications.broadcasting.poll_interval` milliseconds (30 seconds; at least 1 second), and skip it while the tab is hidden. The bell also refreshes every time it's opened.

# Configuration

```php
'broadcasting' => [
    'enabled' => env('NOTIFICATIONS_BROADCASTING', 'auto'), // auto, true, false
    'channel' => 'notifications',                          // the channel name prefix
    'guards' => null,                                      // guards that authorize the channel
    'poll_interval' => 30000,                              // ms
],
```

`Javaabu\Notifications\Support\Broadcasting` has `enabled()`, `pollInterval()`, `channelFor($notifiable)` and `channelPrefix()`, for your own front end.

Your notifiable's broadcast channel comes from `receivesBroadcastNotificationsOn()` in the `Notifiable` trait. If you override it, the bell won't hear about new notifications unless you pass your channel to it.
