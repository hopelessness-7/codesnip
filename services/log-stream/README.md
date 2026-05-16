# Log stream service

Tails Laravel (and other) log files under `LOG_DIR` and pushes new lines to browsers over **WebSocket**, with a tiny **HTTP** page for testing.

## Local run

From this directory:

```bash
cp .env.example .env   # optional; defaults match multi-file *.log
npm install
npm run start
```

Open **http://localhost:8081/** (same host/port for WebSocket) — token is injected by the Node server for this debug page.

**Dashboard (recommended):** open the app at `http://localhost/dashboard` while logged in. The browser connects to **`ws://localhost/ws/logs`** (no token in the URL). nginx checks Laravel session, then proxies to log-stream with header `X-Log-Stream-Token`.

Direct `:8081` access still accepts `?token=` for debugging.

`npm run dev` — restart on file changes (`node --watch`).

## Environment

See [`.env.example`](./.env.example).

| Variable | Meaning |
|----------|---------|
| `LOG_DIR` | Directory to watch (absolute or relative to **cwd** when you start the process). |
| `LOG_FILE` | **Single file** (e.g. `laravel.log`), **`*` or `all`** for every file matching `LOG_GLOB`, or **comma list** (`laravel.log,horizon.log`). |
| `LOG_GLOB` | When tailing the whole directory: simple pattern, default `*.log` (also `laravel*`, `*debug*.log`, etc.). |
| `LOG_MIN_LEVEL` | Minimum Laravel log level for structured lines (`DEBUG` … `EMERGENCY`). Raw lines are always sent. |
| `WS_TOKEN` | Required `?token=` on WebSocket connections. |

### Line format in the UI

- Structured Laravel line: `[имя-файла.log][2026-05-12 10:15:30] local.ERROR: …`
- Non-Laravel / stack / raw line: `[имя-файла.log] …текст строки…`

So you always see **which file** a line came from, and for Laravel lines also the **timestamp from the log line**.

## Docker (root `compose.yaml`)

Service **`log-stream`** mounts `./storage/logs` read-only at `/var/www/html/storage/logs` and uses `LOG_FILE=*` + `LOG_GLOB=*.log` so dated files like `laravel-2026-05-11.log` are included.

In the **project root** `.env`: `FORWARD_LOG_STREAM_WS_PORT`, `LOG_STREAM_WS_TOKEN`.

```bash
docker compose up -d --build log-stream
```

## Architecture

| File | Role |
|------|------|
| `src/index.js` | HTTP static page + WS + single vs multi tail wiring |
| `src/tail-log.js` | `startTail` / `startMultiTail`, rotation, line buffer, dir scan |
| `src/broadcast.js` | Token check, parse/format with `[file][date]`, send JSON |
| `src/load-env.js` | Optional local `.env` |
