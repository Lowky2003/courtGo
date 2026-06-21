@props(['venue'])

@php($hasContact = $venue->contact_phone || $venue->contact_whatsapp || $venue->contact_email || $venue->contact_website || $venue->contact_instagram || $venue->contact_facebook)

@if ($hasContact)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-4']) }}>
        @if ($venue->contact_phone)
            <a href="tel:{{ $venue->contact_phone }}" title="Call {{ $venue->contact_phone }}" aria-label="Call {{ $venue->contact_phone }}"
               class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                <flux:icon name="phone" class="size-6" />
            </a>
        @endif

        @if ($venue->contact_whatsapp)
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $venue->contact_whatsapp) }}" target="_blank" rel="noopener noreferrer" title="WhatsApp" aria-label="WhatsApp"
               class="text-[#25D366] transition hover:opacity-80">
                <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.359.101 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.304-1.654a11.881 11.881 0 005.683 1.448h.005c6.582 0 11.94-5.359 11.943-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
        @endif

        @if ($venue->contact_email)
            <a href="mailto:{{ $venue->contact_email }}" title="Email {{ $venue->contact_email }}" aria-label="Email"
               class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                <flux:icon name="envelope" class="size-6" />
            </a>
        @endif

        @if ($venue->contact_website)
            <a href="{{ $venue->contact_website }}" target="_blank" rel="noopener noreferrer" title="Website" aria-label="Website"
               class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                <flux:icon name="globe-alt" class="size-6" />
            </a>
        @endif

        @if ($venue->contact_instagram)
            <a href="{{ $venue->contact_instagram }}" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram"
               class="text-[#E4405F] transition hover:opacity-80">
                <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069M12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0m0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324M12 16a4 4 0 110-8 4 4 0 010 8m6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881"/></svg>
            </a>
        @endif

        @if ($venue->contact_facebook)
            <a href="{{ $venue->contact_facebook }}" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook"
               class="text-[#1877F2] transition hover:opacity-80">
                <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073"/></svg>
            </a>
        @endif
    </div>
@endif
