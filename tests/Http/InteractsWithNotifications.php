<?php

namespace Javaabu\Notifications\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Tests\TestSupport\Models\Order;
use Javaabu\Notifications\Tests\TestSupport\Notifications\OrderShippedNotification;

trait InteractsWithNotifications
{
    use RefreshDatabase;

    protected function setUpNotifications(): void
    {
        $this->artisan('permissions:sync');
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->notifications();
            $router->notifications(['guard' => 'web_admin', 'prefix' => 'admin/notifications', 'name' => 'admin.notifications']);
            $router->get('/login', fn () => 'login')->name('login');
        });
    }

    protected function notifyOrderShipped(Model $notifiable, string $number = 'ORD-1'): DatabaseNotification
    {
        $order = Order::query()->create(['number' => $number]);

        $notifiable->notifyNow(new OrderShippedNotification($order), ['database']);

        return $notifiable->notifications()->latest()->first();
    }
}
