{{-- AI Chatbot Floating Widget --}}
{{-- Terintegrasi dengan Groq AI untuk informasi keuangan kas dan akademik --}}

<style>
    /* ===== CHATBOT FLOATING BUTTON ===== */
    #chatbot-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9998;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
        color: #fff;
        border: none;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(15, 23, 42, 0.2);
        transition: transform 0.25s ease, opacity 0.25s ease, box-shadow 0.25s ease;
        animation: chatbot-fab-pulse 3s ease-in-out infinite;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }

    #chatbot-fab.is-dragging {
        cursor: grabbing !important;
        transition: none !important;
        animation: none !important;
        box-shadow: 0 16px 48px rgba(15, 23, 42, 0.5) !important;
    }

    #chatbot-fab.chatbot-fab-hidden {
        opacity: 0 !important;
        pointer-events: none !important;
        transform: scale(0) !important;
    }

    #chatbot-fab:hover:not(.is-dragging):not(.chatbot-fab-hidden) {
        transform: scale(1.08);
        box-shadow: 0 12px 40px rgba(15, 23, 42, 0.45), 0 4px 12px rgba(15, 23, 42, 0.3);
    }

    #chatbot-fab:active:not(.is-dragging) {
        transform: scale(0.96);
    }

    #chatbot-fab svg {
        width: 26px;
        height: 26px;
        transition: transform 0.3s ease;
        pointer-events: none;
    }

    #chatbot-fab:hover:not(.is-dragging) svg {
        transform: rotate(-10deg);
    }

    #chatbot-fab .chatbot-fab-badge {
        pointer-events: none;
        position: absolute;
        top: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #10b981;
        border: 2.5px solid #fff;
        animation: chatbot-badge-blink 2s ease-in-out infinite;
    }

    @keyframes chatbot-fab-pulse {
        0%, 100% { box-shadow: 0 8px 32px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(15, 23, 42, 0.2); }
        50% { box-shadow: 0 8px 32px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(15, 23, 42, 0.2), 0 0 0 8px rgba(30, 41, 59, 0.08); }
    }

    @keyframes chatbot-badge-blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Respect prefers-reduced-motion (R-18, R-37, WCAG 2.3.3) */
    @media (prefers-reduced-motion: reduce) {
        #chatbot-fab, #chatbot-fab .chatbot-fab-badge, .chatbot-typing-dots span {
            animation: none !important;
            transition: none !important;
        }
        #chatbot-panel {
            transition: opacity 0.1s linear !important;
            transform: none !important;
        }
    }

    /* ===== CHATBOT PANEL ===== */
    #chatbot-panel {
        position: fixed;
        bottom: 92px;
        right: 24px;
        z-index: 9999;
        width: 400px;
        max-width: calc(100vw - 32px);
        height: 560px;
        max-height: calc(100vh - 120px);
        max-height: calc(100dvh - 120px);
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.2), 0 8px 24px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(148, 163, 184, 0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px) scale(0.95);
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #chatbot-panel.chatbot-open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    #chatbot-panel.is-positioned {
        bottom: auto !important;
        right: auto !important;
    }

    /* ===== CHATBOT HEADER (DRAGGABLE) ===== */
    .chatbot-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
        padding: 8px 16px 14px;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        cursor: grab;
        user-select: none;
        -webkit-user-select: none;
        touch-action: none;
    }

    .chatbot-header:active, #chatbot-panel.panel-is-dragging .chatbot-header {
        cursor: grabbing !important;
    }

    .chatbot-drag-handle-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 2px 0 8px;
        cursor: grab;
    }

    .chatbot-drag-handle-pill {
        width: 38px;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 9999px;
        transition: all 0.2s ease;
    }

    .chatbot-header:hover .chatbot-drag-handle-pill,
    #chatbot-panel.panel-is-dragging .chatbot-drag-handle-pill {
        background: rgba(255, 255, 255, 0.7);
        width: 48px;
    }

    .chatbot-header-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .chatbot-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chatbot-avatar {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    .chatbot-avatar svg {
        width: 20px;
        height: 20px;
        color: #fff;
    }

    .chatbot-header-info h3 {
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .chatbot-header-info p {
        color: #94a3b8;
        font-size: 10.5px;
        margin: 2px 0 0;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .chatbot-header-info p .chatbot-online-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        display: inline-block;
        animation: chatbot-badge-blink 2s ease-in-out infinite;
    }

    .chatbot-header-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .chatbot-header-actions button {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: rgba(255, 255, 255, 0.08);
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .chatbot-header-actions button:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .chatbot-header-actions button svg {
        width: 15px;
        height: 15px;
    }

    /* ===== CHATBOT MESSAGES ===== */
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f8fafc;
        scroll-behavior: smooth;
    }

    .chatbot-messages::-webkit-scrollbar {
        width: 5px;
    }

    .chatbot-messages::-webkit-scrollbar-track {
        background: transparent;
    }

    .chatbot-messages::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .chatbot-messages::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Message Bubbles */
    .chatbot-msg {
        display: flex;
        gap: 8px;
        max-width: 95%;
        min-width: 0;
        animation: chatbot-msg-in 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes chatbot-msg-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chatbot-msg.chatbot-msg-user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .chatbot-msg-avatar {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        margin-top: 2px;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.12);
        user-select: none;
    }

    .chatbot-msg-ai .chatbot-msg-avatar {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.25);
    }

    .chatbot-msg-user .chatbot-msg-avatar {
        color: #fff;
        border: 1px solid rgba(255,255,255,0.25);
    }

    .chatbot-avatar-admin {
        background: linear-gradient(135deg, #7c3aed 0%, #4338ca 100%) !important;
    }

    .chatbot-avatar-bendahara {
        background: linear-gradient(135deg, #059669 0%, #0d9488 100%) !important;
    }

    .chatbot-avatar-ketua {
        background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%) !important;
    }

    .chatbot-avatar-mahasiswa {
        background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%) !important;
    }

    /* Message Header Metadata */
    .chatbot-msg-meta {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 3px;
        font-size: 10.5px;
    }

    .chatbot-msg-user .chatbot-msg-meta {
        justify-content: flex-end;
    }

    .chatbot-msg-name {
        font-weight: 700;
        color: #475569;
    }

    .chatbot-msg-tag {
        font-size: 9px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .chatbot-tag-admin { background: #f3e8ff; color: #7e22ce; }
    .chatbot-tag-bendahara { background: #dcfce7; color: #15803d; }
    .chatbot-tag-ketua { background: #e0f2fe; color: #0369a1; }
    .chatbot-tag-mahasiswa { background: #f1f5f9; color: #475569; }
    .chatbot-tag-ai { background: #e0e7ff; color: #4338ca; }

    .chatbot-msg-bubble {
        padding: 8px 12px;
        border-radius: 14px;
        font-size: 12.5px;
        line-height: 1.45;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        hyphens: auto;
        min-width: 0;
        width: fit-content;
        max-width: 100%;
        text-align: left;
        box-sizing: border-box;
    }

    .chatbot-msg-ai .chatbot-msg-bubble {
        background: #fff;
        color: #334155;
        border: 1px solid #e2e8f0;
        border-top-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        text-align: left;
    }

    .chatbot-msg-user .chatbot-msg-bubble {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: #f1f5f9;
        border-top-right-radius: 4px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        text-align: left;
    }

    .chatbot-msg-bubble p {
        margin: 0 0 6px;
    }

    .chatbot-msg-bubble p:last-child {
        margin-bottom: 0;
    }

    .chatbot-msg-bubble ul,
    .chatbot-msg-bubble ol {
        margin: 4px 0;
        padding-left: 18px;
    }

    .chatbot-msg-bubble li {
        margin-bottom: 2px;
    }

    .chatbot-msg-bubble strong {
        font-weight: 700;
        color: #0f172a;
    }

    .chatbot-msg-user .chatbot-msg-bubble strong {
        color: #fff;
    }

    .chatbot-msg-bubble code {
        background: #f1f5f9;
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 11.5px;
        font-family: 'Courier New', monospace;
        color: #6366f1;
    }

    .chatbot-msg-user .chatbot-msg-bubble code {
        background: rgba(255,255,255,0.15);
        color: #c7d2fe;
    }

    .chatbot-msg-time {
        font-size: 9.5px;
        color: #94a3b8;
        margin-top: 4px;
        display: block;
    }

    .chatbot-msg-user .chatbot-msg-time {
        text-align: right;
    }

    /* Typing Indicator */
    .chatbot-typing {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }

    .chatbot-typing-dots {
        display: flex;
        align-items: center;
        gap: 4px;
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 10px 16px;
        border-radius: 14px;
        border-top-left-radius: 4px;
    }

    .chatbot-typing-dots span {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #94a3b8;
        animation: chatbot-typing-bounce 1.4s ease-in-out infinite;
    }

    .chatbot-typing-dots span:nth-child(2) {
        animation-delay: 0.15s;
    }

    .chatbot-typing-dots span:nth-child(3) {
        animation-delay: 0.3s;
    }

    @keyframes chatbot-typing-bounce {
        0%, 60%, 100% { transform: translateY(0); background: #cbd5e1; }
        30% { transform: translateY(-6px); background: #6366f1; }
    }

    /* Welcome Card */
    .chatbot-welcome {
        background: linear-gradient(135deg, #eef2ff, #e0e7ff);
        border: 1px solid #c7d2fe;
        border-radius: 14px;
        padding: 18px;
        text-align: center;
    }

    .chatbot-welcome-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }

    .chatbot-welcome-icon svg {
        width: 22px;
        height: 22px;
        color: #fff;
    }

    .chatbot-welcome h4 {
        font-size: 13px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 4px;
    }

    .chatbot-welcome p {
        font-size: 11.5px;
        color: #475569;
        margin: 0 0 14px;
        line-height: 1.5;
    }

    .chatbot-quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
    }

    .chatbot-quick-btn {
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid #c7d2fe;
        background: #fff;
        color: #4338ca;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .chatbot-quick-btn:hover {
        background: #4338ca;
        color: #fff;
        border-color: #4338ca;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(67, 56, 202, 0.25);
    }

    .chatbot-copy-wa-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid #86efac;
        background: #f0fdf4;
        color: #15803d;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .chatbot-copy-wa-btn:hover {
        background: #dcfce7;
        border-color: #4ade80;
        color: #166534;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(22, 163, 74, 0.18);
    }

    .chatbot-copy-wa-btn:active {
        transform: translateY(0);
    }

    .chatbot-excel-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff !important;
        text-decoration: none !important;
        font-size: 11.5px;
        font-weight: 700;
        padding: 8px 14px;
        border-radius: 8px;
        margin-top: 8px;
        box-shadow: 0 3px 10px rgba(16, 185, 129, 0.35);
        transition: all 0.2s ease;
    }

    .chatbot-excel-download-btn:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(16, 185, 129, 0.45);
        color: #ffffff !important;
    }

    /* ===== CHATBOT INPUT ===== */
    .chatbot-input-area {
        padding: 12px 14px;
        background: #fff;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .chatbot-input-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 4px 6px 4px 14px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .chatbot-input-wrapper:focus-within {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .chatbot-input-wrapper input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 12.5px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #1e293b;
        padding: 8px 0;
    }

    .chatbot-input-wrapper input::placeholder {
        color: #94a3b8;
    }

    .chatbot-send-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, #1e293b, #334155);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .chatbot-send-btn:hover {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        transform: scale(1.05);
    }

    .chatbot-send-btn:active {
        transform: scale(0.95);
    }

    .chatbot-send-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
    }

    .chatbot-send-btn svg {
        width: 16px;
        height: 16px;
    }

    .chatbot-attach-btn {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        border: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }

    .chatbot-attach-btn:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 640px) {
        #chatbot-panel {
            position: fixed;
            bottom: 84px;
            left: 12px;
            right: 12px;
            width: auto;
            max-width: calc(100vw - 24px);
            height: 480px;
            max-height: calc(100dvh - 105px);
            max-height: calc(100vh - 105px);
            border-radius: 18px;
            z-index: 10001;
            box-shadow: 0 16px 48px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(148, 163, 184, 0.2);
        }

        #chatbot-panel.is-positioned {
            bottom: auto !important;
            right: auto !important;
            width: calc(100vw - 24px) !important;
        }

        #chatbot-fab {
            width: 52px;
            height: 52px;
            bottom: 20px;
            right: 20px;
        }

        .chatbot-msg {
            max-width: 95%;
        }

        .chatbot-msg-bubble {
            font-size: 12px;
            padding: 7px 11px;
            line-height: 1.45;
            width: fit-content;
            max-width: 100%;
        }

        .chatbot-msg-avatar {
            width: 26px;
            height: 26px;
            font-size: 10px;
            border-radius: 7px;
        }

        .chatbot-welcome h4 {
            font-size: 12.5px;
        }

        .chatbot-welcome p {
            font-size: 11px;
        }

        .chatbot-quick-btn {
            font-size: 10.5px;
            padding: 5px 10px;
        }

        .chatbot-header {
            padding: 12px 14px;
        }

        .chatbot-avatar {
            width: 32px;
            height: 32px;
            border-radius: 10px;
        }

        .chatbot-header-info h3 {
            font-size: 12.5px;
        }

        .chatbot-input-wrapper input {
            font-size: 12px;
        }
    }

    /* Tablet / Desktop-mode di HP */
    @media (min-width: 641px) and (max-width: 1024px) {
        #chatbot-panel {
            width: 380px;
            height: 500px;
            max-height: calc(100vh - 100px);
            max-height: calc(100dvh - 100px);
        }
    }
