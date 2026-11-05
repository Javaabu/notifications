<?php

namespace Javaabu\Notifications\Support;

use Javaabu\Translatable\Locale;
use Javaabu\Translatable\Translatable;
use Throwable;

/**
 * The one place that talks to javaabu/translatable, which is optional: every method
 * checks that it's installed first
 */
class Translations
{
    /** The class whose presence means javaabu/translatable is installed */
    public static string $translatable_class = Translatable::class;

    /**
     * Whether javaabu/translatable is installed and enabled, `notifications.translations`
     * isn't off, and at least one language is active
     */
    public static function enabled(): bool
    {
        if (! config('notifications.translations', true)) {
            return false;
        }

        if (! class_exists(static::$translatable_class)) {
            return false;
        }

        try {
            return Translatable::enabled();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * The codes of the active languages, the default first (empty when translatable is off)
     *
     * @return array<int, string>
     */
    public static function locales(): array
    {
        if (! static::enabled()) {
            return [];
        }

        return Translatable::locales()
            ->map(fn (Locale $locale) => $locale->code)
            ->values()
            ->all();
    }

    public static function isActive(string $locale): bool
    {
        return in_array($locale, static::locales(), true);
    }

    public static function defaultLocale(): ?string
    {
        if (! static::enabled()) {
            return null;
        }

        return Translatable::defaultLocale();
    }
}
