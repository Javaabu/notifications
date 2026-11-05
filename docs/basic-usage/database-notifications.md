---
title: Database notifications
sidebar_position: 3
---

Database notifications are Laravel's, stored in the `notifications` table, read through the package's model `Javaabu\Notifications\Models\DatabaseNotification` (it extends Laravel's). The `Notifiable` trait's relations use it:

```php
$user->notifications;            // newest first
$user->unreadNotifications()->count();
$user->readNotifications()->delete();

$notification->markAsRead();
$notification->markAsUnread();
```

Replace the model with a subclass in `notifications.models.notification`.

# Reading a notification

The model reads the texts in the current language (see [Translations](../advanced-usage/translations.md)) and falls back to the text it was sent with:

| Method | Returns |
| --- | --- |
| `title(?string $locale = null)` | The title |
| `body(?string $locale = null)` | The body, paragraphs separated by blank lines, or null |
| `actionText(?string $locale = null)` | The action button's text, or null |
| `text(string $key, ?string $locale = null)` | Any of `title`, `body`, `action_text` |
| `actionUrl()` | Where opening it goes, or null |
| `icon()` | The icon name (`notifications.defaults.icon` when it has none) |
| `color()` | The colour (`notifications.defaults.color` when it has none) |
| `tone()` | The colour as one of Onu's tones: `neutral`, `info`, `success`, `warning`, `danger` |
| `thumbnail()` | The image URL, or null |
| `extraData()` | The message's `data()` |
| `typeLabel()` | The type's `label()` |
| `subject` | The model it's about |
| `isOwnedBy(?Model $user)` | Whether it belongs to that user (type and key both match) |
| `read()`, `unread()` | Laravel's |

# The model a notification is about

`subject($model)` on the message saves the model in the `subject_type` and `subject_id` columns:

```php
NotificationMessage::make(__('New comment'))->subject($post);
```

```php
$notification->subject;                                  // the Post
DatabaseNotification::query()->about($post)->get();       // every notification about it
DatabaseNotification::query()->about($post)->delete();    // e.g. when the post is deleted
DatabaseNotification::query()->forNotifiable($user)->ofType(NewCommentNotification::class)->count();
```

Turn the columns off with `notifications.subject_columns` if your table doesn't have them.

The subject also fills in what the message leaves out. Implement `Javaabu\Notifications\Contracts\NotificationSubject` (or just the methods):

```php
use Javaabu\Notifications\Contracts\NotificationSubject;

class Post extends Model implements NotificationSubject
{
    public function notificationUrl(?object $notifiable = null): ?string
    {
        return route('posts.show', $this);
    }

    public function notificationThumbnail(?object $notifiable = null): ?string
    {
        return $this->getFirstMediaUrl('cover', 'thumb') ?: null;
    }
}
```

A message about a post with no `url()`/`action()` links to `notificationUrl()`, and one with no `thumbnail()` shows `notificationThumbnail()`. Both are resolved when the notification is sent.

# Icons, colours and thumbnails

Each notification shows an icon in a coloured circle, or its thumbnail instead.

- **Icon names** are Onu's semantic names: `bell`, `mail`, `send`, `user`, `users`, `calendar`, `success`, `warning`, `error`, `info`, `file`, `download`, `heart`, `star`, `message`, `megaphone`, `settings`, … In React they render with Onu's `Icon`, so any name you add with `OnuProvider icons={…}` works. In Blade, `bell` is drawn by the package and other names go through `<x-forms::icon>`, which knows javaabu/forms' icons (`check`, `circle-check`, `circle-alert`, `calendar`, `clock`, `download`, `file`, `file-text`, `image`, `info`, `map-pin`, …) and otherwise uses the class from `forms.icons.{name}` (for an icon font). Override `notifications::partials.icon` to draw your own.
- **Colours** are `primary`, `secondary`, `success`, `info`, `warning`, `danger` and `neutral`. The views map them to Onu's tones (`tone()`): `success`, `warning` and `danger` keep their colour, `secondary` and `neutral` are grey, and `primary` and `info` are blue.
- **Thumbnails** are image URLs, such as an avatar or a product photo.

Set them on the message, on the class (`$icon`, `$color`), or for every notification in `notifications.defaults`.

# Opening a notification

The notification's link (`links.show` in JSON, `route('notifications.show', $notification->id)` in Blade) goes through the package: it marks the notification read and redirects to its URL, or to the notifications page when it has none. Users who open someone else's notification (with `view_others_notifications`) don't mark it read.
