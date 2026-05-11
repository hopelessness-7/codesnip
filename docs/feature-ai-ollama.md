# ИИ и Ollama

Приложение использует **локальный Ollama** HTTP API, а не облачный OpenAI, для функций ИИ на сниппетах и фоновых задач.

## Конфигурация по умолчанию

Файл **`config/openai.php`**, секция `ollama`:

| Ключ | Назначение | Значение по умолчанию (env) |
|------|------------|-----------------------------|
| `host` | Базовый URL Ollama | `OLLAMA_HOST`, по умолчанию `http://ollama:11434` (имя сервиса в Docker Compose) |
| `model` | Модель по умолчанию | `OLLAMA_MODEL`, по умолчанию `qwen2.5:7b` |

В `.env` на машине **без** Docker для Ollama часто задают `OLLAMA_HOST=http://127.0.0.1:11434`.

## Выбор модели пользователем

В таблице **`users`** поле **`ollama_model`** (nullable):

- если задано непустое значение — его используют **`SnippetAiService`** и **`GenerateTagsJob`**;
- иначе берётся `config('openai.ollama.model')`.

Настройка в UI: **`/settings/profile`** — обновление списка моделей (`GET /api/tags`), отображение загруженных в память (`GET /api/ps`), поле ввода имени модели и сохранение.

## Провайдер

**`App\Modules\AI\Providers\OllamaProvider`**:

- `generate` / `chat` — обращение к `POST /api/generate`;
- `isAvailable` — `GET /api/tags`;
- `listLocalModelNames` — имена установленных моделей;
- `listRunningModelNames` — имена моделей, удерживаемых в RAM (`GET /api/ps`).

Базовый класс: **`AbstractAiProvider`**, контракт: **`AiProviderInterface`**.

## Сервис сниппет-ИИ

**`SnippetAiService`** строит провайдер через `providerForSnippet(Snippet $snippet)` и использует его для:

- краткого описания (summary);
- объяснения кода;
- генерации тестов.

Результаты могут сохраняться в поля сниппета (`ai_*`), в зависимости от UI и логики редактирования.

## Очередь тегов

**`GenerateTagsJob`**: подтягивает теги через Ollama с той же моделью, что и для владельца сниппета; при неудаче возможен локальный fallback (см. код задачи).

## Требования к окружению

Модель должна быть **установлена в Ollama** (`ollama pull …`). Приложение только передаёт имя модели в API; скачивание моделей выполняется вручную на стороне Ollama.

## Docker

В **`compose.yaml`** описан сервис **`ollama`** с томом для кэша моделей. Блок `deploy.resources.reservations.devices` (NVIDIA GPU) можно убрать или изменить, если GPU нет — см. документацию Ollama для CPU-only.

## Связанные документы

- [configuration.md](configuration.md)
- [infrastructure.md](infrastructure.md)
