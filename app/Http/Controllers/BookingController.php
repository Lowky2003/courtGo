<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
use App\Notifications\BookingConfirmed;
use App\Services\BookingPaymentService;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    /**
     * Reserve a hold for the chosen session+date, then send the customer to pay.
     */
    public function checkout(
        Request $request,
        Court $court,
        SessionTemplate $session,
        BookingService $bookings,
        BookingPaymentService $payments,
    ) {
        abort_unless($session->court_id === $court->id, 404);

        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->query('date'));

        try {
            $booking = $bookings->reserve($request->user(), $session, $date);
        } catch (SlotUnavailableException $e) {
            return redirect()->route('courts.show', $court)->with('booking_error', $e->getMessage());
        }

        // Real Stripe Checkout when configured…
        if (config('cashier.secret')) {
            $url = $payments->checkoutUrl(
                $booking,
                route('bookings.success', $booking).'?session_id={CHECKOUT_SESSION_ID}',
                route('bookings.cancel', $booking),
            );

            return redirect($url);
        }

        // …otherwise a DEMO confirmation (local only, no Stripe keys) so the flow can be tried.
        $booking->update([
            'status' => BookingStatus::Confirmed,
            'payment_status' => 'paid',
            'processed_at' => now(),
        ]);

        BookingConfirmed::dispatchFor(collect([$booking]));

        return redirect()->route('bookings.success', $booking)->with('demo_paid', true);
    }

    /**
     * Resume payment for an existing pending booking (the "Continue payment" button).
     */
    public function pay(Request $request, Booking $booking, BookingPaymentService $payments)
    {
        abort_unless($booking->customer_id === $request->user()->id, 403);

        if (! $booking->awaitingPayment()) {
            return redirect()->route('bookings.mine')
                ->with('booking_error', 'This booking can no longer be paid (the hold expired).');
        }

        if (config('cashier.secret')) {
            $url = $payments->checkoutUrl(
                $booking,
                route('bookings.success', $booking).'?session_id={CHECKOUT_SESSION_ID}',
                route('bookings.cancel', $booking),
            );

            return redirect($url);
        }

        // Demo mode (no Stripe keys): confirm immediately.
        $booking->update([
            'status' => BookingStatus::Confirmed,
            'payment_status' => 'paid',
            'processed_at' => now(),
        ]);

        BookingConfirmed::dispatchFor(collect([$booking]));

        return redirect()->route('bookings.success', $booking)->with('demo_paid', true);
    }

    /** Return URL after a combined (multi-slot) payment — the webhook confirms the bookings. */
    public function cartSuccess()
    {
        return redirect()->route('bookings.mine')->with('booking_confirmed', true);
    }

    /** Cancel a combined payment: release the still-pending holds it created. */
    public function cartCancel(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('bookings', ''))));

        Booking::query()
            ->whereIn('id', $ids)
            ->where('customer_id', $request->user()->id)
            ->where('status', BookingStatus::Pending->value)
            ->update(['status' => BookingStatus::Cancelled->value]);

        return redirect()->route('bookings.mine')->with('booking_error', 'Payment was cancelled.');
    }

    public function success(Booking $booking)
    {
        abort_unless($booking->customer_id === auth()->id(), 403);

        return redirect()->route('bookings.mine')->with('booking_confirmed', true);
    }

    public function cancel(Booking $booking)
    {
        abort_unless($booking->customer_id === auth()->id(), 403);

        if ($booking->status === BookingStatus::Pending) {
            $booking->update(['status' => BookingStatus::Cancelled]);
        }

        return redirect()->route('courts.show', $booking->court)->with('booking_error', 'Payment was cancelled.');
    }
}
