@props(['name', 'accept' => null, 'multiple' => false])

{{-- Native file input with the "Choose File" button styled to look like a real
     button (Tailwind file: utilities), so it's obvious it's clickable. --}}
<input
    type="file"
    name="{{ $name }}{{ $multiple ? '[]' : '' }}"
    @if ($accept) accept="{{ $accept }}" @endif
    @if ($multiple) multiple @endif
    {{ $attributes->merge([
        'class' => 'max-w-full cursor-pointer text-sm text-zinc-500 dark:text-zinc-400 '
            .'file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 '
            .'file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white '
            .'file:transition hover:file:bg-blue-700',
    ]) }}
/>
