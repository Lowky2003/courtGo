@props(['venue'])

@php
    $address = trim(implode(', ', array_filter([$venue->address, $venue->city, $venue->state])));
    $query = urlencode($address);
    $googleUrl = 'https://www.google.com/maps/search/?api=1&query='.$query;
    $wazeUrl = 'https://www.waze.com/ul?q='.$query.'&navigate=yes';
    $base = 'inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm font-medium hover:border-blue-400 dark:border-zinc-700';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
    <a href="{{ $googleUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $base }}">
        <flux:icon name="map-pin" class="size-4" /> Open in Google Maps
    </a>
    <a href="{{ $wazeUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $base }}">
        <flux:icon name="map" class="size-4" /> Open in Waze
    </a>
</div>
