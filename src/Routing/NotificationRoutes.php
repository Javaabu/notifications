<?php

namespace Javaabu\Notifications\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Notifications\Http\Controllers\NotificationsController;

/**
 * Registers the notification routes for `Route::notifications()`
 *
 * ```php
 * Route::notifications();                                                  // /notifications, notifications.*, auth
 * Route::notifications(['guard' => 'web_admin', 'prefix' => 'admin/notifications', 'name' => 'admin.notifications']);
 * ```
 */
class NotificationRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'notifications_guard';

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string|null
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'notifications', '/');
        $name = rtrim($options['name'] ?? 'notifications', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controller = $options['controller'] ?? config('notifications.controllers.notifications', NotificationsController::class);

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
        ], function (Router $router) use ($controller) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/latest', [$controller, 'latest'])->name('latest');
            $router->post('/read-all', [$controller, 'readAll'])->name('read-all');
            $router->get('/{notification}', [$controller, 'show'])->name('show');
            $router->post('/{notification}/read', [$controller, 'read'])->name('read');
            $router->post('/{notification}/unread', [$controller, 'unread'])->name('unread');
            $router->delete('/{notification}', [$controller, 'destroy'])->name('destroy');
        });
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the current route, e.g. `admin.notifications`
     */
    public static function namePrefix(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, '.')
            ? substr($name, 0, strrpos($name, '.'))
            : 'notifications';
    }

    /**
     * Route names for each action under a prefix
     *
     * @return array<string, string>
     */
    public static function names(string $prefix = 'notifications'): array
    {
        return collect(['index', 'latest', 'read-all', 'show', 'read', 'unread', 'destroy'])
            ->mapWithKeys(fn (string $action) => [$action => "{$prefix}.{$action}"])
            ->all();
    }
}
