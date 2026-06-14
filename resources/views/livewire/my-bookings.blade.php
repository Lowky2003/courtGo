<div class="space-y-6 p-6 max-w-3xl mx-auto w-full">
    <flux:heading size="xl">My Bookings</flux:heading>

    @if (session('booking_confirmed'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>Booking confirmed! See it below.</flux:callout.text>
        </flux:callout>
    @endif

    @if ($bookings->isEmpty())
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center space-y-3">
            <flux:text>You have no bookings yet.</flux:text>
            <flux:button variant="primary" :href="route('courts.browse')" wire:navigate>Find a court</flux:button>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($bookings as $booking)
                <div class="flex items-center justify-between rounded-xl border border-zinc-200 dark:border-zinc-700 p-4" wire:key="booking-{{ $booking->id }}">
                    <div>
                        <div class="font-medium">{{ $booking->court->venue->name }} — {{ $booking->court->name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $booking->booking_date->format('D, d M Y') }} ·
                            {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('g:i A') }}
                        </div>
                        <div class="text-sm text-zinc-500">RM {{ number_format($booking->price, 2) }}</div>
                    </div>
                    <div>
                        @if ($booking->status === \App\Enums\BookingStatus::Confirmed)
                            <flux:badge color="green">Confirmed</flux:badge>
                        @elseif ($booking->status === \App\Enums\BookingStatus::Pending)
                            <flux:badge color="amber">Awaiting payment</flux:badge>
                        @else
                            <flux:badge color="zinc">{{ ucfirst($booking->status->value) }}</flux:badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
