# Changelog

All notable changes to `notifications` will be documented in this file.

## 1.0.0 - unreleased

- `BaseNotification`: one `message()` for mail, database, broadcast, FCM and SMS, with channels chosen by route and by the notifiable's preferences
- Database notifications with a subject morph, icons, colours, thumbnails and click redirects that mark them read
- `Route::notifications()`, an overridable controller, `NotificationPolicy`, and the `view_others_notifications` / `delete_others_notifications` permissions
- The notifications page and the bell in Blade and Inertia React, live over Reverb or by polling
- Branded mail from one config, right to left for Dhivehi
- Notifications in the notifiable's language; with javaabu/translatable, database notifications in every active language
- English and Dhivehi strings
- The React page and components' npm packages are declared for pages only, so `manifest:check` doesn't ask Blade apps for React
