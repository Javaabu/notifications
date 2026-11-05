{{--
    The branded mail theme: Laravel's default theme with the colours from
    notifications.mail.branding.colors, and right-to-left text for RTL locales.
--}}
@php
    $colors = [
        'primary' => '#0f766e',
        'primary_text' => '#ffffff',
        'background' => '#f4f5f7',
        'body' => '#ffffff',
        'text' => '#1f2937',
        'muted' => '#6b7280',
        'success' => '#15803d',
        'error' => '#b91c1c',
        ...config('notifications.mail.branding.colors', []),
    ];
    $c = fn (string $name) => preg_replace('/[^#a-zA-Z0-9(),.% ]/', '', (string) $colors[$name]);
    $locale ??= app()->getLocale();
    $rtl = in_array($locale, config('notifications.mail.rtl_locales', []), true);
    $start = $rtl ? 'right' : 'left';
    $logo_height = (int) config('notifications.mail.branding.logo_height', 48);
@endphp
/* Base */

body,
body *:not(html):not(style):not(br):not(tr):not(code) {
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
    position: relative;
}

body {
    -webkit-text-size-adjust: none;
    background-color: {{ $c('body') }};
    color: {{ $c('text') }};
    height: 100%;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    width: 100% !important;
}

p,
ul,
ol,
blockquote {
    line-height: 1.4;
    text-align: {{ $start }};
}

a {
    color: {{ $c('primary') }};
}

a img {
    border: none;
}

/* Typography */

h1 {
    color: {{ $c('text') }};
    font-size: 18px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $start }};
}

h2 {
    font-size: 16px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $start }};
}

h3 {
    font-size: 14px;
    font-weight: bold;
    margin-top: 0;
    text-align: {{ $start }};
}

p {
    font-size: 16px;
    line-height: 1.5em;
    margin-top: 0;
    text-align: {{ $start }};
}

p.sub {
    font-size: 12px;
}

img {
    max-width: 100%;
}

/* Layout */

.wrapper {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c('background') }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.content {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 0;
    padding: 0;
    width: 100%;
}

/* Header */

.header {
    padding: 25px 0;
    text-align: center;
}

.header a {
    color: {{ $c('text') }};
    font-size: 19px;
    font-weight: bold;
    text-decoration: none;
}

/* Logo */

.logo {
    height: {{ $logo_height }}px;
    margin-top: 15px;
    margin-bottom: 10px;
    max-height: {{ $logo_height }}px;
    width: auto;
}

/* Body */

.body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    background-color: {{ $c('background') }};
    border-bottom: 1px solid {{ $c('background') }};
    border-top: 1px solid {{ $c('background') }};
    margin: 0;
    padding: 0;
    width: 100%;
}

.inner-body {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    background-color: {{ $c('body') }};
    border-color: #e4e4e7;
    border-radius: 4px;
    border-width: 1px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
    padding: 0;
    width: 570px;
}

.inner-body a {
    word-break: break-all;
}

/* Subcopy */

.subcopy {
    border-top: 1px solid #e4e4e7;
    margin-top: 25px;
    padding-top: 25px;
}

.subcopy p {
    font-size: 14px;
}

/* Footer */

.footer {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 570px;
    margin: 0 auto;
    padding: 0;
    text-align: center;
    width: 570px;
}

.footer p {
    color: {{ $c('muted') }};
    font-size: 12px;
    text-align: center;
}

.footer a {
    color: {{ $c('muted') }};
    text-decoration: underline;
}

/* Tables */

.table table {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    width: 100%;
}

.table th {
    border-bottom: 1px solid #e4e4e7;
    margin: 0;
    padding-bottom: 8px;
}

.table td {
    color: {{ $c('text') }};
    font-size: 15px;
    line-height: 18px;
    margin: 0;
    padding: 10px 0;
}

.content-cell {
    max-width: 100vw;
    padding: 32px;
}

/* Buttons */

.action {
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
    margin: 30px auto;
    padding: 0;
    text-align: center;
    width: 100%;
    float: unset;
}

.button {
    -webkit-text-size-adjust: none;
    border-radius: 4px;
    color: {{ $c('primary_text') }};
    display: inline-block;
    overflow: hidden;
    text-decoration: none;
}

.button-blue,
.button-primary {
    background-color: {{ $c('primary') }};
    border-bottom: 8px solid {{ $c('primary') }};
    border-left: 18px solid {{ $c('primary') }};
    border-right: 18px solid {{ $c('primary') }};
    border-top: 8px solid {{ $c('primary') }};
}

.button-green,
.button-success {
    background-color: {{ $c('success') }};
    border-bottom: 8px solid {{ $c('success') }};
    border-left: 18px solid {{ $c('success') }};
    border-right: 18px solid {{ $c('success') }};
    border-top: 8px solid {{ $c('success') }};
}

.button-red,
.button-error {
    background-color: {{ $c('error') }};
    border-bottom: 8px solid {{ $c('error') }};
    border-left: 18px solid {{ $c('error') }};
    border-right: 18px solid {{ $c('error') }};
    border-top: 8px solid {{ $c('error') }};
}

/* Panels */

.panel {
    border-left: {{ $c('primary') }} solid 4px;
    margin: 21px 0;
}

.panel-content {
    background-color: {{ $c('background') }};
    color: {{ $c('text') }};
    padding: 16px;
}

.panel-content p {
    color: {{ $c('text') }};
}

.panel-item {
    padding: 0;
}

.panel-item p:last-of-type {
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Utilities */

.break-all {
    word-break: break-all;
}

@if($rtl)
/* Right to left */

body,
.content-cell,
.panel-content,
.table td,
.table th {
    direction: rtl;
}

p,
ul,
ol,
blockquote,
h1,
h2,
h3 {
    direction: rtl;
    text-align: right;
}

.panel {
    border-left: 0;
    border-right: {{ $c('primary') }} solid 4px;
}
@endif
