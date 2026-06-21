<?php

namespace App\Http\Controllers;

use App\Services\BookingPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

/**
 * Handles Stripe Checkout webhooks for BOOKING payments (separate from Cashier's
 * subscription webhook and the Connect webhook). Confirms the booking once paid.
 */
class BookingWebhookController extends Controller
{
    public function handle(Request $request, BookingPaymentService $payments): Response
    {
        $payload = $this->verifiedPayload($request);

        if ($payload === null) {
            return response('Invalid signature', 400);
        }

        $type = $payload['type'] ?? null;
        $session = $payload['data']['object'] ?? [];

        // One session may pay for several bookings (booking_ids). Older single-slot
        // sessions used booking_id — accept both.
        $raw = $session['metadata']['booking_ids'] ?? ($session['metadata']['booking_id'] ?? '');
        $bookingIds = array_filter(array_map('intval', explode(',', (string) $raw)));

        if (empty($bookingIds)) {
            return response('No booking', 200);
        }

        // Card pays immediately (completed); FPX/GrabPay settle later (async_payment_succeeded).
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $payments->confirmPaidSession($session);
        } elseif (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
            $payments->releaseBookings($bookingIds);
        }

        return response('Webhook handled', 200);
    }

    /** @return array<string, mixed>|null  null means invalid signature */
    private function verifiedPayload(Request $request): ?array
    {
        $secret = config('services.stripe.booking_webhook_secret');

        if (! $secret) {
            return $request->json()->all();
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (\Throwable $e) {
            return null;
        }

        return $event->toArray();
    }
}
