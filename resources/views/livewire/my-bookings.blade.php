<div class="space-y-6 p-6 max-w-3xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>

    <div class="flex items-center justify-between gap-4 flex-wrap">
        <flux:heading size="xl">My Bookings</flux:heading>
        <flux:select wire:model.live="filter" class="max-w-xs">
            <flux:select.option value="all">All bookings</flux:select.option>
            <flux:select.option value="confirmed">Confirmed</flux:select.option>
            <flux:select.option value="awaiting">Awaiting payment</flux:select.option>
            <flux:select.option value="cancelled">Cancelled / expired</flux:select.option>
        </flux:select>
    </div>

    @if (session('booking_confirmed'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>Booking confirmed! See it below.</flux:callout.text>
        </flux:callout>
    @endif
    @if (session('booking_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('booking_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($bookings->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center space-y-3">
            <flux:text>No bookings here.</flux:text>
            <flux:button variant="primary" :href="route('courts.browse')" wire:navigate>Find a court</flux:button>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($bookings as $booking)
                <div class="flex items-center justify-between gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4" wire:key="booking-{{ $booking->id }}">
                    <div>
                        <div class="font-medium">{{ $booking->court->venue->name }} — {{ $booking->court->name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $booking->booking_date->format('D, d M Y') }} ·
                            {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('g:i A') }}
                        </div>
                        <div class="text-sm text-zinc-500">RM {{ number_format($booking->price, 2) }}</div>
                        @if ($booking->awaitingPayment())
                            <div class="text-xs text-amber-600 mt-1">
                                Pay before {{ $booking->hold_expires_at->format('g:i A') }} or the slot is released.
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                            <flux:badge color="green">Confirmed</flux:badge>
                        @elseif ($booking->awaitingPayment())
                            <flux:badge color="amber">Awaiting payment</flux:badge>
                            <flux:button size="sm" variant="primary" href="{{ route('bookings.pay', $booking) }}">
                                Continue payment
                            </flux:button>
                        @elseif ($booking->holdExpired() || $booking->status === \App\Enums\BookingStatus::Expired)
                            <flux:badge color="zinc">Expired</flux:badge>
                        @else
                            <flux:badge color="zinc">Cancelled</flux:badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
