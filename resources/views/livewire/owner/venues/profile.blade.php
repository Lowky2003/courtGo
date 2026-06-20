<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:button size="sm" variant="ghost" :href="route('owner.venues.courts', $venue)" wire:navigate icon="arrow-left">
            Back to courts
        </flux:button>
        <flux:heading size="xl">{{ $venue->name }} — Profile</flux:heading>
        <flux:text>{{ $venue->city }}, {{ $venue->state }}</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Amenities (Livewire) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Amenities</flux:heading>
        <flux:text class="text-sm text-zinc-500">Tick everything your venue offers.</flux:text>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach ($allAmenities as $key => $meta)
                <flux:checkbox wire:model="amenities" value="{{ $key }}" label="{{ $meta['label'] }}" />
            @endforeach
        </div>
        @error('amenities.*') <flux:text class="text-sm text-red-600">{{ $message }}</flux:text> @enderror

        <flux:button variant="primary" wire:click="save">Save amenities</flux:button>
    </div>

    {{-- Cover photo (plain HTTP form → existing controller) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Cover photo</flux:heading>
        @if ($venue->imageUrl())
            <img src="{{ $venue->imageUrl() }}" alt="" class="h-40 w-full rounded-lg object-cover" />
        @endif
        <form method="POST" action="{{ route('owner.venues.photo.update', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="photo" accept="image/*" required class="text-sm" />
            <flux:button type="submit" variant="primary" size="sm">Upload cover</flux:button>
        </form>
    </div>

    {{-- Gallery (plain HTTP forms → VenueMediaController) --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">Photo gallery</flux:heading>
        <flux:text class="text-sm text-zinc-500">Up to 12 photos of your courts and facility.</flux:text>

        @if ($photos->isNotEmpty())
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                @foreach ($photos as $photo)
                    <div class="relative" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ $photo->imageUrl() }}" alt="" class="h-24 w-full rounded-lg object-cover" />
                        <form method="POST" action="{{ route('owner.venues.media.photos.destroy', [$venue, $photo]) }}" class="absolute right-1 top-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full bg-black/60 px-2 text-xs text-white hover:bg-black/80">&times;</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($photos->count() < 12)
            <form method="POST" action="{{ route('owner.venues.media.photos.store', $venue) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="photo" accept="image/*" required class="text-sm" />
                <flux:button type="submit" variant="primary" size="sm">Add photo</flux:button>
            </form>
        @else
            <flux:text class="text-sm text-zinc-400">Gallery is full (12 photos).</flux:text>
        @endif
    </div>
</div>
