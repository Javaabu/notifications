<?php

namespace Javaabu\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use Javaabu\Notifications\Contracts\HasNotificationPreferences;
use Javaabu\Notifications\Mail\BrandedMailMessage;
use Javaabu\Notifications\Messages\NotificationMessage;
use Javaabu\Notifications\Support\Broadcasting;
use Javaabu\Notifications\Support\Translations;
use Javaabu\SmsNotifications\Notifications\SendsSms;
use Javaabu\SmsNotifications\Notifications\SmsNotification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use RuntimeException;

/**
 * A notification defined once, in message(), and sent through any channel: mail, database,
 * broadcast, sms (javaabu/sms-notifications) and fcm (laravel-notification-channels/fcm)
 *
 * ```php
 * class OrderShippedNotification extends BaseNotification
 * {
 *     protected array $channels = ['database', 'mail', 'sms'];
 *
 *     public function __construct(public Order $order) {}
 *
 *     public function message(object $notifiable): NotificationMessage
 *     {
 *         return NotificationMessage::make(__('Your order has shipped'))
 *             ->line(__('Order :number is on its way.', ['number' => $this->order->number]))
 *             ->action(__('Track order'), route('orders.show', $this->order))
 *             ->subject($this->order);
 *     }
 * }
 * ```
 */
abstract class BaseNotification extends Notification implements SmsNotification
{
    use Localizable;
    use Queueable;
    use SendsSms;

    /** The channels to send through; empty means `notifications.channels` */
    protected array $channels = [];

    /** Channels the notifiable can't opt out of; null means `notifications.required_channels` */
    protected ?array $required_channels = null;

    /** The icon when the message doesn't set one */
    protected ?string $icon = null;

    /** The icon colour when the message doesn't set one */
    protected ?string $color = null;

    /**
     * What the notification says, in the notifiable's locale (it's called inside it)
     */
    abstract public function message(object $notifiable): NotificationMessage;

    /**
     * A name for this type of notification, for the notifications page and preferences
     */
    public static function label(): string
    {
        return __(Str::of(class_basename(static::class))->beforeLast('Notification')->snake(' ')->ucfirst()->toString());
    }

    /**
     * The key preferences are saved under: `OrderShippedNotification` → `order-shipped`
     */
    public static function preferenceKey(): string
    {
        return static::preferenceKeyFor(static::class);
    }

    public static function preferenceKeyFor(string $class): string
    {
        return Str::of(class_basename($class))->beforeLast('Notification')->kebab()->toString();
    }

    /**
     * The channel names (before aliases are resolved) this notification is sent through
     *
     * @return array<int, string>
     */
    public function channels(): array
    {
        return $this->channels ?: config('notifications.channels', ['database', 'mail']);
    }

    /**
     * @return array<int, string>
     */
    public function requiredChannels(): array
    {
        return $this->required_channels ?? config('notifications.required_channels', ['database']);
    }

    /**
     * The channels to send through: the notification's channels the notifiable can receive and
     * hasn't turned off, plus broadcast when live updates are on
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        foreach ($this->channels() as $channel) {
            if (! $this->canReceive($notifiable, $channel)) {
                continue;
            }

            if (! $this->wants($notifiable, $channel)) {
                continue;
            }

            $channels[] = $channel;
        }

        if (in_array('database', $channels, true) && Broadcasting::enabled()) {
            $channels[] = 'broadcast';
        }

        return array_values(array_unique(array_map(
            fn (string $channel) => Notifications::resolveChannel($channel),
            $channels,
        )));
    }

    /**
     * Whether the notifiable has a route for the channel (an email address, a phone number, …)
     */
    public function canReceive(object $notifiable, string $channel): bool
    {
        return match ($channel) {
            'database' => ! $notifiable instanceof AnonymousNotifiable && method_exists($notifiable, 'notifications'),
            'broadcast' => true,
            'fcm' => Notifications::fcmInstalled() && filled($this->route($notifiable, 'fcm')),
            default => filled($this->route($notifiable, $channel)),
        };
    }

