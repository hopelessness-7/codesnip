const fs = require('fs');
const path = require('path');
const { startTail } = require('./tail-log');

function loadEnv(envPath) {
    if (fs.existsSync(envPath)){
        const lines = fs.readFileSync(envPath, 'utf8').split('\n')
        for (const line of lines) {
            const trimmed = line.trim();
            // Ignore comments and empty values.
            if (!trimmed || trimmed.startsWith('#')) continue;

            const [key, ...rest] = trimmed.split("=");
            const value = rest.join("=").trim();

            if (key && value && !process.env[key]) {
                process.env[key] = value;
            }
        }
    }
}
loadEnv(path.resolve(__dirname, '..', '.env'));

const LOG_DIR = process.env.LOG_DIR || path.resolve(__dirname, '..', '..', '..', 'storage', 'logs');
const LOG_FILE = process.env.LOG_FILE || 'laravel.log';
const LOG_PATH = path.join(LOG_DIR, LOG_FILE);
const TAIL_SIZE = 16 * 1024;

startTail({
    filePath: LOG_PATH,
    onChunk: (lines) => {
        for (const line of lines) {
            console.log(`[${line.date}] ${line.level}: ${line.message}`)

            if (line.isError && line.context?.exception) {
                console.log(`${line.context.exception.message || 'No message'}`);
            }
        }
    }
})

console.log('✅ log-stream ready');
