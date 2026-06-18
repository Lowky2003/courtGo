@props(['preview' => ['state' => 'empty', 'slots' => []], 'hours' => ''])

{{-- Shows the slots a window+duration would create, or a "doesn't match" hint.
     (Named window-preview, not slot-preview — an "x-slot…" name collides with
     Blade's <x-slot> directive.) --}}
@if ($preview['state'] === 'ok')
    <flux:text class="text-xs text-zinc-500">
        Creates {{ count($preview['slots']) }} slot{{ count($preview['slots']) === 1 ? '' : 's' }}:
        @foreach ($preview['slots'] as $slot){{ \Illuminate\Support\Carbon::parse($slot['start'])->format('g:i A') }}–{{ \Illuminate\Support\Carbon::parse($slot['end'])->format('g:i A') }}@unless ($loop->last), @endunless @endforeach
    </flux:text>
@elseif ($preview['state'] === 'mismatch')
    <flux:text class="text-xs font-medium text-amber-600 dark:text-amber-500">
        ⚠ This range doesn't divide evenly into {{ $hours }}-hour slots.
    </flux:text>
@endif
