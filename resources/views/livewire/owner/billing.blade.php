<div class="space-y-8 p-6 max-w-3xl mx-auto w-full">
    <div class="space-y-1">
        <flux:heading size="xl">Billing &amp; Payouts</flux:heading>
        <flux:text>Subscribe each venue and connect your bank so your courts can go live for booking.</flux:text>
    </div>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif
    @if (session('stripe_error'))
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ session('stripe_error') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Step 1: a subscription per venue --}}
    <div class="space-y-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <flux:heading size="lg">1. Venue subscriptions</flux:heading>
        <flux:text>Each venue needs its own monthly plan to be listed on CourtGo.</flux:text>

        @if ($venues->isEmpty())
            <flux:text class="text-zinc-400">
                Add a venue first in <a class="underline" href="{{ route('owner.venues.index') }}" wire:navigate>My Venues</a>.
            </flux:text>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($venues as $venue)
                    @php($subscribed = auth()->user()->subscribed($venue->subscriptionType()))
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="vsub-{{ $venue->id }}">
                        <div>
                            <div class="font-medium">{{ $venue->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $venue->city }}, {{ $venue->state }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($subscribed)
                                <flux:badge color="green" size="sm">Subscribed</flux:badge>
                                <flux:button size="sm" variant="ghost" :href="route('owner.billing.portal')" wire:navigate>Manage</flux:button>
                            @else
                                <flux:badge color="zinc" size="sm">Not subscribed</flux:badge>
                                <flux:button size="sm" variant="primary" href="{{ route('owner.billing.subscribe', $venue) }}">Subscribe</flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Step 2: connect your bank once (payouts for all venues) --}}
    <div class="space-y-4 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">2. Connect your bank (payouts)</flux:heading>
            @if ($onboarded)
                <flux:badge color="green">Connected</flux:badge>
            @else
                <flux:badge color="zinc">Not connected</flux:badge>
            @endif
        </div>
        <flux:text>Connect once — booking money for all your venues goes to your bank. Stripe handles the verification; we never see your bank details.</flux:text>

        <form wire:submit="saveBrn" class="space-y-3">
            <flux:input wire:model="business_registration_number" label="Business Registration Number (BRN)" placeholder="e.g. 202301234567" description="Required by Stripe for FPX (Malaysian online banking) payouts." />
            <flux:button type="submit" variant="ghost" size="sm">Save BRN</flux:button>
            @if (session('brn_saved'))
                <flux:text class="text-green-600">Saved.</flux:text>
            @endif
        </form>

        @if ($onboarded)
            <flux:button variant="ghost" href="{{ route('owner.connect.redirect') }}">Update bank details</flux:button>
        @else
            <flux:button variant="primary" href="{{ route('owner.connect.redirect') }}">Connect bank</flux:button>
        @endif
    </div>

    <flux:text class="text-zinc-400 text-sm">
        Everything runs in Stripe <b>test mode</b> while building — no real money. See <code>docs/STRIPE-SETUP.md</code>.
    </flux:text>
</div>
