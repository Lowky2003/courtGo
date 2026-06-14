<?php

namespace App\Services;

use App\Models\Booking;
use Laravel\Cashier\Cashier;

/**
 * Creates the Stripe Checkout session for a booking — a DESTINATION CHARGE that
 * sends the money to the court owner's connected account (0% platform fee, so
 * application_fee_amount is omitted). Calls Stripe, so it's exercised manually.
 */
class BookingPaymentService
{
    public function checkoutUrl(Booking $booking, string $successUrl, string $cancelUrl): string
    {
        $booking->loadMissing('court.venue.owner');
        $ownerAccountId = $booking->court->venue->owner->stripe_connect_account_id;

        $session = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr',
                    'unit_amount' => (int) round(((float) $booking->price) * 100), // sen
                    'product_data' => [
                        'name' => $booking->court->venue->name.' — '.$booking->court->name,
                        'description' => $booking->booking_date->format('D, d M Y')
                            .' '.substr((string) $booking->start_time, 0, 5)
                            .'–'.substr((string) $booking->end_time, 0, 5),
                    ],
                ],
                'quantity' => 1,
            ]],
            'payment_intent_data' => [
                // Destination charge to the owner; 0% platform fee → no application_fee_amount.
                'transfer_data' => ['destination' => $ownerAccountId],
                'metadata' => ['booking_id' => (string) $booking->id],
            ],
            'metadata' => ['booking_id' => (string) $booking->id],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $booking->update(['stripe_checkout_session_id' => $session->id]);

        return $session->url;
    }
}
