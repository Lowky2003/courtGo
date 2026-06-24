<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingConfirmed;
use Laravel\Cashier\Cashier;

/**
 * Creates the Stripe Checkout session for a booking — a DESTINATION CHARGE that
 * sends the money to the court owner's connected account (0% platform fee, so
 * application_fee_amount is omitted). Also confirms bookings from a paid Checkout
 * session (used by both the Stripe webhook and the post-Checkout return page, so
 * payment confirms even in local dev where the webhook can't reach the app).
 */
class BookingPaymentService
{
    public function checkoutUrl(Booking $booking, string $successUrl, string $cancelUrl): string
    {
        return $this->checkoutUrlForBookings([$booking], $successUrl, $cancelUrl);
    }

    /**
     * One Stripe Checkout session paying for several bookings at once — one line
     * item per slot, a single destination charge to the venue owner. All bookings
     * must belong to the same venue/owner (they always do: one venue page).
     *
     * @param  iterable<int, Booking>  $bookings
     */
    public function checkoutUrlForBookings(iterable $bookings, string $successUrl, string $cancelUrl): string
    {
        $bookings = collect($bookings)->values();
        $bookings->each->loadMissing('court.venue.owner');
        $ownerAccountId = $bookings->first()->court->venue->owner->stripe_connect_account_id;
        $ids = $bookings->pluck('id')->implode(',');

        $lineItems = $bookings->map(fn (Booking $booking) => [
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
        ])->all();

        // Only do a destination charge when the owner has a real Stripe Connect account.
        // The seeder uses 'acct_demo' as a placeholder, which Stripe rejects.
        $paymentIntentData = ['metadata' => ['booking_ids' => $ids]];
        if ($ownerAccountId && $ownerAccountId !== 'acct_demo') {
            $paymentIntentData['transfer_data'] = ['destination' => $ownerAccountId];
        }

        $session = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'payment_intent_data' => $paymentIntentData,
            'metadata' => ['booking_ids' => $ids],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $bookings->each(fn (Booking $booking) => $booking->update(['stripe_checkout_session_id' => $session->id]));

        return $session->url;
    }

    /**
     * Confirm every booking paid for by a completed Checkout session (idempotent),
     * and email the customer for the ones newly confirmed. Safe to call from the
     * webhook and the return page — whichever arrives first wins.
     *
     * @param  array<string, mixed>  $session  a Stripe Checkout Session as an array
     */
    public function confirmPaidSession(array $session): void
    {
        if (($session['payment_status'] ?? 'unpaid') === 'unpaid') {
            return; // async method (FPX/GrabPay) not settled yet
        }

        $raw = $session['metadata']['booking_ids'] ?? ($session['metadata']['booking_id'] ?? '');
        $bookingIds = array_filter(array_map('intval', explode(',', (string) $raw)));

        // Only the slots newly confirmed in THIS call get an email (a Stripe retry
        // or the webhook+return both running won't re-email already-confirmed ones).
        $confirmed = [];
        foreach ($bookingIds as $id) {
            if ($booking = $this->confirm($id, $session)) {
                $confirmed[] = $booking;
            }
        }

        BookingConfirmed::dispatchFor(collect($confirmed));
    }

    /** Release still-pending holds (payment failed or the session expired). */
    public function releaseBookings(array $bookingIds): void
    {
        Booking::query()
            ->whereIn('id', $bookingIds)
            ->where('status', BookingStatus::Pending->value)
            ->update(['status' => BookingStatus::Cancelled->value]);
    }

    /**
     * Confirm one booking (idempotent). Returns the booking only when this call
     * newly confirmed it; null if already confirmed or refunded.
     *
     * @param  array<string, mixed>  $session
     */
    private function confirm(int $bookingId, array $session): ?Booking
    {
        $booking = Booking::query()->whereKey($bookingId)->first();

        if (! $booking || $booking->status === BookingStatus::Confirmed) {
            return null;
        }

        // Edge case: the customer paid right as their hold expired and another active
        // booking grabbed this slot meanwhile. Don't double-book — refund instead.
        $slotTaken = Booking::query()
            ->where('court_id', $booking->court_id)
            ->whereDate('booking_date', $booking->booking_date->toDateString())
            ->where('start_time', $booking->start_time)
            ->whereKeyNot($booking->id)
            ->where(function ($q) {
                $q->where('status', BookingStatus::Confirmed->value)
                    ->orWhere(fn ($p) => $p->where('status', BookingStatus::Pending->value)
                        ->where('hold_expires_at', '>', now()));
            })
            ->exists();

        if ($slotTaken) {
            $this->refund($session);
            $booking->update([
                'status' => BookingStatus::Cancelled,
                'payment_status' => 'refunded',
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'processed_at' => now(),
            ]);

            return null;
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
            'payment_status' => 'paid',
            'stripe_checkout_session_id' => $session['id'] ?? $booking->stripe_checkout_session_id,
            'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            'processed_at' => now(),
        ]);

        return $booking;
    }

    /** Refund a payment for a booking we can't honour (slot was taken). */
    private function refund(array $session): void
    {
        $paymentIntent = $session['payment_intent'] ?? null;

        if (! $paymentIntent || ! config('cashier.secret')) {
            return;
        }

        try {
            Cashier::stripe()->refunds->create([
                'payment_intent' => $paymentIntent,
                'reverse_transfer' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
