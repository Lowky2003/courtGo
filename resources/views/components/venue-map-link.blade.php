@props(['venue', 'label' => null])

@php
    $address = trim(implode(', ', array_filter([$venue->address, $venue->city, $venue->state])));
    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode($address);
@endphp

<a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"
   {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400']) }}>
    📍 <span>{{ $label ?? $address }}</span>
</a>
