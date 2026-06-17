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
        // Re-lock on blur so the next focus is guarded again (the field is readonly
        // at focus time, which is when browsers decide whether to offer autofill).
        document.addEventListener('focusout', function (e) {
            var t = e.target;
            if (t && t.matches && t.matches('input[data-no-autofill]')) t.setAttribute('readonly', 'readonly');
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

<script>
    // Shrink large photos in the browser before uploading so venue images
    // upload quickly even from a phone camera. Falls back to the original file.
    window.courtgoResizeImage = async function (file, maxDim, quality) {
        if (! file || ! file.type || file.type.indexOf('image/') !== 0) return file;
        try {
            var bitmap = await createImageBitmap(file);
            var scale = Math.min(1, maxDim / Math.max(bitmap.width, bitmap.height));
            var w = Math.round(bitmap.width * scale), h = Math.round(bitmap.height * scale);
            var canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
            var blob = await new Promise(function (res) { canvas.toBlob(res, 'image/jpeg', quality); });
            if (! blob) return file;
            return new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
        } catch (e) { return file; }
    };
    // For plain upload forms (data-resize-image on the file input): shrink the
    // chosen image in the browser, then submit the form as a single request.
    document.addEventListener('submit', async function (e) {
        var form = e.target;
        var input = form.querySelector && form.querySelector('input[type=file][data-resize-image]');
        if (! input || form.dataset.cgResized || ! input.files || ! input.files[0]) return;
        var file = input.files[0];
        if (! file.type || file.type.indexOf('image/') !== 0) return;
        e.preventDefault();
        var resized = await window.courtgoResizeImage(file, 1920, 0.90);
        try {
            var dt = new DataTransfer();
            dt.items.add(resized);
            input.files = dt.files;
        } catch (err) { /* DataTransfer unsupported — submit the original */ }
        form.dataset.cgResized = '1';
        form.submit();
    }, true);
</script>
@fluxAppearance
