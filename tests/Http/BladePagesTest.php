<?php

namespace Javaabu\Notifications\Tests\Http;

use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class BladePagesTest extends TestCase
{
    use InteractsWithNotifications;

    protected function setUp(): void
    {
        parent::setUp();

        Manifest::fake();

        $this->setUpNotifications();
    }

    #[Test]
    public function the_index_lists_notifications_with_their_actions(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Your order has shipped')
            ->assertSee('Order ORD-1 is on its way.')
            ->assertSee(url("/notifications/{$notification->id}"), false)
            ->assertSee(url("/notifications/{$notification->id}/read"), false)
            ->assertSee('data-read="false"', false)
            ->assertSee('Mark all as read')
            ->assertSee('data-tone="success"', false);
    }

    #[Test]
    public function the_index_shows_an_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/notifications')
            ->assertOk()
            ->assertSee('No notifications')
            ->assertDontSee('Mark all as read');
    }

    #[Test]
    public function the_index_is_translated_to_dhivehi(): void
    {
        app()->setLocale('dv');

        $user = User::factory()->create();
        $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('ނޯޓިފިކޭޝަންތައް')
            ->assertSee('ހުރިހާ ނޯޓިފިކޭޝަނެއް ކިޔުނީއޭ ބަލާ');
    }
}
