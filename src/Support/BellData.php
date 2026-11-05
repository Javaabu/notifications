<?php

namespace Javaabu\Notifications\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Javaabu\Notifications\Http\Resources\NotificationResource;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Routing\NotificationRoutes;

/**
 * What the React bell needs, for sharing with every Inertia page:
 *
 * ```php
 * // HandleInertiaRequests::share()
 * 'notifications' => fn () => BellData::make($request->user()),
 * 'admin_notifications' => fn () => BellData::make($request->user('web_admin'), 'admin.notifications'),
 * ```
 */
class BellData
{
    /**
     * @return array{
     *     unread_count: int,
     *     notifications: array<int, array<string, mixed>>,
     *     broadcasting: bool,
     *     channel: ?string,
     *     poll_interval: int,
     *     urls: array{index: string, latest: string, read_all: string}
     * }|null
     */
    public static function make(?Model $notifiable, string $routes = 'notifications', ?int $limit = null): ?array
    {
        if (! $notifiable) {
            return null;
        }

        $limit ??= (int) config('notifications.latest_limit', 10);
        $names = NotificationRoutes::names($routes);
        $query = Notifications::notificationModel()::query()->forNotifiable($notifiable);
        $request = app(Request::class);

        $notifications = (clone $query)->latest()->limit($limit)->get()
            ->map(fn (DatabaseNotification $notification) => (new NotificationResource($notification))
                ->routePrefix($routes)
                ->resolve($request))
            ->all();

        return [
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
            'notifications' => $notifications,
            'broadcasting' => Broadcasting::enabled(),
            'channel' => Broadcasting::enabled() ? Broadcasting::channelFor($notifiable) : null,
            'poll_interval' => Broadcasting::pollInterval(),
            'urls' => [
                'index' => route($names['index']),
                'latest' => route($names['latest'], ['limit' => $limit]),
                'read_all' => route($names['read-all']),
            ],
        ];
    }
}
