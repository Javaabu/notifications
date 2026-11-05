<?php

namespace Javaabu\Notifications\Tests\TestSupport\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceMail extends Mailable
{
    public $theme = 'notifications::mail.theme';

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your invoice');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invoice');
    }
}