</style>

@if(auth()->check())
{{-- Floating Action Button (Bisa digeser / Draggable di semua perangkat) --}}
<button id="chatbot-fab" title="Klik untuk membuka AI Chatbot, atau geser (drag) untuk memindahkan posisi" aria-label="Buka AI Chatbot">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <span class="chatbot-fab-badge"></span>
</button>

{{-- Chat Panel --}}
<div id="chatbot-panel">
    {{-- Header (Bisa di-drag / digeser dengan mouse atau sentuhan jari) --}}
    <div class="chatbot-header" id="chatbot-header" title="Klik dan tahan untuk menggeser jendela popup">
        <div class="chatbot-drag-handle-bar">
            <span class="chatbot-drag-handle-pill" title="Pegang di sini untuk menggeser"></span>
        </div>
        <div class="chatbot-header-main">
            <div class="chatbot-header-left">
                <div class="chatbot-avatar">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="chatbot-header-info">
                    <h3>Asisten Kelas AI</h3>
                    <p><span class="chatbot-online-dot"></span> Online · Informatika UPS</p>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button onclick="clearChatHistory()" title="Hapus Riwayat">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                <button onclick="toggleChatbot()" title="Tutup">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    <div class="chatbot-messages" id="chatbot-messages">
        @php
            $activeClassSemester = \App\Services\AcademicService::getActiveSemester();
            $currentMonth = (int) now()->month;
            $currentYear = (int) now()->year;
            $activeWeek = min(4, (int) ceil(now()->day / 7));
            $duesRate = \App\Services\AcademicService::getDuesRatePerWeek();
            $namaBulan = \App\Services\AcademicService::getNamaBulan($currentMonth);

            $userName = auth()->user()->name ?? 'Pengguna';
            $nameParts = preg_split('/\s+/', trim($userName));
            $initials = '';
            if (count($nameParts) >= 2) {
                $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1));
            } else {
                $initials = strtoupper(mb_substr($userName, 0, 2));
            }
            $userRole = auth()->user()->role ?? 'mahasiswa';
            $roleMap = [
                'admin' => 'Super Admin',
                'bendahara' => 'Bendahara',
                'ketua' => 'Ketua Kelas',
            ];
            $userRoleLabel = isset($roleMap[$userRole]) ? $roleMap[$userRole] : 'Mahasiswa';

            // Deteksi konteks halaman aktif untuk Context-Aware Quick Prompts
            $pageContext = 'dashboard';
            $pageTitle = 'Dashboard Kelas';
            $pageIcon = '📊';

            if (request()->routeIs('kas.*') || request()->is('kas*')) {
                $pageContext = 'kas';
                $pageTitle = 'Buku Kas Kelas';
                $pageIcon = '📖';
            } elseif (request()->routeIs('arsip.*') || request()->is('arsip*')) {
                $pageContext = 'arsip';
                $pageTitle = 'Arsip & Rekapitulasi Kas';
                $pageIcon = '📁';
            } elseif (request()->routeIs('admin.users*') || request()->is('admin/users*') || request()->is('admin*')) {
                $pageContext = 'admin_users';
                $pageTitle = 'Kelola Akun & Semester';
                $pageIcon = '⚙️';
            }
        @endphp

        {{-- Welcome Card --}}
        <div class="chatbot-welcome">
            <div class="chatbot-welcome-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>

            <!-- Identitas User Profil -->
            <div style="display:inline-flex; align-items:center; gap:8px; padding:4px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:9999px; margin-bottom:6px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <span class="chatbot-msg-avatar chatbot-avatar-{{ $userRole }}" style="width:24px; height:24px; font-size:10px; border-radius:6px; box-shadow:none;">{{ $initials }}</span>
                <span style="font-size:11.5px; font-weight:700; color:#1e293b;">{{ $userName }}</span>
                <span class="chatbot-msg-tag chatbot-tag-{{ $userRole }}">{{ $userRoleLabel }}</span>
            </div>

            <h4>Halo, {{ $userName }}!</h4>
            <div style="display:inline-flex; align-items:center; gap:6px; font-size:10.5px; color:#475569; background:#f1f5f9; padding:2px 10px; border-radius:12px; margin-top:3px; border:1px solid #e2e8f0;">
                <span>{{ $pageIcon }}</span>
                <span>Halaman: <strong class="text-slate-800">{{ $pageTitle }}</strong></span>
            </div>
            <p style="font-size:11px; color:#64748b; margin-top:4px;">Periode: <strong class="text-slate-800">{{ $namaBulan }} {{ $currentYear }}</strong> • Tarif: <strong class="text-slate-800">Rp {{ number_format($duesRate, 0, ',', '.') }} / minggu</strong></p>

            {{-- Context-Aware & Role-Based Quick Actions --}}
            <div class="chatbot-quick-actions" id="chatbot-context-quick-actions" style="margin-top: 12px;">
                @if($pageContext === 'kas')
                    {{-- 1. Halaman Buku Kas Kelas --}}
                    @if(in_array($userRole, ['admin', 'ketua']))
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan pesan pengingat tagihan kas untuk grup WA')">📢 Broadcast WA</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa saja mahasiswa yang belum bayar uang kas bulan ini?')">⚠️ Rekap Nunggak</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa saldo kas kelas saat ini dan berapa tarif kas per minggu?')">💰 Saldo & Tarif</button>
                    @elseif($userRole === 'bendahara')
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan pesan pengingat tagihan kas untuk grup WA')">📢 Broadcast WA</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa saja mahasiswa yang belum bayar uang kas bulan ini?')">⚠️ Rekap Nunggak</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Bagaimana format perintah chat untuk mencatat pemasukan dan pengeluaran kas?')">➕ Format Catat Kas</button>
                    @else
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Apakah saya punya tunggakan iuran kas?')">🔍 Cek Kas Saya</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa tarif iuran kas per minggu dan total saldo saat ini?')">💰 Tarif & Saldo</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa bendahara kelas untuk pembayaran kas?')">👤 Bendahara Kelas</button>
                    @endif

                @elseif($pageContext === 'arsip')
                    {{-- 2. Halaman Arsip & Rekapitulasi Kas --}}
                    @if(in_array($userRole, ['admin', 'ketua']))
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Rangkum pemasukan, pengeluaran, dan saldo kas untuk semester aktif saat ini')">📈 Rekap Semester Ini</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Bandingkan saldo dan mutasi kas antar semester yang ada di arsip')">📊 Perbandingan Semester</button>
                    @elseif($userRole === 'bendahara')
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Rangkum pemasukan, pengeluaran, dan saldo kas untuk semester aktif saat ini')">📈 Rekap Semester Ini</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Rincikan mutasi kas bulanan tahun berjalan dari data arsip')">📊 Mutasi Bulanan</button>
                    @else
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Rangkum pemasukan, pengeluaran, dan saldo kas untuk semester aktif saat ini')">📈 Rekap Semester Ini</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa total pemasukan dan pengeluaran kas bulan ini?')">📅 Kas Bulan Ini</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Jelaskan fungsi dan ketentuan data pada halaman arsip kas ini')">ℹ️ Integritas Arsip</button>
                    @endif

                @elseif($pageContext === 'admin_users')
                    {{-- 3. Halaman Kelola Akun & Semester (Super Admin & Ketua Kelas) --}}
                    @if(in_array($userRole, ['admin', 'ketua']))
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa saja mahasiswa dan pengurus di kelas ini?')">👥 Data Akun & Role</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Semester berapa yang sedang aktif saat ini dan bagaimana cara mengubahnya?')">🎓 Semester Aktif</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Bagaimana format perintah chat untuk menambahkan akun baru?')">➕ Tambah Akun</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Bagaimana format perintah chat untuk mengubah role akun mahasiswa?')">🔄 Ubah Role</button>
                    @else
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa saja mahasiswa dan pengurus di kelas ini?')">👥 Anggota Kelas</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Apakah saya punya tunggakan iuran kas?')">🔍 Cek Kas Saya</button>
                    @endif

                @else
                    {{-- 4. Halaman Dashboard Kelas --}}
                    @if(in_array($userRole, ['admin', 'ketua']))
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan pesan pengingat tagihan kas untuk grup WA')">📢 Broadcast WA</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa saldo kas kelas saat ini?')">💰 Saldo Kas</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berikan ringkasan lengkap kondisi kas, semester aktif, dan statistik kelas saat ini')">📊 Ringkasan Kelas</button>
                    @elseif($userRole === 'bendahara')
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan pesan pengingat tagihan kas untuk grup WA')">📢 Broadcast WA</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa saldo kas kelas saat ini?')">💰 Saldo Kas</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Tampilkan jejak audit aktivitas kas')">🔍 Jejak Audit</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Lihat riwayat transaksi kas terakhir')">📋 Riwayat Kas</button>
                    @else
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa saldo kas kelas saat ini?')">💰 Saldo Kas</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Buatkan rekap kas dalam excel')">📥 Rekap Excel</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Simulasi anggaran kas kelas')">💡 Simulasi Anggaran</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Apakah saya punya tunggakan iuran kas?')">🔍 Cek Kas Saya</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Siapa saja mahasiswa dan pengurus di kelas ini?')">👥 Anggota Kelas</button>
                        <button class="chatbot-quick-btn" onclick="sendQuickMessage('Jelaskan fitur-fitur web ini dan panduan penggunaannya')">📖 Panduan Web</button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Input Area --}}
    <div class="chatbot-input-area">
        <div class="chatbot-input-wrapper">
            <input type="text" id="chatbot-input" placeholder="Ketik pertanyaan seputar kas atau kelas..." autocomplete="off"
                   onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendChatMessage();}">
            <button class="chatbot-send-btn" id="chatbot-send-btn" onclick="sendChatMessage()" title="Kirim">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- Modal Lightbox In-App Pratinjau Gambar / Foto Tanpa Membuka Tab Baru --}}
