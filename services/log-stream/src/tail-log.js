const fs = require('fs');

/**
 * Парсит одну строку лога Laravel.
 * Возвращает объект или null (если строка не важная).
 *
 * Пример строки:
 * [2026-05-12 10:15:30] local.ERROR: Something went wrong {"exception":"..."}
 */
function parserLine(line) {
    if (!line.trim()) return null;

    const match = line.match(/^\[(?<date>[^\]]+)\]\s+(?<channel>\w+)\.(?<level>\w+):\s+(?<message>.+?)(?:\s+(?<context>\{.*\}))?$/);

    if (!match) return null;

    const { date, channel, level, message, context } = match.groups;

    const minLevel = process.env.LOG_LEVEL || 'DEBUG';
    const levels = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    if (levels.indexOf(level) < levels.indexOf(minLevel)) {
        return null;
    }

    let parsedContext = null;

    if (context) {
        try{
            parsedContext= JSON.parse(context);
        } catch {
            parsedContext = context;
        }
    }

    return { date, channel, level, message: message.trim(), context :parsedContext, isError: ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'].includes(level) }
}

/**
 * Запускает отслеживание изменений файла.
 * При появлении новых строк вызывает onChunk(parsedLines[]).
 */
function startTail({ filePath, onChunk }) {
    if (!fs.existsSync(filePath)) {
        return;
    }

    let position = fs.statSync(filePath).size;

    fs.watch(filePath, (eventType) => {
       if (eventType !== 'change') return;

       try {
           const stats = fs.statSync(filePath);
           const newSize = stats.size;

           if (newSize < position) {
               position = 0;
           }

           if (newSize <= position) return;

           const stream = fs.createReadStream(filePath, {start: position, end: newSize - 1});

           let rawData = '';

           stream.on('data', (chunk) => {
               rawData += chunk.toString();
           });

           stream.on('end', () => {
              const lines = rawData.split('\n');
              const parsed = lines.map(parserLine).filter(Boolean);

              if (parsed.length > 0) {
                  onChunk(parsed);
              }

              position = newSize;
           });

           stream.on('error', (err) => {
               console.error('Stream error:', err.message);
           });

       } catch (err) {

       }
    });
}

module.exports = { startTail, parserLine };
