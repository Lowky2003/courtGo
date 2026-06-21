<div class="space-y-6 p-6 max-w-6xl mx-auto w-full">
    <flux:button size="sm" variant="ghost" :href="route('home')" wire:navigate icon="arrow-left">Back to homepage</flux:button>

    <div class="space-y-1">
        <flux:heading size="xl" class="!text-2xl !font-bold tracking-tight">Find a Court</flux:heading>
        <flux:text>Browse places you can book, then pick a court inside.</flux:text>
    </div>

    {{-- Search panel --}}
    <div class="rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <flux:input wire:model.live.debounce.300ms="name" label="Place name" icon="magnifying-glass" placeholder="e.g. Sunway Hall" autocomplete="new-password" data-no-autofill />
            <x-searchable-select label="Sport" placeholder="Any sport" :options="$sports" wire-model="sport" :live="true" :value="$sport" />
            <x-searchable-select label="State" placeholder="Any state" :options="$states" wire-model="state" :live="true" :value="$state" />
            <flux:input wire:model.live.debounce.300ms="city" label="City" placeholder="e.g. Subang Jaya" autocomplete="new-password" data-no-autofill />
            <flux:input type="date" wire:model.live="date" label="Date" :min="now()->toDateString()" />
        </div>
    </div>

    {{-- Venues (places) --}}
    @if ($venues->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-zinc-300 p-12 text-center dark:border-zinc-700">
            <flux:icon name="map-pin" class="size-8 text-zinc-300 dark:text-zinc-600" />
            <flux:heading size="lg">No places found</flux:heading>
            <flux:text class="text-zinc-500">Try a different sport, city, or date.</flux:text>
        </div>
    @else
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <flux:text class="font-medium">{{ $venues->total() }} {{ \Illuminate\Support\Str::plural('place', $venues->total()) }}</flux:text>
            <flux:text class="text-sm text-zinc-500">Availability for <strong class="text-zinc-700 dark:text-zinc-300">{{ $displayDate->isoFormat('ddd, D MMM') }}</strong></flux:text>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($venues as $venue)
                @php($summary = $summaries[$venue->id])
                @php($amenities = $venue->amenityLabels())
                <a href="{{ route('venues.show', $sport !== '' ? ['venue' => $venue, 'sport' => $sport] : ['venue' => $venue]) }}"
                   wire:navigate wire:key="venue-{{ $venue->id }}"
                   class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-blue-600">
                    {{-- Cover with status / price overlays --}}
                    <div class="relative overflow-hidden">
                        @if ($venue->imageUrl())
                            <img src="{{ $venue->imageUrl() }}" alt="{{ $venue->name }}" loading="lazy"
                                 class="h-44 w-full object-cover transition duration-300 group-hover:scale-105" />
                        @else
                            <div class="flex h-44 w-full items-center justify-center bg-zinc-100 text-zinc-300 dark:bg-zinc-800 dark:text-zinc-600">
                                <flux:icon name="photo" class="size-8" />
                            </div>
                        @endif

                        @if ($venue->opening_hours && $venue->isOpenNow())
                            <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-xs font-medium text-green-700 shadow-sm backdrop-blur dark:bg-zinc-900/90 dark:text-green-400">
                                <span class="size-1.5 rounded-full bg-green-500"></span> Open now
                            </span>
                        @endif

                        @if ($summary['price_from'] !== null)
                            <span class="absolute right-3 top-3 rounded-full bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">from RM{{ number_format($summary['price_from'], 0) }}</span>
                        @endif
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="space-y-1">
                            <h3 class="text-lg font-semibold leading-tight text-zinc-900 transition group-hover:text-blue-700 dark:text-white dark:group-hover:text-blue-300">{{ $venue->name }}</h3>
                            <p class="flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="map-pin" class="size-4 shrink-0" /> {{ $venue->city }}, {{ $venue->state }}
                            </p>
                        </div>

                        {{-- Sports offered --}}
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($venue->courts->pluck('sport')->unique() as $courtSport)
                                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">{{ $courtSport }}</span>
                            @endforeach
                        </div>

                        {{-- Amenities preview --}}
                        @if (! empty($amenities))
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @foreach (array_slice($amenities, 0, 4) as $a)
                                    <span class="inline-flex items-center gap-1"><flux:icon :name="$a['icon']" class="size-3.5" /> {{ $a['label'] }}</span>
                                @endforeach
                                @if (count($amenities) > 4)
                                    <span class="text-zinc-400">+{{ count($amenities) - 4 }} more</span>
                                @endif
                            </div>
                        @endif

                        {{-- Footer: availability + view affordance --}}
                        <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            @if ($summary['available'] > 0)
                                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
                                    <span class="size-1.5 rounded-full bg-green-500"></span> {{ $summary['available'] }} session(s) available
                                </span>
                            @else
                                <span class="text-sm text-zinc-400">Fully booked</span>
                            @endif

                            <span class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 dark:text-blue-400">
                                View <flux:icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div>{{ $venues->links() }}</div>
    @endif
</div>
