<div class="space-y-6 p-6 max-w-4xl mx-auto w-full">
    <flux:heading size="xl">Approve Venues</flux:heading>
    <flux:text>Review new venues and approve them so they become visible and bookable to customers.</flux:text>

    @if ($venues->isEmpty())
        <flux:text class="text-zinc-400">No venues yet.</flux:text>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 font-medium">Venue</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Courts</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($venues as $venue)
                        <tr wire:key="venue-{{ $venue->id }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.venues.show', $venue) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $venue->name }}</a>
                                <div class="text-zinc-500">{{ $venue->city }}, {{ $venue->state }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $venue->owner->name }}</div>
                                <div class="text-zinc-500">{{ $venue->owner->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $venue->courts_count }}</td>
                            <td class="px-4 py-3">
                                @if ($venue->isApproved())
                                    <flux:badge color="green" size="sm">Approved</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">Pending approval</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" :href="route('admin.venues.show', $venue)" wire:navigate>View</flux:button>
                                    @unless ($venue->isApproved())
                                        <flux:button size="sm" variant="primary" wire:click="approve({{ $venue->id }})" wire:confirm="Approve this venue? Customers will be able to find and book it.">
                                            Approve
                                        </flux:button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
