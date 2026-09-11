{{--
    Clipboard copy button. The URL is passed via a data-* attribute and read
    off $root.dataset in Alpine, rather than inlined with @js(...) — @js()
    inside an Alpine attribute has been known to break Livewire's
    morph-aware Blade compiler on long expressions.

    Two real-world failure modes to guard against:
    1. navigator.clipboard only exists in a "secure context" — HTTPS, or the
       literal host "localhost". A real deployment (or a local *.test domain
       over plain HTTP, e.g. Laravel Herd) is neither, so that API is simply
       undefined there.
    2. The legacy execCommand('copy') fallback only works when it runs
       *synchronously* inside the trusted click handler — an `await` before
       it (e.g. trying the modern API first and falling back on failure)
       ends the browser's "user gesture" window, so execCommand then
       silently returns false. So execCommand is tried FIRST, synchronously;
       the modern async API is the fallback, not the other way around.
--}}
@props([
    'url',
    'label' => 'Copy link',
])
<button
    type="button"
    x-data="{
        copied: false,
        markCopied() {
            this.copied = true;
            setTimeout(() => (this.copied = false), 1500);
        },
        copy() {
            const text = $root.dataset.url;

            let legacyOk = false;
            try {
                const el = document.createElement('textarea');
                el.value = text;
                el.setAttribute('readonly', '');
                el.style.position = 'fixed';
                el.style.top = '0';
                el.style.left = '0';
                el.style.opacity = '0';
                el.style.pointerEvents = 'none';
                document.body.appendChild(el);
                el.focus();
                el.select();
                el.setSelectionRange(0, text.length);
                legacyOk = document.execCommand('copy');
                document.body.removeChild(el);
            } catch (e) {
                legacyOk = false;
            }

            if (legacyOk) {
                this.markCopied();
                return;
            }

            if (window.isSecureContext && navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => this.markCopied()).catch(() => {});
            }
        },
    }"
    x-on:click="copy()"
    data-url="{{ $url }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50']) }}
>
    <mds:icon icon="copy-01" class="size-4" />
    <span x-show="! copied">{{ $label }}</span>
    <span x-show="copied" x-cloak>Copied</span>
</button>
