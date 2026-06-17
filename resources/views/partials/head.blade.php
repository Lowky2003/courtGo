<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>[x-cloak]{display:none !important;}</style>

<script>
    // Default the whole app to light mode unless the visitor has explicitly
    // picked an appearance before. (Flux otherwise follows the OS setting.)
    if (! window.localStorage.getItem('flux.appearance')) {
        window.localStorage.setItem('flux.appearance', 'light');
    }
</script>

<script>
    // Suppress the browser's autofill / saved-value popup on free-text fields by
    // holding them readonly until focus (browsers skip autofill on readonly fields).
    // Uses event delegation + skips the focused field, so it survives Livewire morphs.
    (function () {
        function lockAll() {
            document.querySelectorAll('input[data-no-autofill]').forEach(function (el) {
                if (el !== document.activeElement) el.setAttribute('readonly', 'readonly');
            });
        }
        document.addEventListener('focusin', function (e) {
            var t = e.target;
            if (t && t.matches && t.matches('input[data-no-autofill]')) t.removeAttribute('readonly');
        });
        document.addEventListener('DOMContentLoaded', lockAll);
        document.addEventListener('livewire:navigated', lockAll);
        document.addEventListener('livewire:init', function () {
            window.Livewire.hook('commit', function (payload) {
                if (payload && typeof payload.succeed === 'function') {
                    payload.succeed(function () { setTimeout(lockAll, 0); });
                }
            });
        });
    })();
</script>
@fluxAppearance
