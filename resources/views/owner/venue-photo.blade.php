<x-layouts::app :title="__('Venue photo')">
    <div class="p-6 max-w-lg mx-auto w-full space-y-6">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('owner.venues.index')" wire:navigate>
            Back to venues
        </flux:button>

        <flux:heading size="xl">{{ $venue->name }} — Photo</flux:heading>

        @if ($venue->imageUrl())
            <div class="space-y-1">
                <flux:text class="text-sm text-zinc-500">Current photo</flux:text>
                <img src="{{ $venue->imageUrl() }}" alt="Current photo" class="h-48 w-full rounded-xl object-cover" />
            </div>
        @endif

        {{-- Plain single-request upload (resized in the browser before submit). --}}
        <form method="POST" action="{{ route('owner.venues.photo.update', $venue) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <flux:label>{{ $venue->imageUrl() ? 'Replace photo' : 'Choose a photo' }}</flux:label>
                <x-file-input name="photo" accept="image/*" data-resize-image required class="mt-1" />
                @error('photo')
                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <flux:button type="submit" variant="primary">Save photo</flux:button>
        </form>
    </div>
</x-layouts::app>
