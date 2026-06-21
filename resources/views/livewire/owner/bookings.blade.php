<div class="space-y-6 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Bookings</flux:heading>
        <flux:text>Confirmed bookings across all your venues.</flux:text>
    </div>

    {{-- Filter tabs --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $val => $label)
            <button type="button" wire:click="$set('filter', '{{ $val }}')"
                    class="rounded-full px-4 py-1.5 text-sm font-medium transition {{ $filter === $val ? 'bg-blue-600 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($bookings->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon name="calendar-days" class="size-8 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">No {{ $filter === 'all' ? '' : $filter.' ' }}bookings</flux:heading>
            <flux:text class="text-zinc-500">Confirmed bookings on your courts will appear here.</flux:text>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 divide-y divide-zinc-100 dark:border-zinc-700 dark:divide-zinc-800">
            @foreach ($bookings as $b)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4" wire:key="ob-{{ $b->id }}">
                    <div class="min-w-0 space-y-0.5">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $b->court->venue->name }} — {{ $b->court->name }}</div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon name="calendar-days" class="size-4" /> {{ $b->booking_date->format('D, d M Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 tabular-nums">
                                <flux:icon name="clock" class="size-4" />
                                {{ \Illuminate\Support\Carbon::parse($b->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($b->end_time)->format('g:i A') }}
                            </span>
                            @if ($b->customer)
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon name="user" class="size-4" /> {{ $b->customer->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <span class="tabular-nums text-sm font-semibold text-zinc-700 dark:text-zinc-300">RM {{ number_format($b->price, 2) }}</span>
                </div>
            @endforeach
        </div>

        <div>{{ $bookings->links() }}</div>
    @endif
</div>
