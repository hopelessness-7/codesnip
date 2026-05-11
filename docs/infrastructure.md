# Инфраструктура: Docker, очереди, Horizon

## Docker Compose (`compose.yaml`)

Типовой набор сервисов:

| Сервис | Назначение |
|--------|------------|
| `nginx` | Веб-сервер, статика и прокси к PHP |
| `laravel.test` | PHP-FPM (приложение Laravel) |
| `laravel.queue` | Процесс `php artisan queue:work` |
| `mariadb` | Основная реляционная БД |
| `redis` | Кэш, очереди, Horizon |
| `meilisearch` | Опциональный поисковый движок для Scout |
| `mongodb` | Локальный совместимый образ (по необходимости проекта) |
| `ollama` | Локальные LLM для ИИ |

Сеть по умолчанию: **`sail`**. Тома для персистентных данных: MariaDB, Redis, Meilisearch, MongoDB, Ollama.

### Ollama и GPU

В шаблоне `compose` для `ollama` может быть указана резервация **NVIDIA**. На машине без GPU удалите или замените секцию `deploy.resources`, иначе Compose может выдавать предупреждения или не стартовать сервис — ориентируйтесь на документацию вашей версии Compose и Ollama.

### Доступ к Ollama с хоста

Порт пробрасывается через **`FORWARD_OLLAMA_PORT`** (по умолчанию 11434). Внутри сети Docker приложение обращается к хосту **`http://ollama:11434`**.

## Очереди

- Сервис **`laravel.queue`** запускает воркер с таймаутом и числом попыток, подходящим для долгих задач (например ИИ).
- Если **`QUEUE_CONNECTION=sync`**, задания выполняются в том же процессе, что и HTTP — воркер-контейнер можно остановить.

## Laravel Horizon

Пакет установлен (`laravel/horizon`). Horizon работает **только с Redis**. Нельзя одновременно гонять несколько воркеров на одних и тех же очередях без настройки — см. комментарии в `.env.example` в репозитории.

Конфиг: **`config/horizon.php`**. Путь UI по умолчанию: **`/horizon`** (защитите доступ в продакшене).

## Healthcheck

У `laravel.test` настроен healthcheck PHP-FPM; от него зависит старт `nginx` (`depends_on: condition: service_healthy`).

## Связанные документы

- [getting-started.md](getting-started.md)
- [feature-ai-ollama.md](feature-ai-ollama.md)
