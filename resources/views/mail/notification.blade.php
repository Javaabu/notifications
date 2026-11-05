@php
    $locale ??= app()->getLocale();
@endphp
<x-mail::branded :locale="$locale">
{{-- Greeting --}}
@if(! empty($greeting))
# {{ $greeting }}
@elseif($level === 'error')
# {{ __('Whoops!', [], $locale) }}
@else
# {{ __('Hello!', [], $locale) }}
@endif

{{-- Intro Lines --}}
@foreach($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="match ($level) { 'success', 'error' => $level, default => 'primary' }">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if(! empty($salutation))
{{ $salutation }}
@else
{{ __('Regards,', [], $locale) }}<br>
{{ config('notifications.mail.branding.name') ?: config('app.name') }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
{{ __('If you\'re having trouble clicking the ":actionText" button, copy and paste the URL below into your web browser:', ['actionText' => $actionText], $locale) }} <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::branded>
