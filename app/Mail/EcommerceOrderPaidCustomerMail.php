<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail transactionnel : commande payée + liens de téléchargement pour les produits numériques.
 */
class EcommerceOrderPaidCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name: string, sku: ?string, quantity: float, line_total: float}>  $lines
     * @param  array<int, array{name: string, url: string}>  $digitalDownloads
     */
    public function __construct(
        public string $customerName,
        public string $orderNumber,
        public float $totalAmount,
        public string $currency,
        public array $lines,
        public array $digitalDownloads,
        public ?string $successPageUrl = null,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Votre commande '.$this->orderNumber.' est confirmée — '.config('app.name', 'Boutique'))
            ->view('emails.ecommerce-order-paid-customer');
    }
}
