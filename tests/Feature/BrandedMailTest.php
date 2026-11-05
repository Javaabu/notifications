<?php

namespace Javaabu\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Mail\InvoiceMail;
use Javaabu\Notifications\Tests\TestSupport\Models\Order;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\OrderShippedNotification;
use Javaabu\Notifications\Tests\TestSupport\Notifications\WelcomeNotification;
use PHPUnit\Framework\Attributes\Test;

class BrandedMailTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('notifications.mail.branding', [
            'name' => 'Javaabu',
            'url' => 'https://javaabu.com',
            'logo' => 'https://javaabu.com/logo.png',
            'logo_height' => 40,
            'colors' => [
                'primary' => '#123456',
                'background' => '#fafafa',
            ],
            'footer' => 'Javaabu Pvt. Ltd., Malé',
        ]);
    }

    #[Test]
    public function notification_mails_use_the_branding(): void
    {
        $html = $this->renderMail(new OrderShippedNotification(Order::query()->create(['number' => 'ORD-1'])), User::factory()->create());

        $this->assertStringContainsString('src="https://javaabu.com/logo.png"', $html);
        $this->assertStringContainsString('alt="Javaabu"', $html);
        $this->assertStringContainsString('href="https://javaabu.com"', $html);
        $this->assertStringContainsString('Javaabu Pvt. Ltd., Malé', $html);
        $this->assertStringContainsString('Order ORD-1 is on its way.', $html);
        $this->assertStringContainsString('Track order', $html);
        $this->assertStringContainsString('background-color: #fafafa', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('Regards,', $html);
    }

    #[Test]
    public function the_button_uses_the_primary_colour(): void
    {
        $html = $this->renderMail(new WelcomeNotification, User::factory()->create(), function ($mail) {
            $mail->action('Open', 'https://javaabu.com/open');
        });

        $this->assertMatchesRegularExpression('/class="button button-primary"[^>]*style="[^"]*background-color: #123456/', $html);
    }

    #[Test]
    public function the_success_level_uses_the_success_colour(): void
    {
        $html = $this->renderMail(new OrderShippedNotification(Order::query()->create(['number' => 'ORD-1'])), User::factory()->create());

        $this->assertMatchesRegularExpression('/class="button button-success"[^>]*style="[^"]*background-color: #15803d/', $html);
    }

    #[Test]
    public function dhivehi_mails_are_right_to_left(): void
    {
        $html = $this->renderMail(new WelcomeNotification, User::factory()->create(['locale' => 'dv']));

        $this->assertStringContainsString('lang="dv" dir="rtl"', $html);
        $this->assertStringContainsString('direction: rtl', $html);
        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringContainsString('އިޚްތިރާމާއެކު،', $html);
    }

    #[Test]
    public function the_text_version_has_the_branding(): void
    {
        $mail = (new WelcomeNotification)->toMail(User::factory()->create());

        $text = (string) app(Markdown::class)->renderText($mail->markdown, $mail->data());

        $this->assertStringContainsString('Javaabu: https://javaabu.com', $text);
        $this->assertStringContainsString('Thanks for joining.', $text);
        $this->assertStringContainsString('Javaabu Pvt. Ltd., Malé', $text);
        $this->assertStringNotContainsString('<table', $text);
    }

    #[Test]
    public function branding_can_be_turned_off(): void
    {
        config()->set('notifications.mail.branded', false);

        $mail = (new WelcomeNotification)->toMail(User::factory()->create());

        $this->assertEquals('notifications::email', $mail->markdown);
        $this->assertStringNotContainsString('https://javaabu.com/logo.png', (string) $mail->render());
    }

    #[Test]
    public function mailables_can_use_the_branded_layout(): void
    {
        $html = (new InvoiceMail)->render();

        $this->assertStringContainsString('src="https://javaabu.com/logo.png"', $html);
        $this->assertStringContainsString('Invoice INV-1 is ready.', $html);
        $this->assertMatchesRegularExpression('/class="button button-primary"[^>]*style="[^"]*background-color: #123456/', $html);
    }

    protected function renderMail(object $notification, User $user, ?callable $tap = null): string
    {
        $mail = $notification->toMail($user);

        if ($tap) {
            $tap($mail);
        }

        return (string) $mail->render();
    }
}
