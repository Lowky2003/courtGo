<div class="space-y-8 p-6 max-w-4xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.courts', $court->venue)" wire:navigate icon="arrow-left">
            Back to courts
        </flux:button>
        <flux:heading size="xl">{{ $court->name }} — Schedule</flux:heading>
        <flux:text>{{ $court->venue->name }} · {{ $court->sport }}</flux:text>
    </div>

    {{-- Add weekly slots: pick a time window + how long each slot is, and we
         split it into back-to-back bookable slots. --}}
    <form wire:submit="addSession" class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Add weekly slots</flux:heading>
        <flux:text>Pick a time window and how long each slot is — we'll create the bookable slots, repeating every week on the day you choose.</flux:text>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:select wire:model.live="day_of_week" label="Day of week">
                @foreach ($days as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live="start_time" type="time" label="From" />
            <flux:input wire:model.live="end_time" type="time" label="Until" />
            <flux:input wire:model.live="hours_per_slot" type="number" step="0.5" min="0.5" max="24" label="Hours per slot" />
            <flux:input wire:model.live="price" type="number" step="0.01" min="0" label="Price per slot (RM)" />
        </div>

        {{-- Live preview of the slots that will be created --}}
        @if ($preview['state'] === 'ok')
            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4">
                <flux:text class="text-sm font-medium">Preview — creates {{ count($preview['slots']) }} slot{{ count($preview['slots']) === 1 ? '' : 's' }}:</flux:text>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($preview['slots'] as $slot)
                        <flux:badge size="sm" color="blue">
                            {{ \Illuminate\Support\Carbon::parse($slot['start'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($slot['end'])->format('g:i A') }}
                        </flux:badge>
                    @endforeach
                </div>
            </div>
        @elseif ($preview['state'] === 'mismatch')
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>That time range doesn't divide evenly into {{ $hours_per_slot }}-hour slots. Adjust the window or the hours per slot.</flux:callout.text>
            </flux:callout>
        @endif

        <flux:button type="submit" variant="primary">Add slots</flux:button>
    </form>

    {{-- Weekly schedule --}}
    <div class="space-y-3">
        <flux:heading size="lg">Weekly schedule</flux:heading>
        <div class="space-y-3">
            @foreach ($days as $value => $label)
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
                    <div class="font-medium mb-2">{{ $label }}</div>
                    @php($daySessions = $sessionsByDay[$value] ?? collect())
                    @if ($daySessions->isEmpty())
                        <flux:text class="text-zinc-400">No sessions.</flux:text>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($daySessions as $session)
                                <div class="flex items-center gap-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-1.5 text-sm" wire:key="session-{{ $session->id }}">
                                    <span>{{ \Illuminate\Support\Carbon::parse($session->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($session->end_time)->format('g:i A') }}</span>
                                    <span class="text-zinc-500">RM {{ number_format($session->price, 2) }}</span>
                                    <button type="button" class="text-red-500 hover:text-red-700" wire:click="deleteSession({{ $session->id }})" wire:confirm="Remove this session?">&times;</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Blocked dates --}}
    <div class="space-y-4">
        <flux:heading size="lg">Closed dates</flux:heading>
        <flux:text>Block specific dates (holidays, maintenance) so they can't be booked.</flux:text>

        <form wire:submit="blockDate" class="flex flex-wrap items-end gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
            <flux:input wire:model="block_date" type="date" label="Date to close" />
            <flux:input wire:model="block_reason" label="Reason (optional)" placeholder="Public holiday" />
            <flux:button type="submit" variant="primary">Block date</flux:button>
        </form>

        @if ($blockedDates->isEmpty())
            <flux:text class="text-zinc-400">No closed dates.</flux:text>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($blockedDates as $blocked)
                    <div class="flex items-center gap-2 rounded-lg bg-amber-100 dark:bg-amber-900/40 px-3 py-1.5 text-sm" wire:key="blocked-{{ $blocked->id }}">
                        <span>{{ $blocked->date->format('d M Y') }}</span>
                        @if ($blocked->reason)
                            <span class="text-zinc-500">({{ $blocked->reason }})</span>
                        @endif
                        <button type="button" class="text-red-500 hover:text-red-700" wire:click="unblockDate({{ $blocked->id }})">&times;</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
