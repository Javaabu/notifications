@manifestRequire('notifications::notifications')
<div {{ $attributes->merge(['class' => 'relative inline-block']) }}
     data-notifications-bell
     data-latest-url="{{ route($route_names['latest'], ['limit' => $limit]) }}"
     data-read-all-url="{{ route($route_names['read-all']) }}"
     data-broadcasting="{{ $broadcasting() ? 'true' : 'false' }}"
     data-channel="{{ $channel() }}"
     data-poll-interval="{{ $pollInterval() }}"
     data-csrf="{{ csrf_token() }}"
     data-label="{{ __('Notifications') }}"
     data-label-count="{{ __('Notifications (:count unread)', ['count' => ':count']) }}"
     data-new-notifications="{{ __(':count new notifications', ['count' => ':count']) }}">
    <button type="button"
            class="relative inline-flex size-9 items-center justify-center rounded-md text-foreground hover:bg-accent focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
            aria-haspopup="true"
            aria-expanded="false"
            aria-label="{{ __('Notifications (:count unread)', ['count' => $unread_count]) }}"
            data-notifications-trigger>
        @include('notifications::partials.icon', ['name' => 'bell', 'class' => 'size-5'])
        <span @class([
                  'absolute -top-0.5 -end-0.5 h-4 min-w-4 rounded-full bg-destructive px-1 text-[0.625rem] font-semibold leading-4 text-destructive-foreground tabular-nums ring-2 ring-background',
                  'hidden' => $unread_count === 0,
              ])
              aria-hidden="true"
              data-notifications-count>{{ $unread_count > 99 ? '99+' : $unread_count }}</span>
    </button>

    <div class="absolute end-0 top-full z-50 mt-2 hidden w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-lg border bg-popover text-popover-foreground shadow-lg"
         role="region"
         aria-label="{{ __('Notifications') }}"
         data-notifications-panel>
        <div class="flex items-center justify-between gap-2 border-b px-3 py-2">
            <span class="text-sm font-semibold">{{ __('Notifications') }}</span>
            <button type="button"
                    @class(['rounded-md px-2 py-1 text-sm hover:bg-accent', 'hidden' => $unread_count === 0])
                    data-notifications-read-all>
                {{ __('Mark all as read') }}
            </button>
        </div>

        <ul class="max-h-[min(28rem,60vh)] divide-y divide-border overflow-y-auto overscroll-contain" data-notifications-list>
            @foreach($notifications as $notification)
                @include('notifications::notifications.partials.item', [
                    'notification' => $notification,
                    'routes' => $route_names,
                    'actions' => false,
                ])
            @endforeach
        </ul>

        <div @class(['px-3 py-10 text-center', 'hidden' => $notifications->isNotEmpty()]) data-notifications-empty>
            <p class="text-sm font-medium">{{ __('No notifications') }}</p>
            <p class="text-xs text-muted-foreground">{{ __('You\'re all caught up.') }}</p>
        </div>

        <div class="border-t p-1">
            <a href="{{ route($route_names['index']) }}"
               class="block rounded-md px-3 py-2 text-center text-sm hover:bg-accent">
                {{ __('View all notifications') }}
            </a>
        </div>

        <span class="sr-only" role="status" aria-live="polite" data-notifications-announcer></span>
    </div>
</div>
