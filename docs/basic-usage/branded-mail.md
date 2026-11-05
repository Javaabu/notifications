---
title: Branded mail
sidebar_position: 6
---

Every `BaseNotification` mail uses a branded layout and theme, set up once in `notifications.mail.branding`:

```php
// config/notifications.php
'mail' => [
    'branded' => true,

    'theme' => 'notifications::mail.theme',

    'branding' => [
        'name' => 'Javaabu',                                 // the header text and the logo's alt text
        'url' => 'https://javaabu.com',                      // where the header links to
        'logo' => 'https://javaabu.com/images/logo.png',     // an absolute URL; null shows the name
        'logo_height' => 48,                                 // pixels
        'colors' => [
            'primary' => '#0f766e',      // buttons and links
            'primary_text' => '#ffffff', // text on buttons
            'background' => '#f4f5f7',   // around the message
            'body' => '#ffffff',         // the message
            'text' => '#1f2937',
            'muted' => '#6b7280',        // the footer
            'success' => '#15803d',      // success() buttons
            'error' => '#b91c1c',        // error() buttons
        ],
        'footer' => 'Javaabu Pvt. Ltd., Malé, Maldives. [Unsubscribe](https://javaabu.com/settings)', // Markdown; null shows "© 2026 Javaabu. All rights reserved."
    ],

    'rtl_locales' => ['dv', 'ar', 'fa', 'he', 'ur'],
],
```

Colours you leave out keep their defaults. Use a hosted logo (PNG or JPG; many mail clients don't show SVG).

# What the mail looks like

The layout is Laravel's notification mail with your logo in the header, your footer, and the theme's colours. The content is the message: the greeting ("Hello!" by default), the lines, the action button (in `primary`, or `success`/`error` with those levels), the salutation ("Regards," and your name by default), and a line with the button's URL for clients that can't show buttons. The plain-text version has the same content.

# Right to left

When the notifiable's language is in `rtl_locales`, the mail has `dir="rtl"` and `lang`, and its text is right-aligned. The greeting, salutation, footer and the button's fallback line are translated (Dhivehi ships with the package). The theme uses physical CSS properties and swaps them for right-to-left mail, because many mail clients ignore logical properties.

# Mailables

Mailables can use the same look: set the theme, and use `<x-mail::branded>` instead of `<x-mail::message>`.

```php
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

class InvoiceMail extends Mailable
{
    public $theme = 'notifications::mail.theme';

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice');
    }
}
```

```blade
{{-- resources/views/mail/invoice.blade.php --}}
<x-mail::branded>
# {{ __('Your invoice') }}

{{ __('Invoice :number is ready.', ['number' => $invoice->number]) }}

<x-mail::button :url="route('invoices.show', $invoice)">
{{ __('View invoice') }}
</x-mail::button>
</x-mail::branded>
```

`<x-mail::branded>` takes:

| Attribute | |
| --- | --- |
| `locale` | The language of its own words and its direction (the current locale by default) |
| `branding` | An array that replaces `notifications.mail.branding` for this mail |

and the slots `header` (replaces the logo row), `subcopy` and `footer`. All of Laravel's mail components (`button`, `panel`, `table`, `subcopy`) work inside it.

# Changing it

- Turn the branding off for notification mail with `'branded' => false`; mail then uses Laravel's own layout.
- Use your own theme with `'theme' => 'mail.my-theme'` (a view). The package's theme is `notifications::mail.theme`, a Blade view that outputs CSS.
- Publish the views (`--tag=notifications-views`) to change `notifications::mail.notification` (the notification content), `notifications::mail.theme`, or the layout in `resources/views/vendor/notifications/mail/components/{html,text}/branded.blade.php`.
- Build the mail yourself in `toMail()` from `Javaabu\Notifications\Mail\BrandedMailMessage`:

```php
use Javaabu\Notifications\Mail\BrandedMailMessage;

public function toMail(object $notifiable): MailMessage
{
    return (new BrandedMailMessage)
        ->locale('dv')
        ->subject(__('Your invoice'))
        ->line(__('Invoice :number is ready.', ['number' => $this->invoice->number]))
        ->attach($this->invoice->pdfPath());
}
```