<div id="chatbot-image-modal" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(15, 23, 42, 0.85); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:16px;" onclick="closeChatbotImageModal(event)">
    <div style="position:relative; max-width:92vw; max-height:92vh; display:flex; flex-direction:column; align-items:center; background:#1e293b; border-radius:16px; border:1px solid rgba(255,255,255,0.15); box-shadow:0 25px 50px -12px rgba(0,0,0,0.7); overflow:hidden;" onclick="event.stopPropagation()">
        <!-- Header Modal -->
        <div style="width:100%; display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#0f172a; border-bottom:1px solid rgba(255,255,255,0.1);">
            <div style="display:flex; align-items:center; gap:8px; color:#f8fafc; font-size:12.5px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:80vw;">
                <span>🖼️</span>
                <span id="chatbot-image-modal-caption">Pratinjau Foto Berkas</span>
            </div>
            <button type="button" onclick="closeChatbotImageModal()" style="background:rgba(255,255,255,0.1); border:none; color:#f8fafc; width:28px; height:28px; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:15px; font-weight:bold; transition:all 0.2s; margin-left:12px; flex-shrink:0;" onmouseover="this.style.background='rgba(239,68,68,0.8)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" title="Tutup (Esc)">✕</button>
        </div>
        <!-- Konten Gambar -->
        <div style="padding:10px; display:flex; align-items:center; justify-content:center; overflow:auto; max-height:calc(92vh - 54px); background:#020617;">
            <img id="chatbot-image-modal-img" src="" alt="Pratinjau Foto Berkas" style="max-width:88vw; max-height:calc(88vh - 60px); object-fit:contain; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.5);">
        </div>
    </div>
