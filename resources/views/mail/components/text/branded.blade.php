@props([
    'locale' => null,
    'branding' => null,
])
@php
    $locale ??= app()->getLocale();
    $branding = $branding ?? config('notifications.mail.branding', []);
    $name = $branding['name'] ?? config('app.name');
    $footer_text = $branding['footer'] ?? null;
@endphp
{{ $name }}: {{ $branding['url'] ?? config('app.url') }}

{!! strip_tags($slot) !!}
@isset($subcopy)

{!! strip_tags($subcopy) !!}
@endisset

{!! strip_tags($footer ?? ($footer_text ?: '© ' . date('Y') . ' ' . $name . '. ' . __('All rights reserved.', [], $locale))) !!}
