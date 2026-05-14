import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Minimal .env loader (no dotenv dependency).
 * Does not override existing process.env keys.
 */
export function loadEnvFile(relativePath = '../.env') {
    const envPath = path.resolve(__dirname, relativePath);
    if (!fs.existsSync(envPath)) {
        return;
    }

    const text = fs.readFileSync(envPath, 'utf8');
    for (const line of text.split('\n')) {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) {
            continue;
        }

        const eq = trimmed.indexOf('=');
        if (eq === -1) {
            continue;
        }

        const key = trimmed.slice(0, eq).trim();
        const value = trimmed.slice(eq + 1).trim();
        if (key && value !== undefined && process.env[key] === undefined) {
            process.env[key] = value;
        }
    }
}
