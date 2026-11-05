{{--
    javaabu/notifications guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

@verbatim
## javaabu/notifications

- Notifications extend `Javaabu\Notifications\BaseNotification` and define `message(object $notifiable): NotificationMessage` once; the package builds mail, database, broadcast, FCM and SMS from it. List channels in `protected array $channels`; don't hand-write `toMail()`/`toArray()`/`toSms()` or filter channels in `via()`.
- Notifiable models use `Javaabu\Notifications\Concerns\Notifiable` instead of Laravel's `Notifiable`. Messages run in the notifiable's locale, so wrap every string in `__()` and add Dhivehi to `lang/dv.json`.
- Pass the model a notification is about with `->subject($model)`; link to notifications through the `notifications.show` route, which marks them read.
- Register routes with `Route::notifications()` (per guard), render the bell with `<x-notifications::bell />` in Blade or `<NotificationBell bell={...} />` from `@javaabu/notifications` with `BellData::make()` in Inertia.
- Always activate the `javaabu-notifications` skill when writing or sending a notification, choosing its channels or preferences, adding SMS or push notifications, showing the bell or notifications page, branding mail, or testing notifications.
@endverbatim
