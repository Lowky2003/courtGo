{{-- One booking row (a group of slots booked together). Expects $group. --}}
@php($accent = match ($group['status']) {
    'confirmed' => 'bg-green-500',
    'awaiting' => 'bg-amber-500',
    default => 'bg-zinc-300 dark:bg-zinc-600',
})
<div class="overflow-hidden rounded-2xl border border-zinc-200 transition hover:border-blue-300 hover:shadow-md dark:border-zinc-700" wire:key="group-{{ $group['ids'][0] }}">
    <div class="flex">
        <div class="w-1.5 shrink-0 {{ $accent }}"></div>
        <div class="flex flex-1 items-center justify-between gap-4 p-4">
            <a href="{{ route('bookings.show', $group['ids'][0]) }}" wire:navigate class="min-w-0 flex-1 space-y-1.5">
                <div class="truncate font-semibold text-zinc-900 dark:text-white">{{ $group['court']->venue->name }} — {{ $group['court']->name }}</div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="calendar-days" class="size-4 shrink-0" /> {{ $group['date']->format('D, d M Y') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 tabular-nums">
                        <flux:icon name="clock" class="size-4 shrink-0" />
                        {{ \Illuminate\Support\Carbon::parse($group['start_time'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($group['end_time'])->format('g:i A') }}
                    </span>
                </div>

                <div class="text-sm font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">
                    RM {{ number_format($group['price'], 2) }}@if ($group['count'] > 1) <span class="font-normal text-zinc-400">· {{ $group['count'] }} slots</span>@endif
                </div>

                @if ($group['status'] === 'awaiting' && $group['hold_expires_at'])
                    <div class="inline-flex items-center gap-1 text-xs font-medium text-amber-600">
                        <flux:icon name="clock" class="size-3.5" />
                        Pay before {{ $group['hold_expires_at']->format('g:i A') }} or the slot is released.
                    </div>
                @endif
            </a>

            <div class="flex shrink-0 flex-col items-end gap-2">
                @if ($group['status'] === 'confirmed')
                    <flux:badge color="green" icon="check-circle">Confirmed</flux:badge>
                @elseif ($group['status'] === 'awaiting')
                    <flux:badge color="amber">Awaiting payment</flux:badge>
                    <flux:button size="sm" variant="primary" wire:click="payGroup({{ json_encode($group['ids']) }})" wire:loading.attr="disabled">
                        Continue payment
                    </flux:button>
                @elseif ($group['status'] === 'expired')
                    <flux:badge color="zinc">Expired</flux:badge>
                @else
                    <flux:badge color="zinc">Cancelled</flux:badge>
                @endif
            </div>
        </div>
    </div>
</div>
