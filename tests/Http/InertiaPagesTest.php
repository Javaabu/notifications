<?php

namespace Javaabu\Notifications\Tests\Http;

use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia;
use Javaabu\Notifications\Tests\TestCase;
use Javaabu\Notifications\Tests\TestSupport\Models\User;
use Javaabu\Notifications\Tests\TestSupport\Notifications\OrderShippedNotification;
use PHPUnit\Framework\Attributes\Test;

class InertiaPagesTest extends TestCase
{
    use InteractsWithNotifications;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), InertiaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('notifications.views', 'inertia');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);
    }

    #[Test]
    public function the_index_renders_the_inertia_page(): void
    {
        app()->setLocale('dv');

        $user = User::factory()->create();
        $notification = $this->notifyOrderShipped($user);

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('notifications::Notifications/Index')
                ->where('title', 'ނޯޓިފިކޭޝަންތައް')
                ->where('notifications.data.0.id', $notification->id)
                ->where('notifications.data.0.tone', 'success')
                ->where('notifications.data.0.links.show', url("/notifications/{$notification->id}"))
                ->where('is_own', true)
                ->where('filters.status', null)
                ->where('statuses.0.value', 'unread')
                ->where('types.0.value', OrderShippedNotification::class)
                ->where('broadcasting', false)
                ->where('poll_interval', 30000)
                ->where('urls.read_all', url('/notifications/read-all'))
                ->where('strings.mark_all_read', 'ހުރިހާ ނޯޓިފިކޭޝަނެއް ކިޔުނީއޭ ބަލާ')
                ->has('notifications.meta.last_page'));
    }

    #[Test]
    public function the_page_exists_where_manifest_resolves_it(): void
    {
        $this->assertFileExists(__DIR__ . '/../../resources/js/Pages/Notifications/Index.tsx');
    }
}
