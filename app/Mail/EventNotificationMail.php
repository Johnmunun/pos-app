<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailTitle,
        public string $mailBody
    ) {
    }

    public function build(): self
    {
        return $this->subject($this->mailTitle)
            ->view('emails.event-notification');
    }
}

