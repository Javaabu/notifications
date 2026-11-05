---
title: The bell
sidebar_position: 5
---

The bell is a button with the unread count that opens the latest notifications, with "Mark all as read" and "View all notifications". Opening a notification marks it read and goes to its URL. It stays up to date over Reverb, or by polling (see [Live updates](../advanced-usage/live-updates.md)), and announces new notifications to screen readers.

# Blade

```blade
<x-notifications::bell />
```

| Attribute | Default | |
| --- | --- | --- |
| `guard` | `null` (the default guard) | Whose notifications it shows |
| `routes` | `notifications` | The route name prefix you registered the routes under |
| `limit` | `notifications.latest_limit` (10) | How many it shows |
| `notifiable` | the guard's user | Show another notifiable's bell |

Other attributes go on the wrapper, for example `class`. It renders nothing for guests.

```blade
{{-- An admin portal on the web_admin guard, with Route::notifications(['guard' => 'web_admin', 'prefix' => 'admin/notifications', 'name' => 'admin.notifications']) --}}
<x-notifications::bell guard="web_admin" routes="admin.notifications" :limit="5" class="ms-auto" />
```

The bell is server-rendered with the latest notifications, and loads the `notifications::notifications` script through javaabu/manifest. The script opens and closes the panel (Escape or a click outside closes it), marks all read, and refreshes it when it opens and when something new arrives, with the items rendered by the server (`notifications/latest?html=1`) so they look the same as on first load.

The panel is placed at the end of the button (`end-0`), so it opens to the left in English and to the right in Dhivehi.

# React

```tsx
import { NotificationBell } from "@javaabu/notifications";

export function Header({ notifications }: { notifications: BellData }) {
  return (
    <header className="flex items-center gap-2">
      <NotificationBell bell={notifications} />
    </header>
  );
}
```

`bell` is what `BellData::make()` returns, shared with every page:

```php
// HandleInertiaRequests::share()
'notifications' => fn () => BellData::make($request->user()),

// Another guard and route prefix, and 5 notifications
'admin_notifications' => fn () => BellData::make($request->user('web_admin'), 'admin.notifications', 5),
```

```ts
interface BellData {
  unread_count: number;
  notifications: NotificationData[];
  broadcasting: boolean;
  channel: string | null;
  poll_interval: number;
  urls: { index: string; latest: string; read_all: string };
}
```

`BellData::make(null)` is `null`, so guests get no bell: `{props.notifications ? <NotificationBell bell={props.notifications} /> : null}`.

`NotificationBell` is Onu's `NotificationCenter`: a popover on desktop and a bottom sheet on phones, translated by Onu (set `OnuProvider`'s locale to `dv` for Dhivehi). It takes every `NotificationCenter` prop except the ones it fills in (`notifications`, `onMarkRead`, `onMarkAllRead`), plus `viewAllHref` to change where "View all" goes. Its badge counts the unread notifications among the ones it loaded (10 by default).

## Your own bell

`useNotifications()` has the data and the actions, for a bell of your own design:

```tsx
import { useNotifications } from "@javaabu/notifications";

const { notifications, unreadCount, loading, refresh, markRead, markAllRead } = useNotifications({
  latestUrl: bell.urls.latest,
  readAllUrl: bell.urls.read_all,
  initial: bell.notifications,
  initialUnreadCount: bell.unread_count,
  broadcasting: bell.broadcasting,
  channel: bell.channel,
  pollInterval: bell.poll_interval,
});
```

`markRead()` and `markAllRead()` update the list straight away and tell the server. Requests send Laravel's CSRF token from the `csrf-token` meta tag, or the `XSRF-TOKEN` cookie.

`toNotificationItem(notification)` turns a notification into Onu's `NotificationItem`, for any Onu component that lists them.
