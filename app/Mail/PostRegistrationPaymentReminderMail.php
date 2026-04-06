<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PostRegistrationPaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $companyName = null,
        public ?string $paymentUrl = null,
    ) {
    }

    public function build(): self
    {
        $paymentUrl = $this->paymentUrl ?? route('billing.onboarding.payment', absolute: true);

        return $this->subject('Étape suivante : choisir votre formule sur '.config('app.name', 'OmniPOS'))
            ->view('emails.post-registration-payment-reminder', [
                'user' => $this->user,
                'companyName' => $this->companyName,
                'paymentUrl' => $paymentUrl,
            ]);
    }
}
