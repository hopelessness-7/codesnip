import fs from 'node:fs';
import path from 'node:path';

const DEFAULT_TAIL_BYTES = 16 * 1024;

/**
 * Read last up to maxBytes from file (for initial context).
 * @returns {string}
 */
export function readTailSnapshot(filePath, maxBytes = DEFAULT_TAIL_BYTES) {
    if (!fs.existsSync(filePath)) {
        return '';
    }

    const { size } = fs.statSync(filePath);
    if (size === 0) {
        return '';
    }

    const start = size > maxBytes ? size - maxBytes : 0;
    const end = size - 1;

    return fs.readFileSync(filePath, { encoding: 'utf8', start, end });
}

/**
 * Split buffer into complete lines; keep remainder after last newline.
 * @param {string} chunk
 * @param {string} carry — incomplete line from previous chunk
 * @returns {{ lines: string[], carry: string }}
 */
export function splitLines(chunk, carry) {
    const combined = carry + chunk;
    const lastNl = combined.lastIndexOf('\n');
    if (lastNl === -1) {
        return { lines: [], carry: combined };
    }

    const complete = combined.slice(0, lastNl);
    const newCarry = combined.slice(lastNl + 1);
    const lines = complete.split('\n').filter((l) => l.length > 0);

    return { lines, carry: newCarry };
}

/**
 * Resolve absolute path to the logs directory.
 */
export function resolveLogDir() {
    const root = path.resolve(process.cwd());
    const logDirEnv = process.env.LOG_DIR;

    if (logDirEnv) {
        return path.isAbsolute(logDirEnv) ? logDirEnv : path.resolve(root, logDirEnv);
    }

    return path.resolve(root, '..', '..', 'storage', 'logs');
}

/**
 * @returns {{ mode: 'single', filePath: string, fileLabel: string } | { mode: 'multi', logDir: string, filePattern: string, explicitNames?: string[] }}
 */
export function resolveWatchConfig() {
    const logDir = resolveLogDir();
    const raw = (process.env.LOG_FILE ?? 'laravel.log').trim();
    const lower = raw.toLowerCase();

    if (raw === '' || lower === '*' || lower === 'all') {
        const filePattern = (process.env.LOG_GLOB ?? '*.log').trim() || '*.log';

        return { mode: 'multi', logDir, filePattern };
    }

    if (raw.includes(',')) {
        const names = raw
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);

        return { mode: 'multi', logDir, filePattern: '*.log', explicitNames: names };
    }

    const filePath = path.join(logDir, raw);

    return { mode: 'single', filePath, fileLabel: path.basename(filePath) };
}

/**
 * Whether a filename in LOG_DIR should be tailed (simple glob: *.log, prefix*, *suffix).
 */
export function matchesLogPattern(filename, pattern) {
    if (!filename || filename.startsWith('.')) {
        return false;
    }

    if (pattern === '*' || pattern === '**') {
        return true;
    }

    if (!pattern.includes('*')) {
        return filename === pattern;
    }

    if (pattern.startsWith('*.')) {
        const suffix = pattern.slice(1);

        return filename.endsWith(suffix);
    }

    if (pattern.endsWith('*')) {
        const prefix = pattern.slice(0, -1);

        return filename.startsWith(prefix);
    }

    if (pattern.startsWith('*') && pattern.endsWith('*') && pattern.length > 1) {
        const mid = pattern.slice(1, -1);

        return filename.includes(mid);
    }

    return filename === pattern;
}

/**
 * @param {{ mode: 'multi', logDir: string, filePattern: string, explicitNames?: string[] }} config
 */
function listFilesToWatch(config) {
    if (!fs.existsSync(config.logDir)) {
        return [];
    }

    const names = fs.readdirSync(config.logDir, { withFileTypes: true });
    const out = [];

    for (const ent of names) {
        if (!ent.isFile()) {
            continue;
        }

        const name = ent.name;

        if (config.explicitNames?.length) {
            if (config.explicitNames.includes(name)) {
                out.push(path.join(config.logDir, name));
            }

            continue;
        }

        if (matchesLogPattern(name, config.filePattern)) {
            out.push(path.join(config.logDir, name));
        }
    }

    return out;
}

/**
 * Tail one file; onFileLines(basename, lines).
 *
 * @param {{ filePath: string, fileLabel?: string, onFileLines: (label: string, lines: string[]) => void, tailBytes?: number }} opts
 * @returns {{ stop: () => void }}
 */
