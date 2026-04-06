<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EcommerceOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, quantity: float, line_total: float}>  $lines
     * @param  array<int, array{name: string, url: string}>  $digitalDownloads
     */
    public function __construct(
        public string $recipientLabel,
        public string $customerName,
        public string $orderNumber,
        public float $totalAmount,
        public string $currency,
        public string $paymentStatus,
        public array $lines,
        public array $digitalDownloads = [],
        public ?string $note = null,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Nouvelle commande '.$this->orderNumber.' — '.config('app.name', 'Boutique'))
            ->view('emails.ecommerce-order-created');
    }
}
