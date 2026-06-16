<div class="space-y-8 p-6 max-w-5xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('courts.browse')" wire:navigate icon="arrow-left">Back to search</flux:button>
        <flux:heading size="xl">{{ $venue->name }}</flux:heading>
        <flux:text>📍 {{ $venue->address }}, {{ $venue->city }}, {{ $venue->state }}</flux:text>
        @if ($venue->description)
            <flux:text class="text-zinc-500">{{ $venue->description }}</flux:text>
        @endif
    </div>

    <div class="space-y-3">
        <flux:heading size="lg">Choose a court ({{ $courts->count() }})</flux:heading>

        @if ($courts->isEmpty())
            <flux:text class="text-zinc-400">No courts available to book here right now.</flux:text>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courts as $court)
                    <a href="{{ route('courts.show', $court) }}" wire:navigate wire:key="court-{{ $court->id }}"
                       class="block rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 hover:border-blue-400 transition">
                        <flux:badge size="sm" color="blue">{{ $court->sport }}</flux:badge>
                        <div class="mt-2 font-semibold text-lg">{{ $court->name }}</div>
                        <div class="mt-3">
                            <flux:button size="sm" variant="primary">See times &amp; book</flux:button>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
