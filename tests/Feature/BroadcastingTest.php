<?php

namespace Javaabu\Notifications\Tests\Feature;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Javaabu\Notifications\Support\Broadcasting;
use Javaabu\Notifications\Tests\Http\InteractsWithNotifications;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Broadcasting\MemoryBroadcaster;
use Javaabu\Notifications\Tests\TestSupport\Models\Admin;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\WelcomeNotification;
use PHPUnit\Framework\Attributes\Test;

class BroadcastingTest extends TestCase
{
    use InteractsWithNotifications;

    protected MemoryBroadcaster $broadcaster;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $broadcaster = $this->broadcaster = new MemoryBroadcaster;

        $app->make(BroadcastManager::class)->extend('memory', fn () => $broadcaster);

        $app['config']->set('broadcasting.connections.memory', ['driver' => 'memory']);
        $app['config']->set('broadcasting.default', 'memory');
        $app['config']->set('notifications.broadcasting.enabled', true);
        $app['config']->set('notifications.channels', ['database']);
    }

    protected function tearDown(): void
    {
        Broadcasting::$reverb_class = 'Laravel\\Reverb\\ReverbServiceProvider';

        parent::tearDown();
    }

    #[Test]
    public function it_broadcasts_new_notifications_on_the_notifiables_channel(): void
    {
        $user = User::factory()->create();

        $user->notify(new WelcomeNotification);

        $this->assertCount(1, $this->broadcaster->broadcasts);

        $broadcast = $this->broadcaster->broadcasts[0];

        $this->assertEquals(["private-notifications.user.{$user->id}"], $broadcast['channels']);
        $this->assertEquals(BroadcastNotificationCreated::class, $broadcast['event']);
        $this->assertEquals('notification.created', $broadcast['payload']['type']);
        $this->assertEquals('Welcome', $broadcast['payload']['title']);
        $this->assertEquals($user->notifications()->first()->id, $broadcast['payload']['id']);
    }

    #[Test]
    public function it_doesnt_broadcast_notifications_that_skip_the_database(): void
    {
        config()->set('notifications.channels', ['mail']);

        User::factory()->create()->notify(new WelcomeNotification);

        $this->assertCount(0, $this->broadcaster->broadcasts);
    }

    #[Test]
    public function it_authorizes_the_channel_for_the_notifiable_only(): void
    {
        $user = User::factory()->create();
        $admin = Admin::factory()->create();
        $this->assertEquals($user->id, $admin->id);

        $this->assertTrue($this->broadcaster->authorize("notifications.user.{$user->id}", $user));
        $this->assertFalse($this->broadcaster->authorize("notifications.user.{$user->id}", $admin));
        $this->assertTrue($this->broadcaster->authorize("notifications.admin.{$admin->id}", $admin));
        $this->assertFalse($this->broadcaster->authorize('notifications.user.999', $user));
    }

    #[Test]
    public function pages_are_told_which_channel_to_listen_on(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/notifications/latest')
            ->assertJsonPath('meta.broadcasting', true)
            ->assertJsonPath('meta.channel', "notifications.user.{$user->id}");
    }

    #[Test]
    public function channel_names_are_safe_for_class_morph_types(): void
    {
        $this->assertEquals('App-Models-User', Broadcasting::encodeType('App\\Models\\User'));
    }

    #[Test]
    public function it_detects_reverb(): void
    {
        config()->set('notifications.broadcasting.enabled', 'auto');
        config()->set('broadcasting.default', 'reverb');

        Broadcasting::$reverb_class = User::class;
        $this->assertTrue(Broadcasting::enabled());

        config()->set('broadcasting.default', 'null');
        $this->assertFalse(Broadcasting::enabled());

        config()->set('broadcasting.default', 'log');
        $this->assertFalse(Broadcasting::enabled());

        config()->set('broadcasting.default', 'reverb');
        Broadcasting::$reverb_class = 'Missing\\ReverbServiceProvider';
        $this->assertFalse(Broadcasting::enabled());

        config()->set('notifications.broadcasting.enabled', 'false');
        $this->assertFalse(Broadcasting::enabled());

        config()->set('notifications.broadcasting.enabled', true);
        $this->assertTrue(Broadcasting::enabled());
    }

    #[Test]
    public function the_poll_interval_has_a_floor(): void
    {
        config()->set('notifications.broadcasting.poll_interval', 10);

        $this->assertEquals(1000, Broadcasting::pollInterval());
    }
}
