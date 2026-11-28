{{-- Global Feedback Toast Notification (Success / Error / Warning, min 2s display) --}}
<style>
    #ups-toast-stack {
        position: fixed;
        top: 16px;
        right: 16px;
        z-index: 99990;
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: calc(100vw - 32px);
        max-width: 380px;
        pointer-events: none;
    }

    @media (max-width: 639px) {
        #ups-toast-stack {
            top: 12px;
            right: 12px;
            left: 12px;
            width: auto;
            max-width: none;
        }
    }

    .ups-toast {
        pointer-events: auto;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 14px 15px;
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.16), 0 4px 10px -4px rgba(15, 23, 42, 0.08);
        font-family: 'Plus Jakarta Sans', sans-serif;
        opacity: 0;
        transform: translateY(-10px) scale(0.98);
        transition: opacity 0.22s ease, transform 0.22s ease;
    }

    .ups-toast.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .ups-toast.is-leaving {
        opacity: 0;
        transform: translateY(-8px) scale(0.97);
    }

    .ups-toast--success {
        border-color: #a7f3d0;
    }

    .ups-toast--error {
        border-color: #fecdd3;
    }

    .ups-toast--warning {
        border-color: #fde68a;
    }

    .ups-toast-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .ups-toast--success .ups-toast-icon {
        background: #ecfdf5;
        color: #059669;
    }

    .ups-toast--error .ups-toast-icon {
        background: #fff1f2;
        color: #e11d48;
    }

    .ups-toast--warning .ups-toast-icon {
        background: #fffbeb;
        color: #d97706;
    }

    .ups-toast-body {
        flex: 1;
        min-width: 0;
    }

    .ups-toast-title {
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
        margin: 0 0 2px 0;
    }

    .ups-toast-msg {
        font-size: 12px;
        font-weight: 500;
        line-height: 1.45;
        color: #475569;
        margin: 0;
        word-break: break-word;
    }

    .ups-toast-close {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border: none;
        background: transparent;
        color: #64748b;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .ups-toast-close:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .ups-toast-progress {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 3px;
        transform-origin: left center;
        animation-name: upsToastCountdown;
        animation-timing-function: linear;
        animation-fill-mode: forwards;
    }

    .ups-toast--success .ups-toast-progress {
        background: #10b981;
    }

    .ups-toast--error .ups-toast-progress {
        background: #f43f5e;
    }

    .ups-toast--warning .ups-toast-progress {
        background: #f59e0b;
    }

    .ups-toast:hover .ups-toast-progress {
        animation-play-state: paused;
    }

    @keyframes upsToastCountdown {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>

<div id="ups-toast-stack" aria-live="polite" aria-atomic="false"></div>

<script>
    (function initUpsToastFeedback() {
        const stack = document.getElementById('ups-toast-stack');
        if (!stack) return;

        const ICONS = {
            success: '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7"/></svg>',
            error: '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'
        };

        const DEFAULT_TITLES = {
            success: 'Berhasil',
            error: 'Gagal',
            warning: 'Peringatan'
        };

        function showToast(type, message, options) {
            if (!message) return;
            const kind = (type === 'error' || type === 'warning') ? type : 'success';
            const opts = options || {};
            const title = opts.title || DEFAULT_TITLES[kind];
            const minDuration = 2000;
            const defaultDuration = kind === 'success' ? 2500 : 3000;
            const duration = Math.max(minDuration, Number(opts.duration) || defaultDuration);

            const toast = document.createElement('div');
            toast.className = 'ups-toast ups-toast--' + kind;
            toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');

            const iconEl = document.createElement('div');
            iconEl.className = 'ups-toast-icon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.innerHTML = ICONS[kind];

            const bodyEl = document.createElement('div');
            bodyEl.className = 'ups-toast-body';

            const titleEl = document.createElement('p');
            titleEl.className = 'ups-toast-title';
            titleEl.textContent = title;

            const msgEl = document.createElement('p');
            msgEl.className = 'ups-toast-msg';
            msgEl.textContent = String(message);

            bodyEl.appendChild(titleEl);
            bodyEl.appendChild(msgEl);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'ups-toast-close';
            closeBtn.setAttribute('aria-label', 'Tutup notifikasi');
            closeBtn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/></svg>';

            const progressEl = document.createElement('div');
            progressEl.className = 'ups-toast-progress';
            progressEl.style.animationDuration = duration + 'ms';

            toast.appendChild(iconEl);
            toast.appendChild(bodyEl);
            toast.appendChild(closeBtn);
            toast.appendChild(progressEl);

            stack.appendChild(toast);

            let remaining = duration;
            let startedAt = Date.now();
            let timerId = null;
            let dismissed = false;

            function dismiss() {
                if (dismissed) return;
                dismissed = true;
                if (timerId) clearTimeout(timerId);
                toast.classList.remove('is-visible');
                toast.classList.add('is-leaving');
                setTimeout(function() {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 240);
            }

            function startTimer() {
                startedAt = Date.now();
                timerId = setTimeout(dismiss, remaining);
            }

            toast.addEventListener('mouseenter', function() {
                if (dismissed) return;
                if (timerId) clearTimeout(timerId);
                remaining = Math.max(400, remaining - (Date.now() - startedAt));
            });

            toast.addEventListener('mouseleave', function() {
                if (dismissed) return;
                startTimer();
            });

            closeBtn.addEventListener('click', dismiss);

            requestAnimationFrame(function() {
                toast.classList.add('is-visible');
            });
            startTimer();
        }

        window.showToast = showToast;

        document.addEventListener('DOMContentLoaded', function() {
            const initialToasts = [];

            @if(session('success'))
                initialToasts.push({ type: 'success', message: @json(session('success')) });
            @endif

            @if(session('status'))
                initialToasts.push({ type: 'success', message: @json(session('status')) });
            @endif

            @if(session('warning'))
                initialToasts.push({ type: 'warning', message: @json(session('warning')) });
            @endif

            @if(session('error'))
                initialToasts.push({ type: 'error', message: @json(session('error')) });
            @endif

            @if(isset($errors) && $errors->any())
                initialToasts.push({
                    type: 'error',
                    title: 'Perubahan Gagal',
                    message: @json($errors->count() === 1 ? $errors->first() : $errors->first() . ' (+' . ($errors->count() - 1) . ' kesalahan lainnya)')
                });
            @endif

            try {
                const pendingRaw = sessionStorage.getItem('ups_pending_toast');
                if (pendingRaw) {
                    sessionStorage.removeItem('ups_pending_toast');
                    const pending = JSON.parse(pendingRaw);
                    if (pending && pending.message) {
                        initialToasts.push(pending);
                    }
                }
            } catch (e) {}

            initialToasts.forEach(function(item, idx) {
                setTimeout(function() {
                    showToast(item.type, item.message, { title: item.title, duration: item.duration });
                }, idx * 120);
            });
        });
    })();
</script>
