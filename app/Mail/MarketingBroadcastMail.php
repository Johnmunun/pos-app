<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->html($this->htmlBody);
    }
}
