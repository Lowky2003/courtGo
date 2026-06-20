<?php

namespace App\Notifications;

use App\Enums\BookingStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Emails the customer one confirmation for a group of slots booked together.
 */
class BookingConfirmed extends Notification
{
    use Queueable;

    /** @param  Collection<int, \App\Models\Booking>  $bookings  one confirmed group, all the same customer */
    public function __construct(public Collection $bookings)
    {
    }

    /**
     * Send one confirmation email for a freshly-confirmed group of bookings.
     * Safe to call from any confirm path: it ignores anything not Confirmed and
     * never throws (a mail failure must not break a booking or a Stripe webhook).
     *
     * @param  iterable<int, \App\Models\Booking>  $bookings
     */
    public static function dispatchFor(iterable $bookings): void
    {
        $bookings = collect($bookings)
            ->filter(fn ($b) => $b->status === BookingStatus::Confirmed)
            ->values();

        if ($bookings->isEmpty()) {
            return;
        }

        // each->loadMissing (not ->loadMissing): this is a base Collection, and
        // loadMissing only exists on Eloquent collections — so load per model.
        $bookings->each->loadMissing('court.venue', 'customer');
        $customer = $bookings->first()->customer;

        if (! $customer) {
            return;
        }

        try {
            $customer->notify(new self($bookings));
        } catch (\Throwable $e) {
            report($e); // don't let a mail problem break the booking flow
        }
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $venue = $this->bookings->first()->court->venue;
        $date = $this->bookings->first()->booking_date->format('l, d M Y');
        $total = (float) $this->bookings->sum('price');

        $mail = (new MailMessage)
            ->subject('Your CourtGo booking is confirmed')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your booking at '.$venue->name.' is confirmed.')
            ->line('Date: '.$date);

        foreach ($this->bookings as $booking) {
            $mail->line(
                $booking->court->name.': '
                .Carbon::parse($booking->start_time)->format('g:i A').'–'
                .Carbon::parse($booking->end_time)->format('g:i A')
                .'  ·  RM '.number_format((float) $booking->price, 2)
            );
        }

        return $mail
            ->line('Total paid: RM '.number_format($total, 2))
            ->action('View my bookings', route('bookings.mine'))
            ->line('Thanks for booking with CourtGo!');
    }
}
