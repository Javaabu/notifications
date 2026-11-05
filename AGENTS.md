# notifications: notes for AI coding agents

`javaabu/notifications` is a Laravel package: `BaseNotification` (one `message()` for mail, database, broadcast, FCM and SMS), channel preferences, a database notification model with a subject morph, routes and an overridable controller, a policy and permissions (javaabu/permissions), branded mail, and the notifications page and bell in Blade (javaabu/forms v2) and Inertia React (`resources/js`, imported by apps as `@javaabu/notifications` through javaabu/manifest).

## Layout

| Path | What |
| --- | --- |
| `src/BaseNotification.php`, `src/Messages/NotificationMessage.php` | The notification and its message; channel formats, `via()`, locales |
| `src/Concerns`, `src/Contracts` | `Notifiable`, `HasDatabaseNotifications`, `ManagesNotificationPreferences`; `HasNotificationPreferences`, `NotificationSubject` |
| `src/Models/DatabaseNotification.php`, `src/Channels/DatabaseChannel.php` | The model (texts per locale, presentation) and the channel that saves the subject columns |
| `src/Http`, `src/Routing`, `src/Policies`, `src/Views` | Controller, resource, `Route::notifications()`, policy, the Blade/Inertia renderer |
| `src/Support` | `Broadcasting` (Reverb detection, channels), `Translations` (javaabu/translatable, optional: never reference its classes elsewhere), `BellData` |
| `src/View/Components/Bell.php`, `resources/views` | `<x-notifications::bell>`, the page, the item partial, the mail views and theme |
| `resources/js` | React page, components, hook, `support/`, and `blade/notifications.ts` (the Blade script, built by the app's Vite through manifest) |
| `lang/en.json`, `lang/dv.json` | Every user-facing string |
| `tests/` | PHPUnit (`Unit`, `Feature`, `Http`, `TestSupport`) and Vitest (`tests/js`) |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Laravel Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…` or describing behaviour, early returns, no `else`.
- A change to `NotificationResource` changes `resources/js/types.ts`, the docs (`notifications-page.md`, `api-reference.md`) and the components together.
- The Blade item partial is shared by the page, the bell and `latest?html=1`; keep the `data-notifications-*` attributes the script uses.
- javaabu/translatable, laravel-notification-channels/fcm and laravel/reverb are optional: CI runs the suite without them, and their tests skip themselves when they're missing.
- Every user-facing string goes in `lang/en.json` and `lang/dv.json` (`TranslationsTest` checks keys and placeholders). Views use logical directions only (`RightToLeftTest`), except the mail theme.
- Every change needs tests, docs (linked from `llms.txt`), the Boost skill if it changes usage, and a line in `CHANGELOG.md`.

## Commands

```bash
composer install && pnpm install      # pnpm 10.28 (packageManager)
composer lint && composer test        # Pint and PHPUnit
pnpm typecheck && pnpm test           # React and the Blade script
```
