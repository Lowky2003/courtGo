<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Court;
use App\Models\SessionTemplate;
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

        return redirect()->route('bookings.success', $booking)->with('demo_paid', true);
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
