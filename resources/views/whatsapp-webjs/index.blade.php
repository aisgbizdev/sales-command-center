@extends('layouts.app')

@section('content')
<style>
    html,
    body {
        background: #020202 !important;
        background-image: none !important;
    }

    .wa-panel {
        display: grid;
        gap: 12px;
    }
    .wa-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: 320px minmax(0, 1fr);
    }
    .wa-list {
        max-height: 420px;
        overflow: auto;
        display: grid;
        gap: 8px;
    }
    .wa-item-btn {
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.04);
        color: #d9c995;
        border-radius: 12px;
        padding: 8px 10px;
        cursor: pointer;
    }
    .wa-messages {
        max-height: 420px;
        overflow: auto;
        display: grid;
        gap: 8px;
    }
    .wa-msg {
        border-radius: 12px;
        padding: 8px;
        max-width: 80%;
        font-size: 14px;
    }
    .wa-msg.me { margin-left: auto; background: rgba(247, 199, 68, 0.14); }
    .wa-msg.them { margin-right: auto; background: rgba(255, 255, 255, 0.08); }
    #qr {
        width: 280px;
        height: 280px;
        object-fit: contain;
        background: #fff;
        border-radius: 10px;
    }
    @media (max-width: 960px) {
        .wa-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wa-panel">
    <section class="card">
        <div class="section-head">
            <div>
                <h1 class="page-title">WhatsApp WebJS Testing</h1>
                <p class="page-subtitle">Base URL: {{ $baseUrl }}</p>
            </div>
        </div>
        <div class="row">
            <button type="button" class="btn" onclick="refreshState()">Refresh State</button>
        </div>
    </section>

    <section class="wa-grid">
        <article class="card stack">
            <h3 class="form-section-title">Session</h3>
            <div id="state" class="helper-text">Loading...</div>
            <img id="qr" alt="QR">
            <small class="helper-text">Jika belum ready, scan QR ini dengan WhatsApp di HP.</small>
            <button type="button" class="btn" onclick="loadChats()">Load Chats</button>
        </article>

        <article class="card stack">
            <h3 class="form-section-title">Chats</h3>
            <div class="wa-list" id="chatList"></div>
        </article>
    </section>

    <section class="wa-grid">
        <article class="card stack">
            <h3 class="form-section-title">Messages</h3>
            <div class="wa-messages" id="messages"></div>
        </article>
        <article class="card stack">
            <h3 class="form-section-title">Send Message</h3>
            <label>To (628xxx atau chat id @c.us)
                <input id="to" placeholder="628xxxxxx">
            </label>
            <label>Message
                <textarea id="message" rows="6" placeholder="Tulis pesan..."></textarea>
            </label>
            <button type="button" class="btn" onclick="sendMessage()">Send</button>
            <small id="sendResult" class="helper-text"></small>
        </article>
    </section>
</div>

<script>
    const baseUrl = @json($baseUrl);
    let selectedChatId = null;
    let autoRetryTimer = null;
    let chatsPollTimer = null;
    let messagesPollTimer = null;
    let isLoadingChats = false;
    let isLoadingMessages = false;

    async function api(path, options = {}) {
        const response = await fetch(`${baseUrl}${path}`, options);
        const raw = await response.text();
        let data = null;
        try {
            data = raw ? JSON.parse(raw) : {};
        } catch (_e) {
            if (!response.ok) {
                throw new Error(raw.slice(0, 200) || `HTTP ${response.status}`);
            }
            data = {};
        }
        if (!response.ok) throw new Error(data.message || `HTTP ${response.status}`);
        return data;
    }

    async function refreshState() {
        try {
            const health = await api('/health');
            const stateEl = document.getElementById('state');
            if (health.ready) {
                stateEl.innerText = 'Status: Connected';
            } else if (health.hasQr) {
                stateEl.innerText = 'Status: Menunggu scan QR';
            } else {
                stateEl.innerText = 'Status: Starting...';
            }

            const qr = await api('/qr');
            const qrEl = document.getElementById('qr');
            if (health.ready) {
                qrEl.style.display = 'none';
                qrEl.src = '';
            } else {
                qrEl.style.display = 'block';
                qrEl.src = qr.qr || '';
            }
            if (!health.ready || health.hasQr) {
                queueRefresh(3000);
            }
        } catch (e) {
            document.getElementById('state').innerText = `error: ${e.message}`;
            queueRefresh(3000);
        }
    }

    function queueRefresh(ms = 3000) {
        if (autoRetryTimer) {
            clearTimeout(autoRetryTimer);
        }
        autoRetryTimer = setTimeout(() => {
            refreshState();
        }, ms);
    }

    async function loadChats() {
        if (isLoadingChats) return;
        isLoadingChats = true;
        try {
            const data = await api('/chats');
            const list = document.getElementById('chatList');
            list.innerHTML = '';
            data.items.forEach((chat) => {
                const btn = document.createElement('button');
                btn.className = 'wa-item-btn';
                btn.type = 'button';
                btn.innerText = `${chat.name} (${chat.unreadCount})`;
                btn.onclick = () => loadMessages(chat.id);
                list.appendChild(btn);
            });
        } catch (e) {
            document.getElementById('state').innerText = `error: ${e.message}`;
        } finally {
            isLoadingChats = false;
        }
    }

    async function loadMessages(chatId) {
        selectedChatId = chatId;
        document.getElementById('to').value = chatId;
        if (isLoadingMessages) return;
        isLoadingMessages = true;
        try {
            const data = await api(`/messages/${encodeURIComponent(chatId)}`);
            const box = document.getElementById('messages');
            const wasNearBottom = (box.scrollTop + box.clientHeight) >= (box.scrollHeight - 40);
            box.innerHTML = '';
            data.items.forEach((item) => {
                const row = document.createElement('div');
                row.className = `wa-msg ${item.fromMe ? 'me' : 'them'}`;
                const time = item.timestamp ? new Date(item.timestamp * 1000).toLocaleString() : '-';
                row.innerText = `${item.body}\n${time}`;
                box.appendChild(row);
            });
            if (wasNearBottom) {
                box.scrollTop = box.scrollHeight;
            }
        } finally {
            isLoadingMessages = false;
        }
    }

    async function sendMessage() {
        const to = document.getElementById('to').value.trim();
        const message = document.getElementById('message').value.trim();
        try {
            const result = await api('/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ to, message }),
            });
            document.getElementById('sendResult').innerText = `Sent: ${result.id}`;
            document.getElementById('message').value = '';
            if (selectedChatId) loadMessages(selectedChatId);
        } catch (e) {
            document.getElementById('sendResult').innerText = `Error: ${e.message}`;
        }
    }

    refreshState();
    startAutoPolling();

    function startAutoPolling() {
        if (chatsPollTimer) clearInterval(chatsPollTimer);
        if (messagesPollTimer) clearInterval(messagesPollTimer);

        chatsPollTimer = setInterval(() => {
            loadChats();
        }, 5000);

        messagesPollTimer = setInterval(() => {
            if (selectedChatId) {
                loadMessages(selectedChatId);
            }
        }, 2500);
    }
</script>
@endsection
