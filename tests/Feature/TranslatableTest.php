<?php

namespace Javaabu\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Support\Translations;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\WelcomeNotification;
use Javaabu\Translatable\Testing\InteractsWithLanguages;
use Javaabu\Translatable\Translatable;
use PHPUnit\Framework\Attributes\Test;

class TranslatableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(Translatable::class)) {
            $this->markTestSkipped('javaabu/translatable is not installed');
        }

    }

    #[Test]
    public function it_stores_the_texts_in_every_active_language(): void
    {
        $this->languages(['en', 'dv']);

        $user = User::factory()->create(['locale' => 'en']);

        $user->notifyNow(new WelcomeNotification, ['database']);

        $notification = $user->notifications()->first();

        $this->assertEquals([
            'en' => ['title' => 'Welcome', 'body' => 'Thanks for joining.', 'action_text' => null],
            'dv' => ['title' => 'މަރުޙަބާ', 'body' => 'ބައިވެރިވީތީ ޝުކުރިއްޔާ.', 'action_text' => null],
        ], $notification->data['translations']);

        $this->assertEquals('Welcome', $notification->title());
        $this->assertEquals('މަރުޙަބާ', $notification->title('dv'));

        app()->setLocale('dv');

        $this->assertEquals('ބައިވެރިވީތީ ޝުކުރިއްޔާ.', $notification->body());
    }

    #[Test]
    public function readers_see_notifications_in_their_own_language(): void
    {
        $this->languages(['en', 'dv']);

        $user = User::factory()->create(['locale' => 'en']);
        $user->notifyNow(new WelcomeNotification, ['database']);

        app()->setLocale('dv');

        $this->actingAs($user);

        $this->assertEquals('މަރުޙަބާ', $user->notifications()->first()->title());
    }

    #[Test]
    public function only_active_languages_are_used_for_the_notifiable(): void
    {
        $this->languages(['en', 'dv']);

        $this->assertEquals('dv', Notifications::localeFor(User::factory()->make(['locale' => 'dv'])));
        $this->assertEquals('en', Notifications::localeFor(User::factory()->make(['locale' => 'fr'])));
        $this->assertNull(Notifications::localeFor(User::factory()->make(['locale' => null])));
    }

    #[Test]
    public function it_works_without_translations(): void
    {
        $this->languages(['en', 'dv']);

        $this->withoutTranslatable(function () {
            $user = User::factory()->create(['locale' => 'dv']);

            $user->notifyNow(new WelcomeNotification, ['database']);

            $notification = $user->notifications()->first();

            $this->assertArrayNotHasKey('translations', $notification->data);
            $this->assertEquals('މަރުޙަބާ', $notification->title());
            $this->assertEquals('fr', Notifications::localeFor(User::factory()->make(['locale' => 'fr'])));
            $this->assertFalse(Translations::enabled());
        });
    }

    #[Test]
    public function it_can_be_turned_off_in_config(): void
    {
        $this->languages(['en', 'dv']);

        config()->set('notifications.translations', false);

        $this->assertFalse(Translations::enabled());
        $this->assertEquals([], Translations::locales());
    }

    #[Test]
    public function one_language_needs_no_translations(): void
    {
        $this->languages(['en']);

        $user = User::factory()->create();
        $user->notifyNow(new WelcomeNotification, ['database']);

        $this->assertArrayNotHasKey('translations', $user->notifications()->first()->data);
    }

    /**
     * @param  array<int, string>  $codes
     */
    protected function languages(array $codes): void
    {
        $helper = new class
        {
            use InteractsWithLanguages {
                setUpLanguages as public;
                withoutTranslatable as public;
            }
        };

        $helper->setUpLanguages($codes);
    }

    protected function withoutTranslatable(callable $callback): mixed
    {
        $enabled = config('translatable.enabled');

        config(['translatable.enabled' => false]);

        try {
            return $callback();
        } finally {
            config(['translatable.enabled' => $enabled]);
        }
    }
}
