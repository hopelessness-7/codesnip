import { WebSocket, WebSocketServer } from 'ws';

const LEVEL_ORDER = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

function levelRank(level) {
    const i = LEVEL_ORDER.indexOf(level);

    return i === -1 ? 0 : i;
}

/**
 * Parse a single Laravel log line. Returns null for empty lines.
 * Non-matching lines are returned as raw entries so stack traces are not lost.
 */
export function parseLaravelLogLine(line) {
    if (!line || !line.trim()) {
        return null;
    }

    const match = line.match(
        /^\[(?<date>[^\]]+)\]\s+(?<channel>[\w-]+)\.(?<level>\w+):\s+(?<message>.+?)(?:\s+(?<context>\{.*\}))?$/,
    );

    if (!match || !match.groups) {
        return { type: 'raw', text: line };
    }

    const { date, channel, level, message, context } = match.groups;
    let parsedContext = null;

    if (context) {
        try {
            parsedContext = JSON.parse(context);
        } catch {
            parsedContext = context;
        }
    }

    return {
        type: 'laravel',
        date,
        channel,
        level,
        message: message.trim(),
        context: parsedContext,
        isError: ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(level),
    };
}

function minLevelFromEnv() {
    return (process.env.LOG_MIN_LEVEL || process.env.LOG_LEVEL || 'DEBUG').toUpperCase();
}

function shouldInclude(entry) {
    if (entry.type === 'raw') {
        return true;
    }

    const minLevel = minLevelFromEnv();

    return levelRank(entry.level) >= levelRank(minLevel);
}

/**
 * Format one parsed entry for the WebSocket payload.
 * Laravel: [file.log][2026-05-12 10:15:30] channel.level: message
 * Raw:     [file.log] line text
 */
export function formatLogEntry(entry, fileLabel) {
    if (entry.type === 'raw') {
        return `[${fileLabel}] ${entry.text}`;
    }

    let text = `[${fileLabel}][${entry.date}] ${entry.channel}.${entry.level}: ${entry.message}`;

    if (entry.isError && entry.context?.exception) {
        const msg = entry.context.exception.message || 'No message';
        text += ` | ${msg}`;
    }

    return text;
}

/**
 * @param {string} fileLabel — basename of the log file (e.g. laravel-2026-05-12.log)
 * @param {string[]} lines
 * @returns {string[]}
 */
export function linesToPayloadStrings(fileLabel, lines) {
    const out = [];

    for (const line of lines) {
        const entry = parseLaravelLogLine(line);

        if (!entry) {
            continue;
        }

        if (!shouldInclude(entry)) {
            continue;
        }

        out.push(formatLogEntry(entry, fileLabel));
    }

    return out;
}

/**
 * WebSocket hub: token auth, client set, JSON broadcast.
 */
export function createBroadcastHub({ wsToken }) {
    const clients = new Set();

    function verifyToken(req) {
        const headerToken = req.headers['x-log-stream-token'];
        if (typeof headerToken === 'string' && headerToken === wsToken) {
            return true;
        }

        const host = req.headers.host ? `http://${req.headers.host}` : 'http://localhost';
        const url = new URL(req.url ?? '/', host);

        return url.searchParams.get('token') === wsToken;
    }

    function attachWebSocketServer(wss) {
        wss.on('connection', (ws, req) => {
            if (!verifyToken(req)) {
                console.warn('[broadcast] WebSocket rejected: invalid or missing token');
                ws.close(4001, 'Invalid token');

                return;
            }

            clients.add(ws);
            console.log(`[broadcast] Client connected (${clients.size} total)`);

            ws.on('close', () => {
                clients.delete(ws);
                console.log(`[broadcast] Client disconnected (${clients.size} left)`);
            });

            ws.on('error', (err) => {
                console.error('[broadcast] Socket error:', err.message);
                clients.delete(ws);
            });
        });
    }

    function broadcastLines(fileLabel, lines) {
        const messages = linesToPayloadStrings(fileLabel, lines);

        if (messages.length === 0) {
            return;
        }

        const payload = JSON.stringify(messages);

        for (const client of clients) {
            if (client.readyState !== WebSocket.OPEN) {
                clients.delete(client);
                continue;
            }

            try {
                client.send(payload);
            } catch (err) {
                console.error('[broadcast] Send failed:', err.message);
                clients.delete(client);
            }
        }
    }

    return {
        attachWebSocketServer,
        broadcastLines,
        getClientCount: () => clients.size,
    };
}
