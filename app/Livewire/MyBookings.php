<?php

namespace App\Livewire;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingPaymentService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.customer')]
#[Title('My Bookings')]
class MyBookings extends Component
{
    /** all | confirmed | awaiting | cancelled */
    #[Url]
    public string $filter = 'all';

    /** Pay (or resume payment) for every still-held slot in a grouped row, in one go. */
    public function payGroup(array $ids, BookingPaymentService $payments)
    {
        $bookings = auth()->user()->bookings()
            ->whereIn('id', $ids)
            ->where('status', BookingStatus::Pending->value)
            ->where('hold_expires_at', '>', now())
            ->get();

        if ($bookings->isEmpty()) {
            session()->flash('booking_error', 'These holds have expired.');

            return null;
        }

        if (config('cashier.secret')) {
            return redirect()->away($payments->checkoutUrlForBookings(
                $bookings,
                route('bookings.cart.success'),
                route('bookings.cart.cancel', ['bookings' => $bookings->pluck('id')->implode(',')]),
            ));
        }

        foreach ($bookings as $booking) {
            $booking->update(['status' => BookingStatus::Confirmed, 'payment_status' => 'paid', 'processed_at' => now()]);
        }

        return redirect()->route('bookings.mine')->with('booking_confirmed', true);
    }

    public function render()
    {
        $bookings = auth()->user()->bookings()
            ->with('court.venue')
            ->when($this->filter === 'confirmed', fn ($q) => $q->where('status', BookingStatus::Confirmed->value))
            ->when($this->filter === 'awaiting', fn ($q) => $q->where('status', BookingStatus::Pending->value)
                ->where('hold_expires_at', '>', now()))
            ->when($this->filter === 'cancelled', fn ($q) => $q->where(function ($w) {
                $w->whereIn('status', [BookingStatus::Cancelled->value, BookingStatus::Expired->value])
                    ->orWhere(fn ($p) => $p->where('status', BookingStatus::Pending->value)
                        ->where('hold_expires_at', '<=', now()));
            }))
            ->orderBy('court_id')
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return view('livewire.my-bookings', ['groups' => $this->groupConsecutive($bookings)]);
    }

    /**
     * Merge back-to-back slots on the same court, date and status into one row,
     * so e.g. four 30-minute slots show as a single 10:00 AM – 12:00 PM block.
     *
     * @param  Collection<int, Booking>  $bookings  ordered by court, date, start time
     * @return array<int, array<string, mixed>>
     */
    private function groupConsecutive(Collection $bookings): array
    {
        $groups = [];
        $current = null;

        foreach ($bookings as $booking) {
            $status = $this->displayStatus($booking);

            $continues = $current
                && $current['court']->id === $booking->court_id
                && $current['date']->isSameDay($booking->booking_date)
                && $current['status'] === $status
                && substr((string) $current['end_time'], 0, 5) === substr((string) $booking->start_time, 0, 5);

            if ($continues) {
                $current['end_time'] = $booking->end_time;
                $current['price'] += (float) $booking->price;
                $current['count']++;
                $current['ids'][] = $booking->id;

                if ($booking->hold_expires_at && (! $current['hold_expires_at'] || $booking->hold_expires_at->lt($current['hold_expires_at']))) {
                    $current['hold_expires_at'] = $booking->hold_expires_at; // soonest expiry wins
                }

                continue;
            }

            if ($current) {
                $groups[] = $current;
            }

            $current = [
                'court' => $booking->court,
                'date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'price' => (float) $booking->price,
                'count' => 1,
                'status' => $status,
                'ids' => [$booking->id],
                'hold_expires_at' => $booking->hold_expires_at,
            ];
        }

        if ($current) {
            $groups[] = $current;
        }

        // Newest day first, then latest start time.
        usort($groups, fn ($a, $b) => $b['date']->timestamp <=> $a['date']->timestamp
            ?: strcmp((string) $b['start_time'], (string) $a['start_time']));

        return $groups;
    }

    /** Which display bucket a booking falls in (used both to label and to group). */
    private function displayStatus(Booking $booking): string
    {
        return match (true) {
            $booking->status === BookingStatus::Confirmed => 'confirmed',
            $booking->awaitingPayment() => 'awaiting',
            $booking->holdExpired() || $booking->status === BookingStatus::Expired => 'expired',
            default => 'cancelled',
        };
    }
}