export function startTail({ filePath, fileLabel, onFileLines, tailBytes = DEFAULT_TAIL_BYTES }) {
    const label = fileLabel ?? path.basename(filePath);
    let position = 0;
    let lineCarry = '';
    let reading = false;
    let stopped = false;
    let watcher = null;
    let retryTimer = null;

    const tryOpen = () => {
        if (stopped) {
            return;
        }

        if (!fs.existsSync(filePath)) {
            console.warn(`[tail-log] Log file not found yet: ${filePath} (retrying…)`);
            scheduleRetry();

            return;
        }

        clearRetry();

        const snapshot = readTailSnapshot(filePath, tailBytes);
        position = fs.statSync(filePath).size;

        if (snapshot.length > 0) {
            const { lines, carry } = splitLines(snapshot, '');
            lineCarry = carry;

            if (lines.length > 0) {
                onFileLines(label, lines);
            }
        }

        if (watcher) {
            watcher.close();
        }

        watcher = fs.watch(filePath, (eventType) => {
            if (eventType !== 'change' || stopped || reading) {
                return;
            }

            readNewBytes();
        });

        console.log(`[tail-log] Watching file ${filePath} (position ${position})`);
    };

    const scheduleRetry = () => {
        if (retryTimer || stopped) {
            return;
        }

        retryTimer = setInterval(() => {
            if (fs.existsSync(filePath)) {
                clearInterval(retryTimer);
                retryTimer = null;
                tryOpen();
            }
        }, 2000);
    };

    const clearRetry = () => {
        if (retryTimer) {
            clearInterval(retryTimer);
            retryTimer = null;
        }
    };

    const readNewBytes = () => {
        if (stopped || reading) {
            return;
        }

        reading = true;

        try {
            const stats = fs.statSync(filePath);
            const newSize = stats.size;

            if (newSize < position) {
                console.warn(`[tail-log] ${label}: rotated or truncated; resetting position`);
                position = 0;
                lineCarry = '';
            }

            if (newSize <= position) {
                reading = false;

                return;
            }

            const stream = fs.createReadStream(filePath, { start: position, end: newSize - 1 });
            let raw = '';

            stream.on('data', (chunk) => {
                raw += chunk.toString('utf8');
            });

            stream.on('end', () => {
                const { lines, carry } = splitLines(raw, lineCarry);
                lineCarry = carry;
                position = newSize;

                if (lines.length > 0) {
                    onFileLines(label, lines);
                }

                reading = false;
            });

            stream.on('error', (err) => {
                console.error('[tail-log] Read stream error:', err.message);
                reading = false;
            });
        } catch (err) {
            console.error('[tail-log]', err.message);
            reading = false;
        }
    };

    tryOpen();

    return {
        stop() {
            stopped = true;
            clearRetry();

            if (watcher) {
                watcher.close();
                watcher = null;
            }
        },
    };
}

/**
 * Tail all matching *.log (or LOG_GLOB / explicit list) in LOG_DIR.
 *
 * @param {{ logDir: string, filePattern: string, explicitNames?: string[], onFileLines: (label: string, lines: string[]) => void, scanIntervalMs?: number }} opts
 * @returns {{ stop: () => void }}
 */
