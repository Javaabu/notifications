---
title: The notifications page
sidebar_position: 4
---

# Routes

`Route::notifications()` registers the routes. Put it inside your own route group, so you choose the middleware, prefix and names:

```php
Route::middleware('web')->group(function () {
    Route::notifications();
});
```

| Method | URL | Name | Does |
| --- | --- | --- | --- |
| GET | `/notifications` | `notifications.index` | The page, or JSON |
| GET | `/notifications/latest` | `notifications.latest` | The bell's JSON: the latest notifications and the unread count |
| POST | `/notifications/read-all` | `notifications.read-all` | Marks all the user's notifications read |
| GET | `/notifications/{id}` | `notifications.show` | Marks it read and redirects to its URL (JSON: the notification) |
| POST | `/notifications/{id}/read` | `notifications.read` | Marks it read |
| POST | `/notifications/{id}/unread` | `notifications.unread` | Marks it unread |
| DELETE | `/notifications/{id}` | `notifications.destroy` | Deletes it |

Options:

| Option | Default | |
| --- | --- | --- |
| `guard` | `null` (the default guard) | The guard of the users these routes are for |
| `prefix` | `notifications` | The URL prefix |
| `name` | `notifications` | The route name prefix |
| `middleware` | `['auth']`, or `['auth:{guard}']` | Replaces the middleware |
| `controller` | `notifications.controllers.notifications` | A controller class, see [Overriding](../advanced-usage/overriding-controllers-and-views.md) |

```php
Route::notifications([
    'guard' => 'web_admin',
    'prefix' => 'admin/notifications',
    'name' => 'admin.notifications',
    'middleware' => ['auth:web_admin', 'verified'],
]);
```

Every action answers JSON when the request expects it (`Accept: application/json`), and redirects back with a flash message (`success`) otherwise.

# The page

The page lists the user's notifications, newest first, 20 to a page (`notifications.per_page`, or `?per_page=` up to 100). Users can:

- filter by status (`?status=unread` or `read`) and, when they have more than one, by type (`?type=App\Notifications\OrderShippedNotification`);
- open a notification (it's marked read);
- mark one read or unread, or all read;
- delete one, after confirming.

Unread notifications are bold on a tinted background, with "Unread" for screen readers. The page follows new notifications live (see [Live updates](../advanced-usage/live-updates.md)).

## Blade

`notifications::notifications.index` extends `config('notifications.layout')` (`layouts.app`) in the section `config('notifications.layout_section')` (`content`). It's built with javaabu/forms v2 components (`<x-forms::form>`, `<x-forms::select>`, `<x-forms::submit>`, `<x-forms::no-items>`, `<x-forms::pagination>`), and each notification is `notifications::notifications.partials.item`, shared with the bell.

The page loads the `notifications::notifications` script through javaabu/manifest, which asks before deleting.

## Inertia React

With `notifications.views` set to `inertia`, the page is `notifications::Notifications/Index` (resolved by manifest's `resolvePage()`), built with `@javaabu/forms` and Onu. Its props:

| Prop | |
| --- | --- |
| `title` | "Notifications", translated |
| `notifications` | A paginated `NotificationResource` collection (`data`, `links`, `meta`) |
| `is_own` | Whether these are the user's own notifications |
| `filters` | `{ status, type }` |
| `statuses`, `types` | Options for the filters, `{ value, label }` |
| `broadcasting`, `channel`, `poll_interval` | How the page stays up to date |
| `routes`, `urls` | Route names, and the `index`, `latest` and `read_all` URLs |
| `strings` | The page's text, translated |

Share flash messages with Inertia as `flash.success` for the page to show them.

Use the parts in your own pages:

```tsx
import { NotificationListItem, type NotificationData } from "@javaabu/notifications";

<ul className="divide-y rounded-lg border">
  {notifications.map((notification) => (
    <NotificationListItem key={notification.id} notification={notification} strings={strings} />
  ))}
</ul>
```

# The JSON

`GET /notifications` (and the other routes) return `NotificationResource`s:

```json
{
  "id": "9b84…",
  "type": "App\\Notifications\\OrderShippedNotification",
  "type_label": "Order shipped",
  "title": "Your order has shipped",
  "body": "Order ORD-1 is on its way.",
  "action_text": "Track order",
  "action_url": "https://example.com/orders/1",
  "icon": "send",
  "color": "success",
  "tone": "success",
  "thumbnail": null,
  "extra": { "order_id": 1 },
  "subject_type": "order",
  "subject_id": "1",
  "read": false,
  "read_at": null,
  "created_at": "2026-11-05T10:00:00+00:00",
  "created_at_human": "1 hour ago",
  "links": { "show": "…/notifications/9b84…", "read": "…/read", "unread": "…/unread", "destroy": "…/notifications/9b84…" },
  "can": { "update": true, "delete": true }
}
```

The message's `data()` is under `extra`. Lists have `meta` with `broadcasting`, `channel`, `poll_interval`, `routes` and `urls`; `latest`, `read` and `unread` add `unread_count`.

# Other users' notifications

Users with `view_others_notifications` can list someone else's notifications by passing the notifiable:

```
GET /notifications?notifiable_type=user&notifiable_id=5
```

`notifiable_type` is the morph type (the morph map alias, or the class). They can open those notifications without marking them read, and delete them with `delete_others_notifications`. Only the notifiable marks its own notifications read or unread.
