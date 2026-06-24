<div class="space-y-6 p-6 max-w-5xl mx-auto w-full">
    <div class="space-y-1">
        <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Bookings</flux:heading>
        <flux:text>See which courts are booked at any time — pick a day and tap a time to check what's in use.</flux:text>
    </div>

    @php($venues = $this->venues)

    @if ($venues->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon name="calendar-days" class="size-8 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">No venues yet</flux:heading>
            <flux:text class="text-zinc-500">Add a venue and courts to see your booking calendar.</flux:text>
        </div>
    @else
        {{-- Controls: venue + day --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            @if ($venues->count() > 1)
                <flux:select wire:model.live="venueId" label="Venue" class="max-w-xs">
                    @foreach ($venues as $v)
                        <flux:select.option value="{{ $v->id }}">{{ $v->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <div></div>
            @endif

            <div class="flex items-end gap-2">
                <flux:button size="sm" variant="ghost" icon="chevron-left" wire:click="changeDay(-1)" aria-label="Previous day" />
                <flux:input type="date" wire:model.live="date" label="Date" class="w-44" />
                <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="changeDay(1)" aria-label="Next day" />
                <flux:button size="sm" variant="ghost" wire:click="goToday">Today</flux:button>
            </div>
        </div>

        @if (! $grid)
            <flux:text class="text-zinc-500">Choose a venue to see its courts.</flux:text>
        @else
            @php($isToday = $grid['date']->isToday())
            @php($nowHour = (int) now()->format('G'))
            @php($selectedRow = collect($grid['rows'])->firstWhere('hour', $selectedHour))

            {{-- Selected-hour summary: booked vs free, with who's playing. --}}
            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                @if ($selectedRow)
                    @php($bookedCourts = $grid['courts']->whereIn('id', $selectedRow['bookedCourtIds']))
                    @php($freeCourts = $grid['courts']->whereNotIn('id', $selectedRow['bookedCourtIds']))

                    <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                        @if ($isToday && $selectedRow['hour'] === $nowHour)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span> Now
                            </span>
                        @endif
                        <span>{{ $selectedRow['label'] }}</span>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Booked ({{ $bookedCourts->count() }})</div>
                            @if ($bookedCourts->isEmpty())
                                <flux:text class="mt-1 text-zinc-400">No courts booked this hour.</flux:text>
                            @else
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($bookedCourts as $court)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-sm font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">{{ $court->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-400">Free ({{ $freeCourts->count() }})</div>
                            @if ($freeCourts->isEmpty())
                                <flux:text class="mt-1 text-zinc-400">Every court is in use.</flux:text>
                            @else
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($freeCourts as $court)
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-sm text-zinc-500 dark:bg-zinc-700/60 dark:text-zinc-300">{{ $court->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($selectedBookings->isNotEmpty())
                        <div class="mt-3 space-y-1 border-t border-zinc-200 pt-3 text-sm dark:border-zinc-700">
                            @foreach ($selectedBookings as $b)
                                <div class="flex flex-wrap items-center gap-x-2 text-zinc-600 dark:text-zinc-300" wire:key="sel-{{ $b->id }}">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $b->court->name }}</span>
                                    <span class="tabular-nums">{{ \Illuminate\Support\Carbon::parse($b->start_time)->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($b->end_time)->format('g:i A') }}</span>
                                    @if ($b->customer)<span class="text-zinc-400">· {{ $b->customer->name }}</span>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <flux:text class="text-zinc-500">Tap a time row below to see which courts are booked then.</flux:text>
                @endif
            </div>

            @if (! $grid['hasBookings'])
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <flux:text class="text-zinc-500">No bookings on {{ $grid['date']->format('D, d M Y') }} — every court is free.</flux:text>
                </div>
            @endif

            {{-- The calendar: hours down the side, courts across the top. --}}
            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-700">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-left font-medium text-zinc-500 dark:bg-zinc-800/50">Time</th>
                            @foreach ($grid['courts'] as $court)
                                <th class="min-w-24 whitespace-nowrap px-3 py-2 text-center font-medium text-zinc-700 dark:text-zinc-300">{{ $court->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grid['rows'] as $row)
                            @php($isSelected = $row['hour'] === $selectedHour)
                            @php($isNow = $isToday && $row['hour'] === $nowHour)
                            <tr wire:key="row-{{ $row['hour'] }}" wire:click="selectHour({{ $row['hour'] }})"
                                class="cursor-pointer border-t border-zinc-100 transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40 {{ $isSelected ? 'bg-blue-50 dark:bg-blue-500/10' : '' }}">
                                <td class="sticky left-0 z-10 whitespace-nowrap px-3 py-2 font-medium tabular-nums text-zinc-600 dark:text-zinc-300 {{ $isSelected ? 'bg-blue-50 dark:bg-blue-950/50' : 'bg-white dark:bg-zinc-900' }}">
                                    <span class="inline-flex items-center gap-1.5">
                                        {{ $row['label'] }}
                                        @if ($isNow)<span class="size-1.5 rounded-full bg-emerald-500" title="Now"></span>@endif
                                    </span>
                                </td>
                                @foreach ($grid['courts'] as $court)
                                    @php($courtBookings = $row['byCourt'][$court->id] ?? null)
                                    <td class="px-2 py-1.5 text-center">
                                        @if ($courtBookings)
                                            @php($who = optional($courtBookings->first()->customer)->name)
                                            <span class="block truncate rounded-md bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300"
                                                  title="Booked{{ $who ? ' — '.$who : '' }}">
                                                {{ $who ? \Illuminate\Support\Str::of($who)->before(' ') : 'Booked' }}
                                            </span>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>
