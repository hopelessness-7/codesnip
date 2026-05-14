import fs from 'node:fs';
import http from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { WebSocketServer } from 'ws';

import { createBroadcastHub } from './broadcast.js';
import { loadEnvFile } from './load-env.js';
import { resolveWatchConfig, startMultiTail, startTail } from './tail-log.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

loadEnvFile(path.join(__dirname, '..', '.env'));

const WS_PORT = Number.parseInt(process.env.WS_PORT ?? '8081', 10);
const WS_TOKEN = process.env.WS_TOKEN ?? 'dev-secret-token';

const watchConfig = resolveWatchConfig();

const hub = createBroadcastHub({ wsToken: WS_TOKEN });

const server = http.createServer((req, res) => {
    const urlPath = (req.url ?? '/').split('?')[0];

    if (req.method === 'GET' && (urlPath === '/' || urlPath === '/index.html')) {
        const htmlPath = path.join(__dirname, '..', 'public', 'index.html');

        if (fs.existsSync(htmlPath)) {
            const html = fs.readFileSync(htmlPath, 'utf8');
            res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end(html);

            return;
        }
    }

    res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('Not Found');
});

const wss = new WebSocketServer({ server });
hub.attachWebSocketServer(wss);

const onFileLines = (label, lines) => hub.broadcastLines(label, lines);

if (watchConfig.mode === 'single') {
    startTail({
        filePath: watchConfig.filePath,
        fileLabel: watchConfig.fileLabel,
        onFileLines,
    });
} else {
    startMultiTail({
        logDir: watchConfig.logDir,
        filePattern: watchConfig.filePattern,
        explicitNames: watchConfig.explicitNames,
        onFileLines,
    });
}

server.listen(WS_PORT, '0.0.0.0', () => {
    console.log(`[log-stream] HTTP + WS on port ${WS_PORT}`);

    if (watchConfig.mode === 'single') {
        console.log(`[log-stream] Mode: single file → ${watchConfig.filePath}`);
    } else {
        console.log(
            `[log-stream] Mode: directory ${watchConfig.logDir} (pattern: ${watchConfig.explicitNames?.join(', ') || watchConfig.filePattern})`,
        );
    }

    console.log(`[log-stream] Open http://localhost:${WS_PORT}/ (WebSocket same host/port)`);
});
