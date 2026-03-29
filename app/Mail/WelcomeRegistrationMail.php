<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $companyName = null,
        public ?string $storeStartMode = null,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Bienvenue sur '.config('app.name', 'OmniPOS'))
            ->view('emails.welcome-registration');
    }
}

