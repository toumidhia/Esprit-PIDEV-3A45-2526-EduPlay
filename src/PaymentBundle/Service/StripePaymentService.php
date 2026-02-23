<?php

namespace App\PaymentBundle\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session as CheckoutSession;

class StripePaymentService
{
    public function __construct(private string $stripeSecretKey)
    {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => (int) round($amount * 100), // Stripe expects the amount in cents
            'currency' => $currency,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a Stripe Checkout Session and return it
     * @return CheckoutSession
     */
    public function createCheckoutSession(float $amount, string $currency, string $successUrl, string $cancelUrl, array $metadata = []): CheckoutSession
    {
        return CheckoutSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => ($metadata['description'] ?? 'Commande')],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        return CheckoutSession::retrieve($sessionId);
    }
}