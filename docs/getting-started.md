# Установка и первый запуск

## Требования

- Docker и Docker Compose (рекомендуемый путь из `README.md` в корне).
- Либо локально: PHP 8.4+, Composer, Node.js (см. `composer.json` / `package.json`).

## Клонирование и окружение

```bash
git clone <repository-url> codesnip
cd codesnip
cp .env.example .env
```

Отредактируйте `.env`: `APP_NAME`, `APP_URL`, при необходимости БД, очереди, Scout, Ollama (см. [configuration.md](configuration.md)).

## Запуск через Docker Compose

Проект ориентирован на `compose.yaml` (стиль Laravel Sail: сервис `laravel.test`, nginx, БД, Redis, Meilisearch, MongoDB, Ollama, отдельный worker очередей).

```bash
docker compose up -d --build
```

Установка зависимостей в контейнере приложения:

```bash
docker compose exec laravel.test composer install
docker compose exec laravel.test npm install
```

Ключ приложения и миграции:

```bash
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
```

Vite в режиме разработки (часто с `--host` для доступа с хоста):

```bash
docker compose exec laravel.test npm run dev -- --host
```

Порты задаются в `.env`: `APP_PORT`, `VITE_PORT`, `FORWARD_DB_PORT` и т.д.

## Остановка

```bash
docker compose down
```

## Shell в контейнере

```bash
docker compose exec laravel.test bash
```

## Очереди

Сервис `laravel.queue` по умолчанию выполняет `php artisan queue:work`. Подробности — [infrastructure.md](infrastructure.md).

## Регистрация пользователя

Используйте маршруты из `routes/auth.php` (типовой Breeze/Fortify-подобный набор): регистрация, вход, сброс пароля — в зависимости от того, что включено в проекте.

## Что дальше

- [configuration.md](configuration.md) — переменные для Ollama, GitHub, Scout.
- [testing.md](testing.md) — запуск тестов.
