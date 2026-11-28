{{-- Global Page Loading Overlay (.loader) --}}
@php($loaderTriggerOnly = !empty($triggerOnly))
<style>
    #global-page-loader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        background: rgba(248, 250, 252, 0.92);
        opacity: 1;
        visibility: visible;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    #global-page-loader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* HTML: <div class="loader"></div> */
    .loader {
        width: 60px;
        aspect-ratio: 1;
        border: 15px solid #ddd;
        border-radius: 50%;
        position: relative;
        transform: rotate(45deg);
    }

    .loader::before {
        content: "";
        position: absolute;
        inset: -15px;
        border-radius: 50%;
        border: 15px solid #514b82;
        animation: l18 2s infinite linear;
    }

    @keyframes l18 {
        0%   { clip-path: polygon(50% 50%, 0 0, 0 0, 0 0, 0 0, 0 0); }
        25%  { clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 0, 100% 0, 100% 0); }
        50%  { clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 100%, 100% 100%, 100% 100%); }
        75%  { clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 100%, 0 100%, 0 100%); }
        100% { clip-path: polygon(50% 50%, 0 0, 100% 0, 100% 100%, 0 100%, 0 0); }
    }

    .global-loader-text {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        letter-spacing: 0.01em;
    }
</style>

<div id="global-page-loader" class="{{ $loaderTriggerOnly ? 'is-hidden' : '' }}" role="status" aria-live="polite" aria-label="Memuat halaman">
    <div class="loader" aria-hidden="true"></div>
    <p id="global-page-loader-label" class="global-loader-text">Memuat halaman...</p>
</div>

<script>
    (function initGlobalPageLoader() {
        const overlay = document.getElementById('global-page-loader');
        const labelEl = document.getElementById('global-page-loader-label');
        const triggerOnly = {{ $loaderTriggerOnly ? 'true' : 'false' }};
        if (!overlay) return;

        let safetyTimer = null;

        function hideLoader() {
            if (safetyTimer) {
                clearTimeout(safetyTimer);
                safetyTimer = null;
            }
            overlay.classList.add('is-hidden');
        }

        function showLoader(text) {
            if (labelEl && text) {
                labelEl.textContent = text;
            }
            overlay.classList.remove('is-hidden');
            if (safetyTimer) clearTimeout(safetyTimer);
            // Lepas otomatis bila navigasi dibatalkan atau berupa unduhan berkas
            safetyTimer = setTimeout(hideLoader, 8000);
        }

        window.showPageLoader = showLoader;
        window.hidePageLoader = hideLoader;

        // Pastikan overlay tidak macet saat tombol Back/Forward browser (BFCache)
        window.addEventListener('pageshow', hideLoader);

        if (triggerOnly) {
            return;
        }

        // Sembunyikan begitu dokumen selesai dimuat
        if (document.readyState === 'complete') {
            hideLoader();
        } else {
            window.addEventListener('load', hideLoader, { once: true });
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(hideLoader, 180);
            }, { once: true });
        }

        // Tampilkan loader saat klik tautan navigasi internal
        document.addEventListener('click', function(e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const link = e.target.closest('a[href]');
            if (!link) return;

            const href = (link.getAttribute('href') || '').trim();
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (link.hasAttribute('download') || (link.getAttribute('target') && link.getAttribute('target') !== '_self')) return;
            if (href.indexOf('export-excel') !== -1 || href.indexOf('export') !== -1) return;

            try {
                const targetUrl = new URL(link.href, window.location.href);
                if (targetUrl.origin !== window.location.origin) return;
                if (targetUrl.href === window.location.href) return;
                showLoader('Memuat halaman...');
            } catch (err) {}
        });

        // Tampilkan loader saat formulir standar dikirim (bukan yang dibatalkan validasi)
        document.addEventListener('submit', function(e) {
            if (e.defaultPrevented) return;
            const form = e.target;
            if (!form || form.dataset.noLoader === 'true') return;
            if (form.getAttribute('target') && form.getAttribute('target') !== '_self') return;
            showLoader('Memproses data...');
        });
    })();
</script>
