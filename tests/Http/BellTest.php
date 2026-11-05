<?php

namespace Javaabu\Notifications\Tests\Http;

use Illuminate\Support\Facades\Blade;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Notifications\Support\BellData;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\Admin;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class BellTest extends TestCase
{
    use InteractsWithNotifications;

    protected function setUp(): void
    {
        parent::setUp();

        Manifest::fake();
    }

    #[Test]
    public function the_bell_shows_the_unread_count_and_the_latest_notifications(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);
        $this->notifyOrderShipped($user, 'ORD-2')->markAsRead();

        $this->actingAs($user);

        $html = Blade::render('<x-notifications::bell :limit="5" />');

        $this->assertStringContainsString('data-notifications-bell', $html);
        $this->assertStringContainsString('data-notifications-count>1</span>', $html);
        $this->assertStringContainsString('aria-label="Notifications (1 unread)"', $html);
        $this->assertStringContainsString(url('/notifications/latest?limit=5'), $html);
        $this->assertStringContainsString(url("/notifications/{$notification->id}"), $html);
        $this->assertStringContainsString('data-broadcasting="false"', $html);
        $this->assertStringContainsString('data-poll-interval="30000"', $html);
        $this->assertStringContainsString('Order ORD-2 is on its way.', $html);
    }

    #[Test]
    public function the_bell_renders_nothing_for_guests(): void
    {
        $this->assertEquals('', trim(Blade::render('<x-notifications::bell />')));
    }

    #[Test]
    public function the_bell_works_with_another_guard(): void
    {
        $admin = Admin::factory()->create();
        $notification = $this->notifyOrderShipped($admin);

        $this->actingAs($admin, 'web_admin');

        $html = Blade::render('<x-notifications::bell guard="web_admin" routes="admin.notifications" />');

        $this->assertStringContainsString(url("/admin/notifications/{$notification->id}"), $html);
        $this->assertStringContainsString(url('/admin/notifications/read-all'), $html);
    }

    #[Test]
    public function the_bell_is_translated_to_dhivehi(): void
    {
        app()->setLocale('dv');

        $this->actingAs(User::factory()->create());

        $html = Blade::render('<x-notifications::bell />');

        $this->assertStringContainsString('ނޯޓިފިކޭޝަންތައް', $html);
    }

    #[Test]
    public function the_latest_route_renders_the_items_for_the_blade_bell(): void
    {
        $user = User::factory()->create();
        $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->getJson('/notifications/latest?html=1')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertSee('data-slot=\"notification\"', false);
    }

    #[Test]
    public function bell_data_is_what_the_react_bell_needs(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user);

        $data = BellData::make($user, limit: 3);

        $this->assertEquals(1, $data['unread_count']);
        $this->assertEquals($notification->id, $data['notifications'][0]['id']);
        $this->assertEquals(url("/notifications/{$notification->id}"), $data['notifications'][0]['links']['show']);
        $this->assertEquals(url('/notifications/latest?limit=3'), $data['urls']['latest']);
        $this->assertFalse($data['broadcasting']);
        $this->assertNull($data['channel']);
        $this->assertNull(BellData::make(null));
    }
}
