<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Waarschuwing dat de actuele tariefset van één of meer verstrekkers verouderd is (> 36 uur). */
final class StaleRatesAlert extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @param list<array{naam: string, leeftijdUren: float}> $verouderd */
    public function __construct(public readonly array $verouderd)
    {
    }

    public function envelope(): Envelope
    {
        $aantal = count($this->verouderd);

        return new Envelope(
            subject: $aantal === 1
                ? 'Verouderde tarieven bij 1 verstrekker'
                : "Verouderde tarieven bij $aantal verstrekkers",
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.stale-rates-alert', with: ['verouderd' => $this->verouderd]);
    }
}
