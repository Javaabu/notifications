<?php

namespace Javaabu\Notifications\Views;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;
use RuntimeException;

/**
 * Renders a package page with the stack chosen in `config('notifications.views')`
 */
class ViewRenderer
{
    public const string BLADE = 'blade';

    public const string INERTIA = 'inertia';

    /**
     * @param  string  $page  e.g. `notifications.index`, rendered as the Blade view `notifications::notifications.index`
     *                        or the Inertia page `notifications::Notifications/Index`
     * @param  array<string, mixed>  $data
     */
    public function render(string $page, array $data = []): View|Responsable
    {
        if ($this->stack() === self::INERTIA) {
            return $this->renderInertia($page, $data);
        }

        return view("notifications::{$page}", $data);
    }

    public function stack(): string
    {
        return config('notifications.views', self::BLADE);
    }

    public function isInertia(): bool
    {
        return $this->stack() === self::INERTIA;
    }

    /**
     * The Inertia page name for a page: `notifications.index` → `notifications::Notifications/Index`
     */
    public function inertiaPage(string $page): string
    {
        $segments = array_map(fn (string $segment) => str($segment)->studly()->toString(), explode('.', $page));

        return 'notifications::' . implode('/', $segments);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function renderInertia(string $page, array $data): Responsable
    {
        if (! class_exists(Inertia::class)) {
            throw new RuntimeException('Install inertiajs/inertia-laravel to use the inertia views, or set notifications.views to blade.');
        }

        return Inertia::render($this->inertiaPage($page), $data);
    }
}
