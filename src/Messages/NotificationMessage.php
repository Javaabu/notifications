<?php

namespace Javaabu\Notifications\Messages;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

/**
 * What a notification says, defined once and turned into every channel's format
 *
 * ```php
 * return NotificationMessage::make(__('Order :number shipped', ['number' => $this->order->number]))
 *     ->line(__('It will reach you in 2 days.'))
 *     ->action(__('Track order'), route('orders.show', $this->order))
 *     ->icon('truck')
 *     ->color('success')
 *     ->subject($this->order);
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class NotificationMessage implements Arrayable
{
    public const string LEVEL_INFO = 'info';

    public const string LEVEL_SUCCESS = 'success';

    public const string LEVEL_ERROR = 'error';

    /** @var array<int, string> */
    public array $lines = [];

    public ?string $action_text = null;

    public ?string $action_url = null;

    public ?string $icon = null;

    public ?string $color = null;

    public ?string $thumbnail = null;

    public ?Model $subject = null;

    public string $level = self::LEVEL_INFO;

    public ?string $greeting = null;

    public ?string $salutation = null;

    /** @var array<string, mixed> */
    public array $data = [];

    public ?string $sms = null;

    public ?Closure $mail_callback = null;

    public ?Closure $fcm_callback = null;

    public function __construct(
        public string $title = '',
    ) {}

    public static function make(string $title = ''): static
    {
        return new static($title);
    }

    /**
     * The headline: the mail subject, the bell's title, the push notification's title
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Replace the body with this text (one paragraph)
     */
    public function body(?string $body): static
    {
        $this->lines = $body === null || $body === '' ? [] : [$body];

        return $this;
    }

    /**
     * Add a paragraph to the body
     */
    public function line(?string $line): static
    {
        if ($line === null || $line === '') {
            return $this;
        }

        $this->lines[] = $line;

        return $this;
    }

    /**
     * @param  array<int, string|null>  $lines
     */
    public function lines(array $lines): static
    {
        foreach ($lines as $line) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * The button in mail, and where clicking the notification goes
     */
    public function action(string $text, string $url): static
    {
        $this->action_text = $text;
        $this->action_url = $url;

        return $this;
    }

    /**
     * Where clicking the notification goes, without a mail button label
     */
    public function url(?string $url): static
    {
        $this->action_url = $url;

        return $this;
    }

    /**
     * An icon name for the bell and the notifications page, e.g. `truck` or `circle-check`
     */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * A colour for the icon: primary, secondary, success, info, warning, danger or neutral
     */
    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * An image URL shown in place of the icon, and in push notifications
     */
    public function thumbnail(?string $url): static
    {
        $this->thumbnail = $url;

        return $this;
    }

    /**
     * The model the notification is about. Saved on database notifications as a morph,
     * and used for the click redirect and thumbnail when they aren't set
     */
    public function subject(?Model $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * info (default), success or error: the mail button colour
     */
    public function level(string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function success(): static
    {
        return $this->level(self::LEVEL_SUCCESS)->color($this->color ?? 'success');
    }

    public function error(): static
    {
        return $this->level(self::LEVEL_ERROR)->color($this->color ?? 'danger');
    }

    public function greeting(?string $greeting): static
    {
        $this->greeting = $greeting;

        return $this;
    }

    public function salutation(?string $salutation): static
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Extra data kept with database notifications and sent with push notifications
     *
     * @param  array<string, mixed>  $data
     */
    public function data(array $data): static
    {
        $this->data = [...$this->data, ...$data];

        return $this;
    }

    /**
     * The SMS text, instead of the title, body and URL
     */
    public function sms(?string $text): static
    {
        $this->sms = $text;

        return $this;
    }

    /**
     * Change the mail message before it's sent: `fn (MailMessage $mail) => $mail->cc(…)`
     */
    public function mail(?Closure $callback): static
    {
        $this->mail_callback = $callback;

        return $this;
    }

    /**
     * Change the FCM message before it's sent: `fn (FcmMessage $message) => $message->custom(…)`
     */
    public function fcm(?Closure $callback): static
    {
        $this->fcm_callback = $callback;

        return $this;
    }

    /**
     * The body as one string, paragraphs separated by blank lines
     */
    public function bodyText(): string
    {
        return implode("\n\n", $this->lines);
    }

    /**
     * The URL the notification links to: the action, or the subject's notificationUrl()
     */
    public function resolvedUrl(?object $notifiable = null): ?string
    {
        if ($this->action_url !== null) {
            return $this->action_url;
        }

        if ($this->subject && method_exists($this->subject, 'notificationUrl')) {
            return $this->subject->notificationUrl($notifiable);
        }

        return null;
    }

    /**
     * The thumbnail, or the subject's notificationThumbnail()
     */
    public function resolvedThumbnail(?object $notifiable = null): ?string
    {
        if ($this->thumbnail !== null) {
            return $this->thumbnail;
        }

        if ($this->subject && method_exists($this->subject, 'notificationThumbnail')) {
            return $this->subject->notificationThumbnail($notifiable);
        }

        return null;
    }

    /**
     * The text parts, the ones stored per language
     *
     * @return array{title: string, body: string, action_text: ?string}
     */
    public function texts(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->bodyText(),
            'action_text' => $this->action_text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->texts(),
            'action_url' => $this->action_url,
            'icon' => $this->icon,
            'color' => $this->color,
            'thumbnail' => $this->thumbnail,
            'level' => $this->level,
            'data' => $this->data,
        ];
    }
}
