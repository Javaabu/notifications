<?php

namespace Javaabu\Notifications\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Javaabu\Notifications\Http\Controllers\NotificationsController;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Http\CustomNotificationsController;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Policies\DenyAllPolicy;
use Javaabu\Permissions\Models\Permission;
use PHPUnit\Framework\Attributes\Test;

class OverridingTest extends TestCase
{
    use InteractsWithNotifications;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('notifications.controllers.notifications', CustomNotificationsController::class);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->notifications();
            $router->notifications([
                'prefix' => 'inbox',
                'name' => 'inbox',
                'controller' => NotificationsController::class,
                'middleware' => ['auth', 'throttle:60,1'],
            ]);
        });
    }

    #[Test]
    public function the_controller_comes_from_config(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/notifications/latest')
            ->assertExactJson(['custom' => true]);
    }

    #[Test]
    public function the_macro_takes_a_controller_prefix_and_middleware(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/inbox/latest')
            ->assertOk()
            ->assertJsonPath('meta.urls.index', url('/inbox'));

        $this->assertContains('throttle:60,1', app('router')->getRoutes()->getByName('inbox.latest')->gatherMiddleware());
    }

    #[Test]
    public function apps_can_replace_the_policy(): void
    {
        Gate::policy(DatabaseNotification::class, DenyAllPolicy::class);

        $this->actingAs(User::factory()->create())
            ->getJson('/inbox')
            ->assertForbidden();
    }

    #[Test]
    public function it_registers_its_permissions(): void
    {
        $this->artisan('permissions:sync');

        $this->assertTrue(Permission::query()->where('name', 'view_others_notifications')->where('guard_name', 'web')->exists());
        $this->assertTrue(Permission::query()->where('name', 'delete_others_notifications')->where('guard_name', 'web_admin')->exists());
    }
}
