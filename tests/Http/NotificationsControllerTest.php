<?php

namespace Javaabu\Notifications\Tests\Http;

use Illuminate\Support\Carbon;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\Admin;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\OrderShippedNotification;
use Javaabu\Notifications\Tests\TestSupport\Notifications\WelcomeNotification;
use PHPUnit\Framework\Attributes\Test;

class NotificationsControllerTest extends TestCase
{
    use InteractsWithNotifications;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpNotifications();
    }

    #[Test]
    public function it_lists_the_users_own_notifications_as_json(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $notification = $this->notifyOrderShipped($user);
        $this->notifyOrderShipped($other, 'ORD-2');

        $this->actingAs($user)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.title', 'Your order has shipped')
            ->assertJsonPath('data.0.body', 'Order ORD-1 is on its way.')
            ->assertJsonPath('data.0.icon', 'truck')
            ->assertJsonPath('data.0.tone', 'success')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.0.type_label', 'Order shipped')
            ->assertJsonPath('data.0.links.show', url("/notifications/{$notification->id}"))
            ->assertJsonPath('data.0.can.update', true)
            ->assertJsonPath('meta.broadcasting', false)
            ->assertJsonPath('meta.urls.latest', url('/notifications/latest'));
    }

    #[Test]
    public function it_filters_by_status_and_type(): void
    {
        $user = User::factory()->create();

        $read = $this->notifyOrderShipped($user);
        $read->markAsRead();
        $user->notifyNow(new WelcomeNotification, ['database']);

        $this->actingAs($user)
            ->getJson('/notifications?status=unread')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', WelcomeNotification::class);

        $this->actingAs($user)
            ->getJson('/notifications?status=read')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $read->id);

        $this->actingAs($user)
            ->getJson('/notifications?type=' . urlencode(OrderShippedNotification::class))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $read->id);
    }

    #[Test]
    public function the_latest_route_feeds_the_bell(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $number) {
            Carbon::setTestNow(now()->addMinute());
            $this->notifyOrderShipped($user, "ORD-{$number}");
        }

        $user->notifications()->latest()->first()->markAsRead();

        $this->actingAs($user)
            ->getJson('/notifications/latest?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.body', 'Order ORD-3 is on its way.')
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('meta.poll_interval', 30000);
    }

    #[Test]
    public function opening_a_notification_marks_it_read_and_redirects_to_its_url(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect($notification->actionUrl());

        $this->assertNotNull($notification->refresh()->read_at);
    }

    #[Test]
    public function a_notification_without_a_url_redirects_to_the_notifications_page(): void
    {
        $user = User::factory()->create();
        $user->notifyNow(new WelcomeNotification, ['database']);

        $this->actingAs($user)
            ->get('/notifications/' . $user->notifications()->first()->id)
            ->assertRedirect(url('/notifications'));
    }

    #[Test]
    public function it_marks_notifications_read_and_unread(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->postJson("/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.read', true)
            ->assertJsonPath('meta.unread_count', 0);

        $this->actingAs($user)
            ->post("/notifications/{$notification->id}/unread")
            ->assertRedirect()
            ->assertSessionHas('success', 'Marked as unread');

        $this->assertNull($notification->refresh()->read_at);
    }

    #[Test]
    public function it_marks_all_notifications_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->notifyOrderShipped($user);
        $this->notifyOrderShipped($user, 'ORD-2');
        $others = $this->notifyOrderShipped($other, 'ORD-3');

        $this->actingAs($user)
            ->postJson('/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('marked', 2);

        $this->assertEquals(0, $user->unreadNotifications()->count());
        $this->assertNull($others->refresh()->read_at);
    }

    #[Test]
    public function it_deletes_a_notification(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->delete("/notifications/{$notification->id}")
            ->assertRedirect()
            ->assertSessionHas('success', 'Notification deleted');

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    #[Test]
    public function users_cannot_touch_other_users_notifications(): void
    {
        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped(User::factory()->create());

        $this->actingAs($user)->getJson("/notifications/{$notification->id}")->assertForbidden();
        $this->actingAs($user)->postJson("/notifications/{$notification->id}/read")->assertForbidden();
        $this->actingAs($user)->deleteJson("/notifications/{$notification->id}")->assertForbidden();
        $this->actingAs($user)
            ->getJson("/notifications?notifiable_type=user&notifiable_id={$notification->notifiable_id}")
            ->assertForbidden();

        $this->assertNull($notification->refresh()->read_at);
    }

    #[Test]
    public function the_view_others_permission_shows_other_users_notifications(): void
    {
        $support = User::factory()->create();
        $support->givePermissionTo('view_others_notifications');

        $customer = User::factory()->create();
        $notification = $this->notifyOrderShipped($customer);

        $this->actingAs($support)
            ->getJson("/notifications?notifiable_type=user&notifiable_id={$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.can.update', false)
            ->assertJsonPath('data.0.can.delete', false);

        $this->actingAs($support)
            ->getJson("/notifications/{$notification->id}")
            ->assertOk();

        $this->assertNull($notification->refresh()->read_at, 'Viewing someone else\'s notification does not mark it read');
    }

    #[Test]
    public function the_delete_others_permission_deletes_other_users_notifications(): void
    {
        $support = User::factory()->create();
        $support->givePermissionTo('delete_others_notifications');

        $notification = $this->notifyOrderShipped(User::factory()->create());

        $this->actingAs($support)
            ->deleteJson("/notifications/{$notification->id}")
            ->assertNoContent();
    }

    #[Test]
    public function admins_use_their_own_guard_and_routes(): void
    {
        $admin = Admin::factory()->create();
        $notification = $this->notifyOrderShipped($admin);
        $this->notifyOrderShipped(User::factory()->create(), 'ORD-2');

        $this->actingAs($admin, 'web_admin')
            ->getJson('/admin/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.links.show', url("/admin/notifications/{$notification->id}"))
            ->assertJsonPath('meta.urls.index', url('/admin/notifications'));

        $this->actingAs($admin, 'web_admin')
            ->postJson("/admin/notifications/{$notification->id}/read")
            ->assertOk();
    }

    #[Test]
    public function admins_cannot_use_the_user_routes(): void
    {
        // actingAs() also makes the guard the default; a real session doesn't
        $this->actingAs(Admin::factory()->create(), 'web_admin');
        $this->app['auth']->shouldUse('web');

        $this->getJson('/notifications')->assertUnauthorized();
    }

    #[Test]
    public function users_cannot_use_the_admin_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/admin/notifications')
            ->assertUnauthorized();
    }

    #[Test]
    public function guests_cannot_see_notifications(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
        $this->get('/notifications')->assertRedirect('/login');
    }

    #[Test]
    public function a_user_with_the_same_id_cannot_open_an_admins_notification(): void
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $this->assertEquals($admin->id, $user->id);

        $notification = $this->notifyOrderShipped($admin);

        $this->actingAs($user)->getJson("/notifications/{$notification->id}")->assertForbidden();
    }
}
