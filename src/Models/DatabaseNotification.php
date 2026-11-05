<?php

namespace Javaabu\Notifications\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\DatabaseNotification as BaseDatabaseNotification;
use Illuminate\Support\Str;
use Javaabu\Notifications\BaseNotification;
use Javaabu\Notifications\Notifications;

/**
 * A notification in the database: Laravel's model, with the subject it's about, the texts in
 * the reader's language, and its presentation (icon, colour, thumbnail, URL)
 *
 * @property string $id
 * @property string $type
 * @property array<string, mixed> $data
 * @property ?string $subject_type
 * @property int|string|null $subject_id
 */
class DatabaseNotification extends BaseDatabaseNotification
{
    public function getTable(): string
    {
        return Notifications::table();
    }

    /**
     * The model the notification is about
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForNotifiable(Builder $query, Model $notifiable): void
    {
        $query->where('notifiable_type', $notifiable->getMorphClass())
            ->where('notifiable_id', $notifiable->getKey());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAbout(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    public function isOwnedBy(?Model $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->notifiable_type === $user->getMorphClass()
            && (string) $this->notifiable_id === (string) $user->getKey();
    }

    /**
     * A text (title, body, action_text) in the given locale (the current one by default),
     * falling back to the text it was sent with
     */
    public function text(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        $value = $this->data['translations'][$locale][$key]
            ?? $this->data[$key]
            ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->text('title', $locale);
    }

    public function body(?string $locale = null): ?string
    {
        return $this->text('body', $locale) ?: null;
    }

    public function actionText(?string $locale = null): ?string
    {
        return $this->text('action_text', $locale) ?: null;
    }

    /**
     * Where the notification links to (followed by the redirect route)
     */
    public function actionUrl(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    public function icon(): string
    {
        return $this->data['icon'] ?? config('notifications.defaults.icon', 'bell');
    }

    public function color(): string
    {
        return $this->data['color'] ?? config('notifications.defaults.color', 'primary');
    }

    /**
     * The colour as one of Onu's tones (neutral, info, success, warning, danger), for the icon tile
     */
    public function tone(): string
    {
        return match ($this->color()) {
            'success' => 'success',
            'warning' => 'warning',
            'danger', 'error' => 'danger',
            'secondary', 'neutral' => 'neutral',
            default => 'info',
        };
    }

    public function thumbnail(): ?string
    {
        return $this->data['thumbnail'] ?? null;
    }

    /**
     * Extra data the notification was sent with
     *
     * @return array<string, mixed>
     */
    public function extraData(): array
    {
        return $this->data['data'] ?? [];
    }

    /**
     * The notification type's label: its label() when the class has one, or its name
     */
    public function typeLabel(): string
    {
        return static::labelForType($this->type);
    }

    public static function labelForType(string $type): string
    {
        if (is_a($type, BaseNotification::class, true)) {
            return $type::label();
        }

        return Str::of(class_basename($type))
            ->beforeLast('Notification')
            ->snake(' ')
            ->ucfirst()
            ->toString() ?: $type;
    }
}
