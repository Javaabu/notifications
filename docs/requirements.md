---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- [javaabu/permissions](https://github.com/Javaabu/permissions) v3, [javaabu/forms](https://github.com/Javaabu/forms) v2, [javaabu/manifest](https://github.com/Javaabu/manifest) v1 and [javaabu/sms-notifications](https://github.com/Javaabu/sms-notifications) v1, installed with this package
- For the notifications page and the bell: Vite with manifest's plugin, and Tailwind CSS with Onu's theme (the views use its colour tokens). Blade apps need no other npm packages. The Inertia React page and components also need `@javaabu/forms`, `@javaabu/onu`, React 18.3 or 19 and `@inertiajs/react` 2 or 3 in the app; `php artisan manifest:check` lists them only when the app's `package.json` has `@inertiajs/react`.

Optional:

| Package | For |
| --- | --- |
| `laravel/reverb` ^1.12 | New notifications reach the bell straight away. Without it, the bell polls. See [Live updates](advanced-usage/live-updates.md). |
| `laravel-notification-channels/fcm` ^6.0 | Push notifications through the `fcm` channel. See [Channels and preferences](basic-usage/channels-and-preferences.md). |
| `javaabu/translatable` ^3.0 | Database notifications in every active language. See [Translations](advanced-usage/translations.md). |
| `inertiajs/inertia-laravel` ^2.0 | The Inertia React page (`notifications.views` = `inertia`). |
| An SMS driver | `dash8x/dhiraagu-sms-notification` or `laravel-notification-channels/twilio`, for the `sms` channel (see javaabu/sms-notifications). |
