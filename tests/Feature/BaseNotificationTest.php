<?php

namespace Javaabu\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Javaabu\Notifications\Mail\BrandedMailMessage;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\Order;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\OrderShippedNotification;
use Javaabu\Notifications\Tests\TestSupport\Notifications\WelcomeNotification;
use PHPUnit\Framework\Attributes\Test;

class BaseNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_through_the_channels_the_notifiable_can_receive(): void
    {
        $user = User::factory()->create(['phone' => '7771234']);
        $notification = new OrderShippedNotification($this->order());

        $this->assertEquals(
            ['database', 'mail', config('sms.channels.dhiraagu')],
            $notification->via($user),
        );
    }

    #[Test]
    public function it_skips_channels_without_a_route(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->assertEquals(['database'], (new OrderShippedNotification($this->order()))->via($user));
    }

    #[Test]
    public function it_sends_push_notifications_when_the_notifiable_has_a_token(): void
    {
        if (! Notifications::fcmInstalled()) {
            $this->markTestSkipped('laravel-notification-channels/fcm is not installed');
        }

        $user = User::factory()->create(['fcm_token' => 'token-1']);

        $this->assertContains(Notifications::$fcm_channel, (new OrderShippedNotification($this->order()))->via($user));
    }

    #[Test]
    public function it_uses_the_default_channels_from_config(): void
    {
        config()->set('notifications.channels', ['database']);

        $user = User::factory()->create();

        $this->assertEquals(['database'], (new WelcomeNotification)->via($user));
    }

    #[Test]
    public function it_leaves_out_channels_the_notifiable_turned_off(): void
    {
        $user = User::factory()->create(['phone' => '7771234']);
        $user->setNotificationPreference('order-shipped', 'mail', false)
            ->setNotificationPreference('*', 'sms', false)
            ->save();

        $this->assertEquals(['database'], (new OrderShippedNotification($this->order()))->via($user->refresh()));
        $this->assertEquals(['database', 'mail'], (new WelcomeNotification)->via($user));
    }

    #[Test]
    public function a_type_preference_wins_over_the_wildcard(): void
    {
        $user = User::factory()->create(['phone' => '7771234']);
        $user->setNotificationPreference('*', 'sms', false)
            ->setNotificationPreference('order-shipped', 'sms', true)
            ->save();

        $this->assertContains(config('sms.channels.dhiraagu'), (new OrderShippedNotification($this->order()))->via($user->refresh()));
        $this->assertTrue($user->notificationPreference('order-shipped', 'sms'));
        $this->assertNull($user->notificationPreference('order-shipped', 'mail'));
    }

    #[Test]
    public function it_clears_a_preference(): void
    {
        $user = User::factory()->create();
        $user->setNotificationPreference('order-shipped', 'mail', false)->save();
        $user->setNotificationPreference('order-shipped', 'mail', null)->save();

        $this->assertNull($user->refresh()->notification_preferences);
    }

    #[Test]
    public function required_channels_cannot_be_turned_off(): void
    {
        $user = User::factory()->create();
        $user->setNotificationPreference('*', 'database', false)->save();

        $this->assertContains('database', (new WelcomeNotification)->via($user->refresh()));
    }

    #[Test]
    public function it_notifies_anonymous_notifiables_by_mail(): void
    {
        $notifiable = (new AnonymousNotifiable)->route('mail', 'someone@example.com');

        $this->assertEquals(['mail'], (new OrderShippedNotification($this->order()))->via($notifiable));
    }

    #[Test]
    public function it_broadcasts_database_notifications_when_live_updates_are_on(): void
    {
        config()->set('notifications.broadcasting.enabled', true);

        $user = User::factory()->create();

        $this->assertEquals(['database', 'mail', 'broadcast'], (new WelcomeNotification)->via($user));
    }

    #[Test]
    public function it_stores_the_message_in_the_database(): void
    {
        $user = User::factory()->create();
        $order = $this->order();

        $user->notifyNow(new OrderShippedNotification($order), ['database']);

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->first();

        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertEquals(OrderShippedNotification::class, $notification->type);
        $this->assertEquals('Your order has shipped', $notification->title());
        $this->assertEquals('Order ORD-1 is on its way.', $notification->body());
        $this->assertEquals('Track order', $notification->actionText());
        $this->assertEquals(url("/orders/{$order->id}/track"), $notification->actionUrl());
        $this->assertEquals('truck', $notification->icon());
        $this->assertEquals('success', $notification->color());
        $this->assertEquals(url("/images/orders/{$order->id}.png"), $notification->thumbnail());
        $this->assertEquals(['order_id' => $order->id, 'items' => [1, 2]], $notification->extraData());
        $this->assertEquals('Order shipped', $notification->typeLabel());

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'subject_type' => 'order',
            'subject_id' => $order->id,
        ]);

        $this->assertTrue($notification->subject->is($order));
    }

    #[Test]
    public function it_turns_the_message_into_a_branded_mail(): void
    {
        $user = User::factory()->create();

        $mail = (new OrderShippedNotification($this->order()))->toMail($user);

        $this->assertInstanceOf(BrandedMailMessage::class, $mail);
        $this->assertEquals('Your order has shipped', $mail->subject);
        $this->assertEquals(['Order ORD-1 is on its way.'], $mail->introLines);
        $this->assertEquals('Track order', $mail->actionText);
        $this->assertEquals('success', $mail->level);
        $this->assertEquals('notifications::mail.notification', $mail->markdown);
    }

    #[Test]
    public function it_turns_the_message_into_an_sms(): void
    {
        $user = User::factory()->create();
        $order = $this->order();

        $this->assertEquals(
            "Your order has shipped\n\nOrder ORD-1 is on its way.\n\n" . url("/orders/{$order->id}/track"),
            (new OrderShippedNotification($order))->toSms($user),
        );
        $this->assertEquals(
            "Your order has shipped\n\nOrder ORD-1 is on its way.\n\n" . url("/orders/{$order->id}/track"),
            (new OrderShippedNotification($order))->toDhiraagu($user),
        );
    }

    #[Test]
    public function it_turns_the_message_into_a_push_notification(): void
    {
        if (! Notifications::fcmInstalled()) {
            $this->markTestSkipped('laravel-notification-channels/fcm is not installed');
        }

        $user = User::factory()->create();
        $order = $this->order();
        $notification = new OrderShippedNotification($order);
        $notification->id = 'abc';

        $fcm = $notification->toFcm($user);

        $this->assertEquals('Your order has shipped', $fcm->notification->title);
        $this->assertEquals('Order ORD-1 is on its way.', $fcm->notification->body);
        $this->assertEquals(url("/images/orders/{$order->id}.png"), $fcm->notification->image);
        $this->assertEquals([
            'id' => 'abc',
            'type' => OrderShippedNotification::class,
            'url' => url("/orders/{$order->id}/track"),
            'order_id' => (string) $order->id,
            'items' => '[1,2]',
        ], $fcm->data);
    }

    #[Test]
    public function it_notifies_in_the_notifiables_locale(): void
    {

        $user = User::factory()->create(['locale' => 'dv']);

        $user->notifyNow(new WelcomeNotification, ['database']);

        $this->assertEquals('މަރުޙަބާ', $user->notifications()->first()->title());
        $this->assertEquals('en', app()->getLocale());

        $mail = (new WelcomeNotification)->toMail($user);

        $this->assertEquals('މަރުޙަބާ', $mail->subject);
        $this->assertEquals('dv', $mail->viewData['locale']);
    }

    #[Test]
    public function the_type_has_a_label_and_a_preference_key(): void
    {
        $this->assertEquals('Welcome', WelcomeNotification::label());
        $this->assertEquals('welcome', WelcomeNotification::preferenceKey());
        $this->assertEquals('order-shipped', OrderShippedNotification::preferenceKey());
    }

    #[Test]
    public function it_sends_through_laravel(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->notify(new WelcomeNotification);

        Notification::assertSentTo($user, WelcomeNotification::class, function (WelcomeNotification $notification, array $channels) {
            return $channels === ['database', 'mail'];
        });
    }

    protected function order(): Order
    {
        return Order::query()->create(['number' => 'ORD-1']);
    }
}
