<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

/**
 * Handles Stripe Checkout webhooks for BOOKING payments (separate from Cashier's
 * subscription webhook and the Connect webhook). Confirms the booking once paid.
 */
class BookingWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload = $this->verifiedPayload($request);

        if ($payload === null) {
            return response('Invalid signature', 400);
        }

        $type = $payload['type'] ?? null;
        $session = $payload['data']['object'] ?? [];
        $bookingId = $session['metadata']['booking_id'] ?? null;

        if (! $bookingId) {
            return response('No booking', 200);
        }

        // Card pays immediately (completed); FPX/GrabPay settle later (async_payment_succeeded).
        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            if (($session['payment_status'] ?? 'unpaid') === 'unpaid') {
                return response('Awaiting payment', 200); // async not settled yet
            }
            $this->confirm((int) $bookingId, $session);
        } elseif (in_array($type, ['checkout.session.async_payment_failed', 'checkout.session.expired'], true)) {
            $this->release((int) $bookingId);
        }

        return response('Webhook handled', 200);
    }

    /** Confirm the booking (idempotent — safe to call more than once). */
    private function confirm(int $bookingId, array $session): void
    {
        $booking = Booking::query()->whereKey($bookingId)->first();

        if (! $booking || $booking->status === BookingStatus::Confirmed) {
            return;
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'payment_status' => 'paid',
            'stripe_checkout_session_id' => $session['id'] ?? $booking->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            'processed_at' => now(),
        ]);
    }

    /** Release a still-pending hold (payment failed or the session expired). */
    private function release(int $bookingId): void
    {
        Booking::query()
            ->whereKey($bookingId)
            ->where('status', BookingStatus::Pending->value)
            ->update(['status' => BookingStatus::Cancelled->value]);
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
