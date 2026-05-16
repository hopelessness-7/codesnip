/**
 * Live log panel on the dashboard — WebSocket via nginx (/ws/logs), no token in the browser URL.
 */

const RECONNECT_MS = 4000;
const MAX_LINES_DEFAULT = 400;

let socket = null;
let reconnectTimer = null;
let paused = false;

function wsUrlFromPath(wsPath) {
    const proto = window.location.protocol === 'https:' ? 'wss:' : 'ws:';

    return `${proto}//${window.location.host}${wsPath}`;
}

function levelClass(text) {
    if (/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/.test(text)) {
        return 'text-rose-400';
    }

    if (/\.WARNING:/.test(text)) {
        return 'text-amber-400';
    }

    if (/\.INFO:/.test(text)) {
        return 'text-sky-400/90';
    }

    return 'text-zinc-300';
}

function trimLogDom(logEl, maxLines) {
    while (logEl.childElementCount > maxLines) {
        logEl.removeChild(logEl.firstElementChild);
    }
}

function appendLines(logEl, messages, maxLines) {
    for (const text of messages) {
        const line = document.createElement('div');
        line.className = `font-mono text-xs leading-relaxed ${levelClass(text)}`;
        line.textContent = text;
        logEl.appendChild(line);
    }

    trimLogDom(logEl, maxLines);

    if (!paused) {
        logEl.scrollTop = logEl.scrollHeight;
    }
}

function setStatus(statusEl, text, tone = 'muted') {
    statusEl.textContent = text;
    statusEl.dataset.tone = tone;
}

function disconnect() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }

    if (socket) {
        socket.close();
        socket = null;
    }
}

function scheduleReconnect(panel) {
    if (reconnectTimer) {
        return;
    }

    reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        connect(panel);
    }, RECONNECT_MS);
}

function connect(panel) {
    const wsPath = panel.dataset.wsPath;
    const maxLines = Number.parseInt(panel.dataset.maxLines ?? String(MAX_LINES_DEFAULT), 10) || MAX_LINES_DEFAULT;
    const statusEl = panel.querySelector('[data-log-status]');
    const logEl = panel.querySelector('[data-log-output]');

    if (!wsPath || !statusEl || !logEl) {
        return;
    }

    disconnect();

    const url = wsUrlFromPath(wsPath);
    setStatus(statusEl, statusEl.dataset.connecting ?? 'Connecting…');

    socket = new WebSocket(url);

    socket.addEventListener('open', () => {
        setStatus(statusEl, statusEl.dataset.connected ?? 'Connected', 'ok');
    });

    socket.addEventListener('message', (event) => {
        let messages;

        try {
            messages = JSON.parse(event.data);
        } catch {
            appendLines(logEl, [String(event.data)], maxLines);

            return;
        }

        if (Array.isArray(messages) && messages.length > 0) {
            appendLines(logEl, messages, maxLines);
        }
    });

    socket.addEventListener('close', (event) => {
        setStatus(
            statusEl,
            event.code === 4001
                ? (statusEl.dataset.denied ?? 'Access denied (invalid token on server)')
                : `${statusEl.dataset.disconnected ?? 'Disconnected'} (${event.code})`,
            'warn',
        );
        scheduleReconnect(panel);
    });

    socket.addEventListener('error', () => {
        setStatus(statusEl, statusEl.dataset.error ?? 'WebSocket error', 'warn');
    });
}

function bindControls(panel) {
    const clearBtn = panel.querySelector('[data-log-clear]');
    const pauseBtn = panel.querySelector('[data-log-pause]');
    const logEl = panel.querySelector('[data-log-output]');

    clearBtn?.addEventListener('click', () => {
        if (logEl) {
            logEl.replaceChildren();
        }
    });

    pauseBtn?.addEventListener('click', () => {
        paused = !paused;
        pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        pauseBtn.textContent = paused
            ? (pauseBtn.dataset.resumeLabel ?? 'Resume scroll')
            : (pauseBtn.dataset.pauseLabel ?? 'Pause scroll');
    });
}

function initLogStreamPanel() {
    const panel = document.getElementById('log-stream-panel');

    if (!panel || panel.dataset.logStreamInit === '1') {
        return;
    }

    panel.dataset.logStreamInit = '1';
    bindControls(panel);
    connect(panel);
}

function teardownLogStream() {
    disconnect();
    paused = false;

    const panel = document.getElementById('log-stream-panel');

    if (panel) {
        panel.dataset.logStreamInit = '0';
    }
}

document.addEventListener('DOMContentLoaded', initLogStreamPanel);

document.addEventListener('livewire:navigated', () => {
    if (document.getElementById('log-stream-panel')) {
        const panel = document.getElementById('log-stream-panel');

        if (panel?.dataset.logStreamInit !== '1') {
            initLogStreamPanel();
        }
    } else {
        teardownLogStream();
    }
});

window.addEventListener('beforeunload', teardownLogStream);
