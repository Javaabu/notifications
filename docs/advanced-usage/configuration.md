---
title: Configuration
sidebar_position: 10
---

Publish the config to change it:

```bash
php artisan vendor:publish --tag=notifications-config
```

| Key | Default | |
| --- | --- | --- |
| `channels` | `['database', 'mail']` | Channels for notifications without `$channels` |
| `required_channels` | `['database']` | Channels notifiables can't turn off |
| `preferences.attribute` | `notification_preferences` | The JSON column `ManagesNotificationPreferences` uses |
| `locale_attribute` | `locale` | The notifiable attribute with its language, when it doesn't implement `HasLocalePreference`. `null` turns it off. |
| `translations` | `true` | Store database notifications in every active language when javaabu/translatable is on |
| `defaults.icon` | `bell` | The icon of notifications without one |
| `defaults.color` | `primary` | The colour of notifications without one |
| `mail.branded` | `true` | Use the branded layout and theme for notification mail |
| `mail.theme` | `notifications::mail.theme` | The mail theme (a view) |
| `mail.branding.name` | `APP_NAME` | The header text and logo alt text |
| `mail.branding.url` | `APP_URL` | Where the header links to |
| `mail.branding.logo` | `null` | An absolute logo URL |
| `mail.branding.logo_height` | `48` | Pixels |
| `mail.branding.colors` | teal and greys | `primary`, `primary_text`, `background`, `body`, `text`, `muted`, `success`, `error` |
| `mail.branding.footer` | `null` | Markdown; `null` shows the copyright line |
| `mail.rtl_locales` | `['dv', 'ar', 'fa', 'he', 'ur']` | Locales whose mail is right to left |
| `table` | `notifications` | The notifications table |
| `notifiable_key_type` | `integer` | `integer`, `uuid`, `ulid` or `string`, when the package creates the table |
| `subject_columns` | `true` | Save the subject in `subject_type` and `subject_id` |
| `models.notification` | `DatabaseNotification` | The notification model |
| `latest_limit` | `10` | How many notifications the bell shows |
| `per_page` | `20` | Notifications per page |
| `broadcasting.enabled` | `auto` (`NOTIFICATIONS_BROADCASTING`) | `auto`, `true` or `false`. See [Live updates](live-updates.md). |
| `broadcasting.channel` | `notifications` | The private channel prefix |
| `broadcasting.guards` | `null` | Guards that authorize the channel |
| `broadcasting.poll_interval` | `30000` | Milliseconds between polls |
| `views` | `blade` (`NOTIFICATIONS_VIEWS`) | `blade` or `inertia` |
| `layout` | `layouts.app` | The layout the Blade page extends |
| `layout_section` | `content` | The section it fills |
| `controllers.notifications` | `NotificationsController` | The controller |
| `policy` | `NotificationPolicy` | The policy |
| `register_permissions` | `true` | Register `view_others_notifications` and `delete_others_notifications` |
