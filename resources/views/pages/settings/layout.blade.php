<div class="flex items-start gap-8 max-md:flex-col">
    <aside class="w-full shrink-0 pb-4 md:w-56">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item icon="user" :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item icon="lock-closed" :href="route('security.edit')" wire:navigate>{{ __('Change password') }}</flux:navlist.item>
            <flux:navlist.item icon="swatch" :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </aside>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ $heading ?? '' }}</flux:heading>
            <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

            <div class="mt-6 w-full max-w-lg">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
