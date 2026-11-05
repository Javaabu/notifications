<?php

namespace Javaabu\Notifications\Tests\Unit;

use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Support\Translations;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\Test;

class TranslationsSupportTest extends TestCase
{
    protected function tearDown(): void
    {
        Translations::$translatable_class = Translatable::class;

        parent::tearDown();
    }

    #[Test]
    public function it_is_off_when_translatable_is_not_installed(): void
    {
        Translations::$translatable_class = 'Missing\\Translatable';

        $this->assertFalse(Translations::enabled());
        $this->assertEquals([], Translations::locales());
        $this->assertNull(Translations::defaultLocale());
        $this->assertFalse(Translations::isActive('dv'));
    }

    #[Test]
    public function the_notifiable_locale_is_used_as_is_without_translatable(): void
    {
        Translations::$translatable_class = 'Missing\\Translatable';

        $this->assertEquals('dv', Notifications::localeFor(new User(['locale' => 'dv'])));
    }

    #[Test]
    public function the_locale_attribute_can_be_turned_off(): void
    {
        config()->set('notifications.locale_attribute', null);

        $this->assertNull(Notifications::localeFor(new User(['locale' => 'dv'])));
    }

    #[Test]
    public function a_missing_locale_attribute_is_ignored(): void
    {
        config()->set('notifications.locale_attribute', 'language');

        $this->assertNull(Notifications::localeFor(new User(['locale' => 'dv'])));
    }
}