</div>

<script>
    const CHATBOT_CHAT_URL = "{{ route('chatbot.chat') }}";
    const CHATBOT_CLEAR_URL = "{{ route('chatbot.clear') }}";
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";
    }
    const CURRENT_USER_NAME = {!! json_encode($userName) !!};
    const CURRENT_USER_INITIALS = {!! json_encode($initials) !!};
    const CURRENT_USER_ROLE = {!! json_encode($userRole) !!};
    const CURRENT_USER_ROLE_LABEL = {!! json_encode($userRoleLabel) !!};
    const CURRENT_DUES_RATE = {{ $duesRate }};
    let chatbotIsOpen = false;
    let chatbotIsSending = false;
    let chatMessagesHistory = [];

    function isManualPageRefresh() {
        // Jika reload dipicu oleh sinkronisasi otomatis aksi chatbot, jangan anggap sebagai refresh manual
        if (sessionStorage.getItem('chatbot_keep_open_after_sync') === 'true') {
            return false;
        }

        try {
            const navEntries = performance.getEntriesByType && performance.getEntriesByType('navigation');
            if (navEntries && navEntries.length > 0) {
                return navEntries[0].type === 'reload';
            }
        } catch (e) {}

        try {
            if (window.performance && window.performance.navigation) {
                return window.performance.navigation.type === 1; // TYPE_RELOAD
            }
        } catch (e) {}

        return false;
    }

    function saveChatHistoryToStorage() {
        try {
            const toSave = chatMessagesHistory.slice(-50);
            sessionStorage.setItem('ups_chatbot_active_messages', JSON.stringify(toSave));
        } catch (e) {}
    }

    function openChatbotPanel(focusInput = true) {
        const panel = document.getElementById('chatbot-panel');
        const fab = document.getElementById('chatbot-fab');
        if (!panel || !fab) return;
        chatbotIsOpen = true;
        if (typeof window.restoreChatbotPanelPosition === 'function') {
            window.restoreChatbotPanelPosition();
        }
        panel.classList.add('chatbot-open');
        fab.classList.add('chatbot-fab-hidden');
        sessionStorage.setItem('ups_chatbot_is_open', 'true');
        if (focusInput) {
            setTimeout(() => {
                const inp = document.getElementById('chatbot-input');
                if (inp) inp.focus();
            }, 300);
        }
    }

    function closeChatbotPanel() {
        const panel = document.getElementById('chatbot-panel');
        const fab = document.getElementById('chatbot-fab');
        if (!panel || !fab) return;
        chatbotIsOpen = false;
        panel.classList.remove('chatbot-open');
        fab.classList.remove('chatbot-fab-hidden');
        sessionStorage.setItem('ups_chatbot_is_open', 'false');
    }

    function toggleChatbot(focusInput = true) {
        if (chatbotIsOpen) {
            closeChatbotPanel();
        } else {
            openChatbotPanel(focusInput);
        }
    }

    // Modal lightbox gambar bukti transaksi
    window.openChatbotImageModal = function(src, caption) {
        if (!src) return;
        const modal = document.getElementById('chatbot-image-modal');
        const img = document.getElementById('chatbot-image-modal-img');
        const cap = document.getElementById('chatbot-image-modal-caption');
        if (!modal || !img) return;
        img.src = src;
        if (cap) {
            cap.innerText = caption || 'Pratinjau Foto Berkas';
        }
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeChatbotImageModal = function(event) {
        if (event && event.target && event.target.id !== 'chatbot-image-modal' && event.target.tagName !== 'BUTTON') {
            return;
        }
        const modal = document.getElementById('chatbot-image-modal');
        const img = document.getElementById('chatbot-image-modal-img');
        if (!modal) return;
        modal.style.display = 'none';
        if (img) img.src = '';
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('chatbot-image-modal');
            if (modal && modal.style.display === 'flex') {
                closeChatbotImageModal();
            }
        }
    });

    function loadChatHistoryFromStorage() {
        // KECUALI DI REFRESH: Jika halaman di-refresh (F5 / reload browser), reset chatbot!
        if (isManualPageRefresh()) {
            sessionStorage.removeItem('ups_chatbot_active_messages');
            sessionStorage.removeItem('ups_chatbot_is_open');
            chatMessagesHistory = [];
            try {
                fetch(CHATBOT_CLEAR_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                    },
                }).catch(() => {});
            } catch (e) {}
            return;
        }

        // JIKA PINDAH MENU / NAVIGASI: Pulihkan riwayat obrolan agar tidak ter-reset
        try {
            const saved = sessionStorage.getItem('ups_chatbot_active_messages');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    chatMessagesHistory = parsed;
                    parsed.forEach(item => {
                        if (item.type === 'user') {
                            renderUserMessageDom(item.text, item.time, item.senderName, item.senderInitials, item.senderRole, item.senderRoleLabel, item.imgUrl);
                        } else if (item.type === 'ai') {
                            renderAiMessageDom(item.text, item.isConfirmation, item.time);
                        }
                    });
                    scrollToBottom();
                }
            }
        } catch (e) {
            chatMessagesHistory = [];
        }

        // Pulihkan status buka/tutup chatbot saat berpindah menu
        const wasOpen = sessionStorage.getItem('ups_chatbot_is_open') === 'true' || sessionStorage.getItem('chatbot_keep_open_after_sync') === 'true';
        if (wasOpen && !chatbotIsOpen) {
            openChatbotPanel(false);
        }
        if (sessionStorage.getItem('chatbot_keep_open_after_sync') === 'true') {
            sessionStorage.removeItem('chatbot_keep_open_after_sync');
        }
    }

    function scrollToBottom() {
        const container = document.getElementById('chatbot-messages');
        if (!container) return;
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 50);
    }

    function getTimeString() {
        const now = new Date();
        return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
    }

    function renderUserMessageDom(text, timeStr, name, initials, role, roleLabel, imgUrl = null) {
        const container = document.getElementById('chatbot-messages');
        if (!container) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-msg chatbot-msg-user';

        let imgHtml = '';
        if (imgUrl) {
            imgHtml = `<div style="margin-bottom:6px;"><img src="${imgUrl}" style="max-width:180px; max-height:120px; border-radius:8px; object-fit:cover; border:1px solid rgba(255,255,255,0.3); display:block; cursor:pointer;" onclick="openChatbotImageModal(this.src, 'Foto Berkas yang Diunggah')" alt="Foto Bukti" title="Klik untuk melihat foto penuh"></div>`;
        }
        let cleanUserText = (text || '').trim();
        let textHtml = cleanUserText ? `<div style="white-space:pre-wrap; word-break:break-word;">${escapeHtml(cleanUserText)}</div>` : '';

        wrapper.innerHTML = `
            <div class="chatbot-msg-avatar chatbot-avatar-${role || CURRENT_USER_ROLE}" title="${escapeHtml(name || CURRENT_USER_NAME)} (${roleLabel || CURRENT_USER_ROLE_LABEL})">
                ${initials || CURRENT_USER_INITIALS}
            </div>
            <div style="max-width: 84%; display: flex; flex-direction: column; align-items: flex-end;">
                <div class="chatbot-msg-meta">
                    <span class="chatbot-msg-name">${escapeHtml(name || CURRENT_USER_NAME)}</span>
                    <span class="chatbot-msg-tag chatbot-tag-${role || CURRENT_USER_ROLE}">${roleLabel || CURRENT_USER_ROLE_LABEL}</span>
                </div>
                <div class="chatbot-msg-bubble">${imgHtml}${textHtml}</div>
                <span class="chatbot-msg-time">${timeStr || getTimeString()}</span>
            </div>
        `;
        container.appendChild(wrapper);
    }

    function addUserMessage(text, imgUrl = null) {
        const timeStr = getTimeString();
        renderUserMessageDom(text, timeStr, CURRENT_USER_NAME, CURRENT_USER_INITIALS, CURRENT_USER_ROLE, CURRENT_USER_ROLE_LABEL, imgUrl);
        chatMessagesHistory.push({
            type: 'user',
            text: text,
            imgUrl: imgUrl,
            time: timeStr,
            senderName: CURRENT_USER_NAME,
            senderInitials: CURRENT_USER_INITIALS,
            senderRole: CURRENT_USER_ROLE,
            senderRoleLabel: CURRENT_USER_ROLE_LABEL
        });
        saveChatHistoryToStorage();
        scrollToBottom();
    }

    function renderAiMessageDom(text, isConfirmation = false, timeStr = null) {
        const container = document.getElementById('chatbot-messages');
        if (!container) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-msg chatbot-msg-ai';

        let confirmHtml = '';
        if (isConfirmation || (text && text.includes('Konfirmasi') && text.includes('Ketik **Ya**'))) {
            confirmHtml = `
                <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" onclick="sendQuickMessage('Ya, konfirmasi')" 
                            style="background:#10b981; color:#fff; border:none; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; box-shadow:0 2px 6px rgba(16,185,129,0.3); transition:all 0.2s;"
                            onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                        ✓ Ya, Konfirmasi
                    </button>
                    <button type="button" onclick="sendQuickMessage('Batal')" 
                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:6px 12px; border-radius:8px; font-size:11.5px; font-weight:600; cursor:pointer; transition:all 0.2s;"
                            onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        ✕ Batal
                    </button>
                </div>
            `;
        }

        let copyWaHtml = '';
        if (text && (text.includes('PENGINGAT IURAN KAS') || text.includes('Daftar Mahasiswa Menunggak') || text.includes('Belum Melunasi') || text.includes('INFORMASI IURAN KAS'))) {
            const waMsgId = 'wa_msg_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
            window._waBroadcastStore = window._waBroadcastStore || {};
            window._waBroadcastStore[waMsgId] = text;
            copyWaHtml = `
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: flex-end;">
                    <button type="button" class="chatbot-copy-wa-btn" onclick="copyWaBroadcast(this, '${waMsgId}')" title="Salin pesan untuk WhatsApp">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span>Salin Pesan WA</span>
                    </button>
                </div>
            `;
        }

        wrapper.innerHTML = `
            <div class="chatbot-msg-avatar" title="Asisten AI Kelas Informatika">
                AI
            </div>
            <div style="max-width: 84%; display: flex; flex-direction: column; align-items: flex-start;">
                <div class="chatbot-msg-meta">
                    <span class="chatbot-msg-name">Asisten AI Kelas</span>
                    <span class="chatbot-msg-tag chatbot-tag-ai">Smart Bot</span>
                </div>
                <div class="chatbot-msg-bubble">${formatAiResponse(text)}${copyWaHtml}${confirmHtml}</div>
                <span class="chatbot-msg-time">${timeStr || getTimeString()}</span>
            </div>
        `;
        container.appendChild(wrapper);
    }

    function copyWaBroadcast(btn, id) {
        const text = (window._waBroadcastStore && window._waBroadcastStore[id]) ? window._waBroadcastStore[id] : '';
        if (!text) return;

        function showSuccess() {
            const originalContent = btn.innerHTML;
            btn.innerHTML = `
                <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span style="color:#15803d; font-weight:700;">✓ Pesan Disalin!</span>
            `;
            btn.style.borderColor = '#4ade80';
            btn.style.backgroundColor = '#dcfce7';
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.style.borderColor = '';
                btn.style.backgroundColor = '';
            }, 2500);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(showSuccess).catch(() => {
                fallbackCopyText(text, showSuccess);
            });
        } else {
            fallbackCopyText(text, showSuccess);
        }
    }

    function fallbackCopyText(text, successCb) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            if (successCb) successCb();
        } catch (e) {
            alert('Silakan salin teks secara manual.');
        }
        document.body.removeChild(ta);
    }

    function addAiMessage(text, isConfirmation = false) {
        const timeStr = getTimeString();
        renderAiMessageDom(text, isConfirmation, timeStr);
        chatMessagesHistory.push({
            type: 'ai',
            text: text,
            isConfirmation: Boolean(isConfirmation),
            time: timeStr
        });
        saveChatHistoryToStorage();
        scrollToBottom();
    }

    function showTypingIndicator() {
        const container = document.getElementById('chatbot-messages');
        const typing = document.createElement('div');
        typing.className = 'chatbot-msg chatbot-msg-ai';
        typing.id = 'chatbot-typing-indicator';
        typing.innerHTML = `
            <div class="chatbot-msg-avatar" title="Asisten AI Kelas">AI</div>
            <div style="max-width: 82%;">
                <div class="chatbot-msg-meta">
                    <span class="chatbot-msg-name">Asisten AI Kelas</span>
                    <span class="chatbot-msg-tag chatbot-tag-ai">Mengetik...</span>
                </div>
                <div class="chatbot-typing-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        `;
        container.appendChild(typing);
        scrollToBottom();
    }

    function removeTypingIndicator() {
        const el = document.getElementById('chatbot-typing-indicator');
        if (el) el.remove();
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function cleanMarkdownTables(rawText) {
        if (!rawText || rawText.indexOf('|') === -1) return rawText;

        const lines = rawText.split('\n');
        let result = [];
        let inTable = false;
        let headers = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();
            if (line.startsWith('|') && line.endsWith('|')) {
                let cells = line.split('|').map(c => c.trim()).filter((_, idx, arr) => idx > 0 && idx < arr.length - 1);
                // Check if divider row: |---|---|
                if (cells.every(c => /^[-:\s]+$/.test(c))) {
                    inTable = true;
                    continue;
                }
                if (!inTable) {
                    headers = cells;
                    inTable = true;
                } else {
                    let formattedParts = [];
                    for (let j = 0; j < cells.length; j++) {
                        let cVal = cells[j].replace(/<\s*br\s*\/?>/gi, ' · ').trim();
                        if (!cVal) continue;
                        if (j === 0) {
                            formattedParts.push('**' + cVal + '**');
                        } else if (headers[j]) {
                            formattedParts.push(headers[j] + ': ' + cVal);
                        } else {
                            formattedParts.push(cVal);
                        }
                    }
                    if (formattedParts.length > 0) {
                        result.push('• ' + formattedParts.join(' • '));
                    }
                }
            } else {
                inTable = false;
                result.push(lines[i]);
            }
        }
        return result.join('\n');
    }

    function formatAiResponse(text) {
        if (!text) return '';

        // 0. Bersihkan spasi kosong & baris baru berlebih di awal/akhir
        let cleanText = text.trim();

        // 1. Bersihkan tabel markdown (simbol pipa |) ke format teks/bullet yang rapi
        cleanText = cleanMarkdownTables(cleanText);

        // 2. Normalisasi tag HTML mentah seperti <br> atau <br/>
        cleanText = cleanText.replace(/<\s*br\s*\/?>/gi, '\n');

        // 3. Bersihkan garis horizontal --- atau ===
        cleanText = cleanText.replace(/^[ \t]*[-=_]{3,}[ \t]*$/gm, '');

        // 4. Ubah heading markdown ### atau ## menjadi teks tebal biasa
        cleanText = cleanText.replace(/^#{1,6}\s*(.+)$/gm, '**$1**');

        // Batasi rentetan baris baru beruntun (maksimal 2 baris baru)
        cleanText = cleanText.replace(/\n{3,}/g, '\n\n');

        // 5. Escape HTML agar aman
        let html = escapeHtml(cleanText);

        // 6. Bold: **text** atau __text__
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__(.*?)__/g, '<strong>$1</strong>');

        // 7. Italic: *text* atau _text_
        html = html.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');

        // 8. Inline code: `text`
        html = html.replace(/`(.*?)`/g, '<code>$1</code>');

        // 8.5. Markdown Links & Download Buttons: [text](url)
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function(match, linkText, linkUrl) {
            let cleanUrl = linkUrl.replace(/&amp;/g, '&');
            if (cleanUrl.indexOf('export-excel') !== -1 || cleanUrl.indexOf('export') !== -1) {
                return `<a href="${cleanUrl}" target="_blank" class="chatbot-excel-download-btn"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg> ${linkText}</a>`;
            }
            return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" style="color:#2563eb; text-decoration:underline; font-weight:600;">${linkText}</a>`;
        });

        // 9. Unordered lists: baris diawali - atau •
        html = html.replace(/^[-•]\s+(.+)/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*<\/li>\n?)+/g, '<ul style="margin:4px 0; padding-left:18px;">$&</ul>');

        // 10. Numbered lists: baris diawali angka.
        html = html.replace(/^\d+\.\s+(.+)/gm, '<li>$1</li>');

        // 11. Line breaks
        html = html.replace(/\n/g, '<br>');

        // 12. Rapikan spasi <br> di dalam dan di sekitar list
        html = html.replace(/<\/li><br>/g, '</li>');
        html = html.replace(/<br><li>/g, '<li>');
        html = html.replace(/<br><\/ul>/g, '</ul>');
        html = html.replace(/<br><ul>/g, '<ul>');
        html = html.replace(/(<br\s*\/?>\s*){3,}/gi, '<br><br>');

        // 13. Hapus tag <br> yang menempel di awal atau akhir output
        html = html.replace(/^(<br\s*\/?>\s*)+|(<br\s*\/?>\s*)+$/gi, '');

        return html.trim();
    }

    async function sendChatMessage() {
        if (chatbotIsSending) return;

        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();

        if (!message) return;

        input.value = '';

        chatbotIsSending = true;
        document.getElementById('chatbot-send-btn').disabled = true;

        addUserMessage(message);
        showTypingIndicator();

        try {
            let response = await fetch(CHATBOT_CHAT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: message }),
            });

            // Mitigasi 419: Auto-Refresh CSRF Token & Retry Sekali
            if (response.status === 419) {
                try {
                    const tokenRes = await fetch('{{ route("csrf.token") }}', { headers: { 'Accept': 'application/json' } });
                    const tokenData = await tokenRes.json();
                    if (tokenData && tokenData.token) {
                        const freshToken = tokenData.token;
                        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', freshToken);
                        document.querySelectorAll('input[name="_token"]').forEach(input => input.value = freshToken);

                        response = await fetch(CHATBOT_CHAT_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': freshToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ message: message }),
                        });
                    }
                } catch (retryErr) {}
            }

            const data = await response.json();
            removeTypingIndicator();

            // Penanganan Khusus Status Blokir Sementara (Batas 3x Toxic/Vulgar)
            if (data.is_blocked) {
                addAiMessage(data.reply);
                const chatInput = document.getElementById('chatbot-input');
                const sendBtn = document.getElementById('chatbot-send-btn');
                if (chatInput) {
                    chatInput.disabled = true;
                    chatInput.placeholder = 'Akses Chatbot diblokir sementara (15 menit)...';
                    chatInput.style.backgroundColor = '#fef2f2';
                    chatInput.style.borderColor = '#fca5a5';
                    chatInput.style.color = '#991b1b';
                }
                if (sendBtn) {
                    sendBtn.disabled = true;
                    sendBtn.style.opacity = '0.4';
                    sendBtn.style.cursor = 'not-allowed';
                }
                return;
            }

            if (data.success) {
                addAiMessage(data.reply, data.is_confirmation);

                // SINKRONISASI REAL-TIME: Jika aksi CRUD berhasil dieksekusi,
                // sinkronkan halaman dan seluruh tab/role pengguna secara otomatis
                if (data.action_executed) {
                    broadcastClassDataSync();
                    sessionStorage.setItem('chatbot_keep_open_after_sync', 'true');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1800);
                }
            } else {
                addAiMessage(data.reply || 'Maaf, terjadi kesalahan. Silakan coba lagi.');
            }
        } catch (error) {
            removeTypingIndicator();
            addAiMessage('⚠️ Gagal terhubung ke server. Pastikan koneksi internet Anda stabil dan coba lagi.');
        } finally {
            chatbotIsSending = false;
            const chatInput = document.getElementById('chatbot-input');
            const sendBtn = document.getElementById('chatbot-send-btn');
            if (chatInput && !chatInput.disabled) {
                chatInput.focus();
            }
            if (sendBtn && (!chatInput || !chatInput.disabled)) {
                sendBtn.disabled = false;
            }
        }
    }

    function sendQuickMessage(text) {
        document.getElementById('chatbot-input').value = text;
        sendChatMessage();
    }

    async function clearChatHistory() {
        if (!confirm('Hapus semua riwayat percakapan dengan AI?')) return;

        chatMessagesHistory = [];
        sessionStorage.removeItem('ups_chatbot_active_messages');

        try {
            await fetch(CHATBOT_CLEAR_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
            });
        } catch (e) {
            // ignore
        }

        // Reset UI
        const container = document.getElementById('chatbot-messages');
        if (container) {
            const contextActionsEl = document.getElementById('chatbot-context-quick-actions');
            const actionsHtml = contextActionsEl 
                ? contextActionsEl.outerHTML 
                : `<div class="chatbot-quick-actions"><button class="chatbot-quick-btn" onclick="sendQuickMessage('Berapa saldo kas kelas saat ini?')">💰 Saldo Kas</button></div>`;

            container.innerHTML = `
                <div class="chatbot-welcome">
                    <div class="chatbot-welcome-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h4>Percakapan Direset</h4>
                    <p>Riwayat percakapan telah dihapus. Silakan mulai percakapan baru dengan topik seputar halaman ini:</p>
                    ${actionsHtml}
                </div>
            `;
        }
    }

    // Broadcast sinkronisasi lintas-tab dan lintas-role
    function broadcastClassDataSync() {
        try {
            if (typeof window.BroadcastChannel !== 'undefined') {
                const bc = new BroadcastChannel('ups_class_data_sync_channel');
                bc.postMessage({ type: 'DATA_SYNC', timestamp: Date.now() });
            }
            localStorage.setItem('ups_class_data_sync_timestamp', Date.now().toString());
        } catch (e) {}
    }

    // Inisialisasi dan pulihkan status chatbot secara asinkron tanpa memblokir thread render utama
    function initChatbotState() {
        loadChatHistoryFromStorage();
    }
    if ('requestIdleCallback' in window) {
        requestIdleCallback(initChatbotState, { timeout: 1200 });
    } else {
        window.addEventListener('load', function() {
            setTimeout(initChatbotState, 150);
        });
    }

    // Close chatbot with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && chatbotIsOpen) {
            toggleChatbot();
        }
    });

    // Tombol apung geser (draggable FAB kompatibel mouse & touch)
    (function initDraggableFab() {
        const fab = document.getElementById('chatbot-fab');
        if (!fab) return;

        let isDragging = false;
        let hasMoved = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;
        const dragThreshold = 6; // px to differentiate click vs drag

        function clamp(val, min, max) {
            return Math.max(min, Math.min(val, max));
        }

        function getClientPos(e) {
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
            if (e.changedTouches && e.changedTouches.length > 0) {
                return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
            }
            return { x: e.clientX, y: e.clientY };
        }

        function restoreFabPosition() {
            try {
                const saved = localStorage.getItem('ups_chatbot_fab_position');
                if (saved) {
                    const pos = JSON.parse(saved);
                    const margin = 12;
                    const maxLeft = window.innerWidth - (fab.offsetWidth || 56) - margin;
                    const maxTop = window.innerHeight - (fab.offsetHeight || 56) - margin;

                    if (typeof pos.x === 'number' && typeof pos.y === 'number') {
                        fab.style.left = clamp(pos.x, margin, maxLeft) + 'px';
                        fab.style.top = clamp(pos.y, margin, maxTop) + 'px';
                        fab.style.right = 'auto';
                        fab.style.bottom = 'auto';
                    }
                }
            } catch (err) {}
        }

        window.addEventListener('resize', function() {
            if (fab.style.left && fab.style.top) {
                const margin = 12;
                const curLeft = parseFloat(fab.style.left) || 0;
                const curTop = parseFloat(fab.style.top) || 0;
                const maxLeft = window.innerWidth - (fab.offsetWidth || 56) - margin;
                const maxTop = window.innerHeight - (fab.offsetHeight || 56) - margin;
                fab.style.left = clamp(curLeft, margin, maxLeft) + 'px';
                fab.style.top = clamp(curTop, margin, maxTop) + 'px';
            }
        });

        function onDragStart(e) {
            // Ignore right clicks
            if (e.type === 'mousedown' && e.button !== 0) return;

            const pos = getClientPos(e);
            startX = pos.x;
            startY = pos.y;
            hasMoved = false;

            const rect = fab.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;

            isDragging = true;
            fab.classList.add('is-dragging');

            document.addEventListener('mousemove', onDragMove, { passive: false });
            document.addEventListener('mouseup', onDragEnd);
            document.addEventListener('touchmove', onDragMove, { passive: false });
            document.addEventListener('touchend', onDragEnd);
            document.addEventListener('touchcancel', onDragEnd);
        }

        function onDragMove(e) {
            if (!isDragging) return;

            const pos = getClientPos(e);
            const dx = pos.x - startX;
            const dy = pos.y - startY;

            if (Math.abs(dx) > dragThreshold || Math.abs(dy) > dragThreshold) {
                hasMoved = true;
            }

            if (hasMoved) {
                if (e.cancelable) e.preventDefault(); // prevent viewport scrolling during touch drag

                const margin = 8;
                const maxLeft = window.innerWidth - (fab.offsetWidth || 56) - margin;
                const maxTop = window.innerHeight - (fab.offsetHeight || 56) - margin;

                const newLeft = clamp(initialLeft + dx, margin, maxLeft);
                const newTop = clamp(initialTop + dy, margin, maxTop);

                fab.style.left = newLeft + 'px';
                fab.style.top = newTop + 'px';
                fab.style.right = 'auto';
                fab.style.bottom = 'auto';
            }
        }

        function onDragEnd(e) {
            if (!isDragging) return;
            isDragging = false;
            fab.classList.remove('is-dragging');

            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', onDragEnd);
            document.removeEventListener('touchmove', onDragMove);
            document.removeEventListener('touchend', onDragEnd);
            document.removeEventListener('touchcancel', onDragEnd);

            if (hasMoved) {
                // Save final position to localStorage
                const rect = fab.getBoundingClientRect();
                try {
                    localStorage.setItem('ups_chatbot_fab_position', JSON.stringify({
                        x: Math.round(rect.left),
                        y: Math.round(rect.top)
                    }));
                } catch (err) {}
            } else {
                // Regular tap or click without dragging
                toggleChatbot();
            }
        }

        fab.addEventListener('mousedown', onDragStart);
        fab.addEventListener('touchstart', onDragStart, { passive: true });

        // Keyboard accessibility
        fab.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleChatbot();
            }
        });

        // Initialize position
        restoreFabPosition();
    })();

    // Panel chatbot geser (draggable panel kompatibel mouse & touch di HP/Tablet/Desktop)
    (function initDraggablePanel() {
        const panel = document.getElementById('chatbot-panel');
        const header = document.getElementById('chatbot-header') || (panel ? panel.querySelector('.chatbot-header') : null);
        if (!panel || !header) return;

        let isDragging = false;
        let hasMoved = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;
        let currentLeft = 0;
        let currentTop = 0;
        const dragThreshold = 4;

        function clamp(val, min, max) {
            return Math.max(min, Math.min(val, max));
        }

        function getClientPos(e) {
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
            if (e.changedTouches && e.changedTouches.length > 0) {
                return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
            }
            return { x: e.clientX, y: e.clientY };
        }

        function restorePanelPosition() {
            try {
                const saved = localStorage.getItem('ups_chatbot_panel_position');
                if (saved) {
                    const pos = JSON.parse(saved);
                    const margin = 8;
                    const panelWidth = panel.offsetWidth || (window.innerWidth <= 640 ? window.innerWidth - 24 : 400);
                    const panelHeight = panel.offsetHeight || 480;
                    const maxLeft = Math.max(margin, window.innerWidth - panelWidth - margin);
                    const maxTop = Math.max(margin, window.innerHeight - panelHeight - margin);

                    if (typeof pos.x === 'number' && typeof pos.y === 'number') {
                        panel.classList.add('is-positioned');
                        panel.style.left = clamp(pos.x, margin, maxLeft) + 'px';
                        panel.style.top = clamp(pos.y, margin, maxTop) + 'px';
                        panel.style.right = 'auto';
                        panel.style.bottom = 'auto';
                    }
                }
            } catch (err) {}
        }
        window.restoreChatbotPanelPosition = restorePanelPosition;

        function onHeaderDragStart(e) {
            // Abaikan klik pada tombol di header (seperti tombol hapus riwayat & tutup)
            if (e.target.closest('button')) return;
            if (e.type === 'mousedown' && e.button !== 0) return;

            const pos = getClientPos(e);
            startX = pos.x;
            startY = pos.y;
            hasMoved = false;

            const rect = panel.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;
            currentLeft = initialLeft;
            currentTop = initialTop;

            isDragging = true;
            panel.classList.add('panel-is-dragging');
            panel.style.transition = 'none';

            document.addEventListener('mousemove', onHeaderDragMove, { passive: false });
            document.addEventListener('mouseup', onHeaderDragEnd);
            document.addEventListener('touchmove', onHeaderDragMove, { passive: false });
            document.addEventListener('touchend', onHeaderDragEnd);
            document.addEventListener('touchcancel', onHeaderDragEnd);
        }

        function onHeaderDragMove(e) {
            if (!isDragging) return;

            const pos = getClientPos(e);
            const dx = pos.x - startX;
            const dy = pos.y - startY;

            if (Math.abs(dx) > dragThreshold || Math.abs(dy) > dragThreshold) {
                hasMoved = true;
            }

            if (hasMoved) {
                if (e.cancelable) e.preventDefault();

                panel.classList.add('is-positioned');
                const margin = 8;
                const panelWidth = panel.offsetWidth || (window.innerWidth <= 640 ? window.innerWidth - 24 : 400);
                const panelHeight = panel.offsetHeight || 480;
                const maxLeft = Math.max(margin, window.innerWidth - panelWidth - margin);
                const maxTop = Math.max(margin, window.innerHeight - panelHeight - margin);

                currentLeft = clamp(initialLeft + dx, margin, maxLeft);
                currentTop = clamp(initialTop + dy, margin, maxTop);

                panel.style.left = currentLeft + 'px';
                panel.style.top = currentTop + 'px';
                panel.style.right = 'auto';
                panel.style.bottom = 'auto';
            }
        }

        function onHeaderDragEnd() {
            if (!isDragging) return;
            isDragging = false;
            panel.classList.remove('panel-is-dragging');
            panel.style.transition = '';

            document.removeEventListener('mousemove', onHeaderDragMove);
            document.removeEventListener('mouseup', onHeaderDragEnd);
            document.removeEventListener('touchmove', onHeaderDragMove);
            document.removeEventListener('touchend', onHeaderDragEnd);
            document.removeEventListener('touchcancel', onHeaderDragEnd);

            if (hasMoved) {
                try {
                    localStorage.setItem('ups_chatbot_panel_position', JSON.stringify({
                        x: Math.round(currentLeft),
                        y: Math.round(currentTop)
                    }));
                } catch (err) {}
            }
        }

        header.addEventListener('mousedown', onHeaderDragStart);
        header.addEventListener('touchstart', onHeaderDragStart, { passive: true });

        window.addEventListener('resize', function() {
            if (panel.style.left && panel.style.top) {
                const margin = 8;
                const curLeft = parseFloat(panel.style.left) || 0;
                const curTop = parseFloat(panel.style.top) || 0;
                const panelWidth = panel.offsetWidth || (window.innerWidth <= 640 ? window.innerWidth - 24 : 400);
                const panelHeight = panel.offsetHeight || 480;
                const maxLeft = Math.max(margin, window.innerWidth - panelWidth - margin);
                const maxTop = Math.max(margin, window.innerHeight - panelHeight - margin);
                panel.style.left = clamp(curLeft, margin, maxLeft) + 'px';
                panel.style.top = clamp(curTop, margin, maxTop) + 'px';
            }
        });

        restorePanelPosition();
    })();
</script>
@endif