    /**
     * Whether the notifiable wants this channel: required channels always, others unless it
     * turned them off (HasNotificationPreferences)
     */
    public function wants(object $notifiable, string $channel): bool
    {
        if (in_array($channel, $this->requiredChannels(), true)) {
            return true;
        }

        if (! $notifiable instanceof HasNotificationPreferences) {
            return true;
        }

        return $notifiable->wantsNotificationVia($channel, $this);
    }

    /**
     * The message in the notifiable's locale, with the type's icon and colour as defaults
     */
    public function messageFor(object $notifiable, ?string $locale = null): NotificationMessage
    {
        $locale ??= Notifications::localeFor($notifiable);

        $message = $this->withLocale($locale, fn () => $this->message($notifiable));

        $message->icon ??= $this->icon;
        $message->color ??= $this->color;

        return $message;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->messageFor($notifiable);

        return $this->withLocale(Notifications::localeFor($notifiable), function () use ($message, $notifiable) {
            $mail = BrandedMailMessage::fromMessage($message, $message->resolvedUrl($notifiable));

            if ($message->mail_callback) {
                $mail = ($message->mail_callback)($mail, $notifiable) ?? $mail;
            }

            return $mail;
        });
    }

    /**
     * What's stored in the database: the texts, presentation and data, and the texts in every
     * active language when javaabu/translatable is on
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $message = $this->messageFor($notifiable);

        $payload = $this->payload($message, $notifiable);

        $translations = $this->translations($notifiable);

        if ($translations) {
            $payload['translations'] = $translations;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload($this->messageFor($notifiable), $notifiable);
    }

    /**
     * Tells the bell there's something new; it fetches the latest notifications itself
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $message = $this->messageFor($notifiable);

        return new BroadcastMessage([
            'title' => $message->title,
            'body' => $message->bodyText(),
            'icon' => $message->icon,
            'color' => $message->color,
        ]);
    }

    public function broadcastType(): string
    {
        return 'notification.created';
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        if (! Notifications::fcmInstalled()) {
            throw new RuntimeException('Install laravel-notification-channels/fcm to send notifications through the fcm channel.');
        }

        $message = $this->messageFor($notifiable);

        $data = [
            'id' => (string) $this->id,
            'type' => static::class,
            'url' => (string) $message->resolvedUrl($notifiable),
        ];

        foreach ($message->data as $key => $value) {
            $data[$key] = is_scalar($value) || $value === null
                ? (string) $value
                : (string) json_encode($value);
        }

        $fcm = FcmMessage::create(
            data: $data,
            notification: new FcmNotification(
                title: $message->title,
                body: $message->bodyText() ?: null,
                image: $message->resolvedThumbnail($notifiable),
            ),
        );

        if ($message->fcm_callback) {
            $fcm = ($message->fcm_callback)($fcm, $notifiable) ?? $fcm;
        }

        return $fcm;
    }

    /**
     * The SMS text: the message's sms(), or its title, body and URL
     */
    public function toSms($notifiable): string
    {
        $message = $this->messageFor($notifiable);

        if ($message->sms !== null) {
            return $message->sms;
        }

        return collect([$message->title, $message->bodyText(), $message->resolvedUrl($notifiable)])
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(NotificationMessage $message, object $notifiable): array
    {
        return [
            ...$message->toArray(),
            'action_url' => $message->resolvedUrl($notifiable),
            'thumbnail' => $message->resolvedThumbnail($notifiable),
        ];
    }

    /**
     * The title, body and action text in each active language, so readers see their own
     *
     * @return array<string, array{title: string, body: string, action_text: ?string}>
     */
    protected function translations(object $notifiable): array
    {
        $locales = Translations::locales();

        if (count($locales) < 2) {
            return [];
        }

        $translations = [];

        foreach ($locales as $locale) {
            $translations[$locale] = $this->messageFor($notifiable, $locale)->texts();
        }

        return $translations;
    }

    protected function route(object $notifiable, string $channel): mixed
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        return $notifiable->routeNotificationFor($channel, $this);
    }

    /**
     * The subject model, for the database channel's subject columns
     */
    public function subjectFor(object $notifiable): ?Model
    {
        return $this->messageFor($notifiable)->subject;
    }
}
