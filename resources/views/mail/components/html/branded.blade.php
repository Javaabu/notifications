@props([
    'locale' => null,
    'branding' => null,
])
@php
    $locale ??= app()->getLocale();
    $branding = $branding ?? config('notifications.mail.branding', []);
    $name = $branding['name'] ?? config('app.name');
    $dir = in_array($locale, config('notifications.mail.rtl_locales', []), true) ? 'rtl' : 'ltr';
    $footer_text = $branding['footer'] ?? null;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
<title>{{ $name }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body dir="{{ $dir }}" class="{{ $dir }}">

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" dir="{{ $dir }}">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
@isset($header)
{!! $header !!}
@else
<tr>
<td class="header">
<a href="{{ $branding['url'] ?? config('app.url') }}" style="display: inline-block;">
@if(! empty($branding['logo']))
<img src="{{ $branding['logo'] }}" class="logo" alt="{{ $name }}" height="{{ $branding['logo_height'] ?? 48 }}">
@else
{{ $name }}
@endif
</a>
</td>
</tr>
@endisset

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell" dir="{{ $dir }}">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

@isset($subcopy)
<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
{!! Illuminate\Mail\Markdown::parse($subcopy) !!}
</td>
</tr>
</table>
@endisset
</td>
</tr>
</table>
</td>
</tr>

<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" dir="{{ $dir }}">
@isset($footer)
{!! Illuminate\Mail\Markdown::parse($footer) !!}
@else
{!! Illuminate\Mail\Markdown::parse($footer_text ?: '© ' . date('Y') . ' ' . e($name) . '. ' . __('All rights reserved.', [], $locale)) !!}
@endisset
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