export function startMultiTail({
    logDir,
    filePattern,
    explicitNames,
    onFileLines,
    scanIntervalMs = 8000,
}) {
    let stopped = false;
    /** @type {Map<string, { position: number, lineCarry: string, reading: boolean }>} */
    const stateByPath = new Map();
    let dirWatcher = null;
    let scanTimer = null;
    /** @type {Map<string, ReturnType<typeof setTimeout>>} */
    const debounceByPath = new Map();

    const config = { mode: 'multi', logDir, filePattern, explicitNames };

    function ensureState(filePath) {
        if (!stateByPath.has(filePath)) {
            let position = 0;
            let lineCarry = '';

            if (fs.existsSync(filePath)) {
                position = fs.statSync(filePath).size;
            }

            stateByPath.set(filePath, { position, lineCarry, reading: false });
        }

        return stateByPath.get(filePath);
    }

    function removeState(filePath) {
        const t = debounceByPath.get(filePath);

        if (t) {
            clearTimeout(t);
            debounceByPath.delete(filePath);
        }

        stateByPath.delete(filePath);
    }

    function syncDiscoveredFiles() {
        if (stopped || !fs.existsSync(logDir)) {
            return;
        }

        const paths = listFilesToWatch(config);

        for (const filePath of paths) {
            ensureState(filePath);
        }
    }

    function scheduleReadFile(filePath) {
        if (stopped) {
            return;
        }

        const label = path.basename(filePath);
        const existing = debounceByPath.get(filePath);

        if (existing) {
            clearTimeout(existing);
        }

        debounceByPath.set(
            filePath,
            setTimeout(() => {
                debounceByPath.delete(filePath);
                readNewBytesForFile(filePath, label);
            }, 50),
        );
    }

    function readNewBytesForFile(filePath, label) {
        if (stopped || !fs.existsSync(filePath)) {
            removeState(filePath);

            return;
        }

        const st = ensureState(filePath);

        if (st.reading) {
            return;
        }

        st.reading = true;

        try {
            const stats = fs.statSync(filePath);
            const newSize = stats.size;

            if (newSize < st.position) {
                console.warn(`[tail-log] ${label}: rotated or truncated; resetting position`);
                st.position = 0;
                st.lineCarry = '';
            }

            if (newSize <= st.position) {
                st.reading = false;

                return;
            }

            const stream = fs.createReadStream(filePath, { start: st.position, end: newSize - 1 });
            let raw = '';

            stream.on('data', (chunk) => {
                raw += chunk.toString('utf8');
            });

            stream.on('end', () => {
                const { lines, carry } = splitLines(raw, st.lineCarry);
                st.lineCarry = carry;
                st.position = newSize;
                st.reading = false;

                if (lines.length > 0) {
                    onFileLines(label, lines);
                }
            });

            stream.on('error', (err) => {
                console.error(`[tail-log] ${label} read error:`, err.message);
                st.reading = false;
            });
        } catch (err) {
            console.error('[tail-log]', err.message);
            st.reading = false;
        }
    }

    function onDirEvent(_eventType, filename) {
        if (stopped) {
            return;
        }

        if (!fs.existsSync(logDir)) {
            return;
        }

        if (filename) {
            if (explicitNames?.length) {
                if (!explicitNames.includes(filename)) {
                    return;
                }
            } else if (!matchesLogPattern(filename, filePattern)) {
                return;
            }

            const filePath = path.join(logDir, filename);
            ensureState(filePath);
            scheduleReadFile(filePath);

            return;
        }

        syncDiscoveredFiles();

        for (const filePath of stateByPath.keys()) {
            if (fs.existsSync(filePath)) {
                scheduleReadFile(filePath);
            }
        }
    }

    // Initial: discover files, start at EOF (no bulk replay of old logs)
    syncDiscoveredFiles();

    for (const filePath of stateByPath.keys()) {
        console.log(`[tail-log] Watching (dir) ${path.basename(filePath)} → EOF position ${stateByPath.get(filePath).position}`);
    }

    if (!fs.existsSync(logDir)) {
        console.warn(`[tail-log] LOG_DIR does not exist yet: ${logDir} (retrying scan…)`);
    }

    try {
        dirWatcher = fs.watch(logDir, { persistent: true }, onDirEvent);
    } catch (err) {
        console.error('[tail-log] fs.watch(logDir) failed:', err.message);
    }

    scanTimer = setInterval(() => {
        if (stopped) {
            return;
        }

        if (!fs.existsSync(logDir)) {
            return;
        }

        const before = new Set(stateByPath.keys());
        syncDiscoveredFiles();

        for (const filePath of stateByPath.keys()) {
            if (!before.has(filePath)) {
                console.log(`[tail-log] New log file: ${path.basename(filePath)}`);
            }

            scheduleReadFile(filePath);
        }
    }, scanIntervalMs);

    return {
        stop() {
            stopped = true;

            if (dirWatcher) {
                dirWatcher.close();
                dirWatcher = null;
            }

            if (scanTimer) {
                clearInterval(scanTimer);
                scanTimer = null;
            }

            for (const t of debounceByPath.values()) {
                clearTimeout(t);
            }

            debounceByPath.clear();
            stateByPath.clear();
        },
    };
}

/** @deprecated use resolveWatchConfig + startTail / startMultiTail */
export function resolveLogPath() {
    const logDir = resolveLogDir();
    const logFileName = process.env.LOG_FILE || 'laravel.log';

    return path.join(logDir, logFileName);
}
