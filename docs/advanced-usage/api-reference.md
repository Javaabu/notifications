---
title: API reference
sidebar_position: 20
---

# PHP

## `Javaabu\Notifications\BaseNotification`

Extends `Illuminate\Notifications\Notification`, implements javaabu/sms-notifications' `SmsNotification`, uses `Queueable` and `SendsSms`.

| Member | |
| --- | --- |
| `protected array $channels = []` | The channels; empty means `notifications.channels` |
| `protected ?array $required_channels = null` | Channels that can't be turned off; null means `notifications.required_channels` |
| `protected ?string $icon`, `protected ?string $color` | Defaults for the message |
| `abstract message(object $notifiable): NotificationMessage` | What it says, called in the notifiable's locale |
| `static label(): string` | The type's name |
| `static preferenceKey(): string` | The key preferences use (`order-shipped`) |
| `static preferenceKeyFor(string $class): string` | The same for any notification class |
| `channels(): array`, `requiredChannels(): array` | |
| `via(object $notifiable): array` | The channels it's sent through |
| `canReceive(object $notifiable, string $channel): bool` | Whether the notifiable has a route for the channel |
| `wants(object $notifiable, string $channel): bool` | Required, or not turned off |
| `messageFor(object $notifiable, ?string $locale = null): NotificationMessage` | The message in a locale, with the type's icon and colour |
| `subjectFor(object $notifiable): ?Model` | The subject |
| `toMail()`, `toDatabase()`, `toArray()`, `toBroadcast()`, `toFcm()`, `toSms()` | Each channel's format |
| `broadcastType(): string` | `notification.created` |

## `Javaabu\Notifications\Messages\NotificationMessage`

`make(string $title = '')`, `title()`, `body()`, `line()`, `lines()`, `action()`, `url()`, `icon()`, `color()`, `thumbnail()`, `subject()`, `level()`, `success()`, `error()`, `greeting()`, `salutation()`, `data()`, `sms()`, `mail()`, `fcm()`. Readers: `bodyText()`, `resolvedUrl(?object $notifiable)`, `resolvedThumbnail(?object $notifiable)`, `texts()`, `toArray()`. Public properties of the same names (`$title`, `$lines`, `$action_text`, `$action_url`, `$icon`, `$color`, `$thumbnail`, `$subject`, `$level`, `$greeting`, `$salutation`, `$data`, `$sms`, `$mail_callback`, `$fcm_callback`). See [Writing notifications](../basic-usage/writing-notifications.md#the-message).

## Traits and contracts

| | |
| --- | --- |
| `Concerns\Notifiable` | Laravel's `Notifiable` with the package's model; `receivesBroadcastNotificationsOn()` |
| `Concerns\HasDatabaseNotifications` | `notifications()`, `readNotifications()`, `unreadNotifications()` |
| `Concerns\ManagesNotificationPreferences` | `wantsNotificationVia()`, `notificationPreference()`, `notificationPreferences()`, `setNotificationPreference()`, `notificationPreferencesAttribute()` |
| `Contracts\HasNotificationPreferences` | `wantsNotificationVia(string $channel, Notification $notification): bool` |
| `Contracts\NotificationSubject` | `notificationUrl(?object $notifiable): ?string`, `notificationThumbnail(?object $notifiable): ?string` |

## `Javaabu\Notifications\Models\DatabaseNotification`

Extends Laravel's. `subject()` (morph to), scopes `forNotifiable(Model)`, `about(Model)`, `ofType(string)`; `isOwnedBy(?Model)`, `text()`, `title()`, `body()`, `actionText()`, `actionUrl()`, `icon()`, `color()`, `tone()`, `thumbnail()`, `extraData()`, `typeLabel()`, `static labelForType(string)`. See [Database notifications](../basic-usage/database-notifications.md).

## `Javaabu\Notifications\Notifications`

| Member | |
| --- | --- |
| `static bool $runs_migrations`, `static ignoreMigrations()` | Stop loading the migrations |
| `static notificationModel(): string`, `static table(): string` | |
| `static string $fcm_channel`, `static fcmInstalled(): bool` | |
| `static resolveChannel(string): string` | `sms` and `fcm` to their channel classes |
| `static smsChannel(): string` | The SMS driver's channel |
| `static localeFor(mixed $notifiable): ?string` | The notifiable's locale |

## Support

| | |
| --- | --- |
| `Support\BellData::make(?Model $notifiable, string $routes = 'notifications', ?int $limit = null): ?array` | The React bell's data |
| `Support\Broadcasting` | `enabled()`, `pollInterval()`, `channelPrefix()`, `channelFor(Model)`, `registerChannels()`, `encodeType(string)`, `static $reverb_class` |
| `Support\Translations` | `enabled()`, `locales()`, `isActive(string)`, `defaultLocale()`, `static $translatable_class` |
| `Mail\BrandedMailMessage` | A `MailMessage` with the branded layout and theme; `locale(string)`, `static fromMessage(NotificationMessage, ?string $url)` |
| `Channels\DatabaseChannel` | Laravel's database channel, saving the subject columns; bound in place of Laravel's |
| `Routing\NotificationRoutes` | `register(Router, array)`, `guard(Request)`, `namePrefix(Request)`, `names(string $prefix)` |
| `Http\Controllers\NotificationsController` | `index`, `latest`, `show`, `read`, `unread`, `readAll`, `destroy` |
| `Http\Resources\NotificationResource` | The JSON; `routePrefix(?string)` |
| `Policies\NotificationPolicy` | See [Permissions and policies](permissions-and-policies.md) |
| `View\Components\Bell` | `<x-notifications::bell>` |
| `Views\ViewRenderer` | Renders a page with `blade` or `inertia` |

# JavaScript (`@javaabu/notifications`)

| Export | |
| --- | --- |
| `NotificationBell` | `{ bell: BellData, viewAllHref?, ...NotificationCenterProps }` |
| `toNotificationItem(notification)` | To Onu's `NotificationItem` |
| `NotificationListItem` | `{ notification, strings, actions? = true }` |
| `useNotifications(options)` | `{ notifications, unreadCount, loading, refresh, markRead, markAllRead }`; options `latestUrl`, `readAllUrl`, `initial?`, `initialUnreadCount?`, `broadcasting?`, `channel?`, `pollInterval?` |
| `subscribe({ broadcasting, channel, pollInterval, onChange })` | Echo or polling; returns a stop function |
| `csrfHeaders()`, `requestJson(url, method?)` | Requests with Laravel's CSRF token |
| Types | `NotificationData`, `BellData`, `LatestResponse`, `NotificationsMeta`, `NotificationRoutes`, `NotificationsIndexProps`, `NotificationsStrings`, `Paginated`, `Option`, `Tone`, `EchoLike`, `EchoChannel` |

The page is `notifications::Notifications/Index`. The Blade script (`notifications::notifications`) exports `init()`, `initBell()`, `refresh()`, `updateBell()`, `updateCount()` and `follow()`.
