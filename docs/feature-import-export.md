# Импорт и экспорт

Страница: **`/snippets/import-export`**, компонент `App\Livewire\Snippets\ImportExport`.

## ZIP

- **Импорт**: загрузка `.zip` (лимит размера задаётся валидацией, по умолчанию до 50 МБ). Разбор выполняет **`SnippetZipArchiveService`**: манифест + файлы сниппетов, совместимость с массовым парсером **`MassSnippetFileParser`** (расширения по языку).
- **Экспорт**: формируется временный архив в `storage/app/tmp`, отдаётся на скачивание и удаляется после отправки.

## JSON-конверт (внутренний формат)

**`SnippetJsonTransferService`** собирает и разбирает «конверт» экспорта:

- обёртка **`SnippetArchiveEnvelopeData`**;
- элементы **`SnippetExportItemData`** (uuid, заголовок, код, язык, публичность, теги).

При экспорте в массив: если список ID **пустой**, в выборку попадают **все** сниппеты пользователя; если ID заданы — только они (с проверкой владельца на уровне репозитория).

## Опции импорта (`ImportOptionsData`)

- поведение при дубликате по UUID (`skip` / обновление — см. актуальные значения в коде);
- сохранять ли UUID из файла;
- видимость по умолчанию для новых записей;
- **dry run**: просчёт без записи в БД.

Результат импорта — **`ImportResultData`** (создано, обновлено, пропущено, ошибки); на UI показывается toast с агрегированной статистикой.

## GitHub Gist

Требуется **личный токен** (Classic PAT) с правами на **gist**, сохранённый у пользователя:

- на странице импорта/экспорта, или
- в **`/settings/profile`** (то же поле в БД).

Модуль **`App\Modules\GithubGist\Client`**: `POST /gists`, `GET /gists/{id}` к `api.github.com` с заголовком версии API.

**`GithubGistTransferService`**:

- **Экспорт в gist**: один файл `codesnip-export.json` с тем же JSON, что и внутренний экспорт; непубличный gist по умолчанию.
- **Импорт из gist**: URL или сырой id; из ответа API извлекается `codesnip-export.json` и передаётся в `SnippetJsonTransferService::importFromArray`.

## Связанные файлы

- `app/Livewire/Snippets/ImportExport.php`
- `app/Services/SnippetZipArchiveService.php`
- `app/Services/SnippetJsonTransferService.php`
- `app/Services/GithubGistTransferService.php`
- `app/Modules/GithubGist/Client.php`
- `app/DTOs/ImportOptionsData.php`, `ImportResultData.php`, `SnippetArchiveEnvelopeData.php`, `SnippetExportItemData.php`

## Связанные документы

- [configuration.md](configuration.md) — переменные окружения (при необходимости для HTTP).
- [feature-ai-ollama.md](feature-ai-ollama.md) — отдельно от экспорта.
