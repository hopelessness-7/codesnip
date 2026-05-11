# Конфигурация и переменные окружения

## Приложение

| Переменная | Описание |
|------------|----------|
| `APP_NAME`, `APP_URL`, `APP_KEY` | Стандартные настройки Laravel |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Локаль по умолчанию |
| `APP_DEBUG` | Режим отладки |

## База данных

В **`.env.example`** по умолчанию указан **`DB_CONNECTION=sqlite`**. В Docker Compose обычно используют MariaDB: задайте `DB_CONNECTION=mysql`, хост `mariadb`, имя БД, пользователя и пароль в согласовании с `compose.yaml`.

## Сессии и кэш

- `SESSION_DRIVER` — в примере `database` (нужна таблица сессий при использовании БД).
- `CACHE_STORE` — в примере `database`.

## Очереди

| Переменная | Описание |
|------------|----------|
| `QUEUE_CONNECTION` | `database`, `redis` или `sync` (синхронно, без воркера) |

Для **Laravel Horizon** нужен **Redis** и соответствующие переменные `REDIS_*`.

## Поиск (Scout)

```env
SCOUT_DRIVER=collection
```

Другие драйверы — см. `config/scout.php` и документацию Laravel Scout (Meilisearch, database, …).

## Ollama / OpenAI-пакет

Файл **`config/openai.php`** (пакет `openai-php/laravel`):

- `OPENAI_API_KEY` и др. — для облачного OpenAI, если когда-либо понадобится;
- для CodeSnip важны **`OLLAMA_HOST`** и **`OLLAMA_MODEL`** в секции `ollama`.

Добавьте в свой `.env` при необходимости:

```env
OLLAMA_HOST=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:7b
```

## Livewire

**`config/livewire.php`**: в том числе namespace **`layouts`** → каталог с Blade-шаблонами приложения (`resources/views/components/layouts`), чтобы маршруты Volt с `layouts::app` резолвились корректно.

## Почта, файлы, логирование

Стандартные ключи Laravel — `config/mail.php`, `config/filesystems.php`, `config/logging.php`.

## GitHub

Токен **не** хранится в `.env` для конечного пользователя: он вводится в профиле или на странице импорта и сохраняется в БД (шифрование cast на модели `User`).

## Docker Compose (дополнительно к `.env`)

См. комментарии в **`.env.example`**: `WWWUSER`, `WWWGROUP`, `APP_PORT`, `VITE_PORT`, проброс портов БД, Redis, Meilisearch, MongoDB, Ollama.

## Связанные документы

- [getting-started.md](getting-started.md)
- [infrastructure.md](infrastructure.md)
