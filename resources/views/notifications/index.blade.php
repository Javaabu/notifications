@extends(config('notifications.layout', 'layouts.app'))

@section(config('notifications.layout_section', 'content'))
    @manifestRequire('notifications::notifications')

    <div class="flex flex-col gap-6" data-notifications-page>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold">{{ $title }}</h1>

            @if($is_own && $notifications->isNotEmpty())
                <x-forms::form method="POST" :action="$urls['read_all']">
                    <x-forms::submit color="secondary" size="sm" :animate="false" start-icon="check">
                        {{ __('Mark all as read') }}
                    </x-forms::submit>
                </x-forms::form>
            @endif
        </div>

        @if(session('success'))
            <x-forms::alert type="success">{{ session('success') }}</x-forms::alert>
        @endif

        <x-forms::form method="GET" :action="$urls['index']" class="flex flex-wrap items-end gap-4" data-notifications-filter>
            @unless($is_own)
                <input type="hidden" name="notifiable_type" value="{{ $notifiable->getMorphClass() }}">
                <input type="hidden" name="notifiable_id" value="{{ $notifiable->getKey() }}">
            @endunless
            <x-forms::select
                name="status"
                :label="__('Status')"
                :placeholder="__('All notifications')"
                show-placeholder
                :options="collect($statuses)->pluck('label', 'value')->all()"
                :default="$filters['status']" />
            @if(count($types) > 1)
                <x-forms::select
                    name="type"
                    :label="__('Type')"
                    :placeholder="__('All types')"
                    show-placeholder
                    :options="collect($types)->pluck('label', 'value')->all()"
                    :default="$filters['type']" />
            @endif
            <x-forms::submit color="secondary" :animate="false">{{ __('Filter') }}</x-forms::submit>
        </x-forms::form>

        @if($notifications->isEmpty())
            <x-forms::no-items
                icon="circle-check"
                :show-create="false"
                :title="__('No notifications')"
                :message="__('You\'re all caught up.')" />
        @else
            <ul class="divide-y divide-border overflow-hidden rounded-lg border bg-card" data-slot="notifications-list">
                @foreach($notifications as $notification)
                    @include('notifications::notifications.partials.item', [
                        'notification' => $notification,
                        'routes' => $routes,
                        'actions' => true,
                    ])
                @endforeach
            </ul>
        @endif

        @if($notifications->hasPages())
            <x-forms::pagination :paginator="$notifications" />
        @endif
    </div>
@endsection
