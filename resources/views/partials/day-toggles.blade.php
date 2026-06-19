{{-- Day-of-week toggles (Monday-first). $model = wire:model path to the days
     array, $weekdays = [dayNumber => name]. Owners tick the days a slot applies
     to — no need to know that Sunday is "day 0". --}}
<div>
    <span class="mb-1 block text-xs font-medium text-zinc-500">Days</span>
    <div class="flex flex-wrap gap-1.5">
        @foreach ($weekdays as $num => $label)
            <label class="cursor-pointer">
                <input type="checkbox" value="{{ $num }}" wire:model.live="{{ $model }}" class="peer sr-only" />
                <span class="inline-block rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-zinc-700 dark:text-zinc-300">
                    {{ \Illuminate\Support\Str::substr($label, 0, 3) }}
                </span>
            </label>
        @endforeach
    </div>
    @error($model)
        <flux:text class="mt-1 text-xs text-red-600">{{ $message }}</flux:text>
    @enderror
</div>
