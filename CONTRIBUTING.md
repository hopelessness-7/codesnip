# Участие в разработке CodeSnip

Спасибо за интерес к проекту. CodeSnip — приложение на Laravel + Livewire для хранения, поиска и версионирования фрагментов кода. Ниже — краткий процесс для тех, кто хочет предложить исправление или новую возможность.

## С чего начать

1. Ознакомьтесь с [README.md](README.md) и оглавлением [docs/README.md](docs/README.md).
2. Поднимите окружение по [docs/getting-started.md](docs/getting-started.md) (рекомендуется Docker Compose из `compose.yaml`).
3. Для понимания структуры кода прочитайте [docs/architecture.md](docs/architecture.md).

## Окружение разработки

Основной сценарий — Docker (стиль Laravel Sail):

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec laravel.test composer install
docker compose exec laravel.test npm install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test npm run dev -- --host
```

Подробности по портам, очередям и сервисам: [docs/infrastructure.md](docs/infrastructure.md), [docs/configuration.md](docs/configuration.md).

Локальный запуск без Docker возможен при наличии PHP 8.4+, Composer и Node.js (см. `composer.json` и `package.json`).

## Ветки и pull request

- Целевые ветки для интеграции: **`main`** и **`develop`** (на них настроен CI).
- Создайте ветку от актуальной `develop` (или `main`, если в репозитории принят другой рабочий поток — уточните у мейнтейнеров).
- Делайте **небольшие, сфокусированные** PR: одна логическая задача на запрос.
- В описании PR укажите:
  - что изменилось и зачем;
  - как проверить вручную;
  - ссылки на issue, если есть.

## Стиль кода и архитектура

### PHP / Laravel

- Следуйте слоям проекта: контроллеры и Form Request → Livewire/Volt → сервисы → репозитории → модели ([docs/architecture.md](docs/architecture.md)).
- Бизнес-логику по возможности выносите в `app/Services`, доступ к данным — в `app/Repositories`.
- Перед отправкой PR отформатируйте PHP через **Laravel Pint**:

```bash
docker compose exec laravel.test ./vendor/bin/pint
```

### Frontend

- Сборка: Vite (`npm run dev` / `npm run build`).
- UI: Livewire Flux (`flux:*`).
- Редактор: CodeMirror 6.

Подробнее: [docs/frontend-i18n.md](docs/frontend-i18n.md).

### Локализация

Новые пользовательские строки добавляйте **в оба** файла:

- `lang/en.json`
- `lang/ru.json`

Иначе переключатель языка (`en` / `ru`) будет показывать неполный интерфейс.

### Безопасность

- Не коммитьте секреты (`.env`, токены, ключи API).
- Учитывайте политики доступа и публичные ссылки — см. [docs/security.md](docs/security.md).

## Тесты

Для изменений в поведении добавляйте или обновляйте тесты в `tests/`. Предпочтительны **Feature**-тесты для HTTP/Livewire-сценариев.

Запуск всех тестов:

```bash
docker compose exec laravel.test php artisan test
```

Выборочный запуск:

```bash
docker compose exec laravel.test php artisan test --filter=ИмяТеста
```

Рекомендации: [docs/testing.md](docs/testing.md).

Перед PR убедитесь, что проходят:

1. `./vendor/bin/pint` (или эквивалент в контейнере);
2. `php artisan test` (или `./vendor/bin/phpunit`, как в CI);
3. `npm run build` — если менялись фронтенд-ассеты.

## CI

На push и pull request в `main` / `develop` запускаются workflow:

| Workflow | Проверка |
|----------|----------|
| [`.github/workflows/lint.yml`](.github/workflows/lint.yml) | Laravel Pint |
| [`.github/workflows/tests.yml`](.github/workflows/tests.yml) | `composer install`, `npm run build`, PHPUnit |

Локально воспроизведите эти шаги, чтобы снизить риск отклонения PR.

## Документация

- Общая документация — каталог [docs/](docs/).
- Крупные фичи описывайте в отдельных файлах по образцу `docs/feature-*.md`, если меняется заметное поведение или API.
- Не расширяйте scope PR правками документации, не связанными с задачей.

## Сообщение об ошибках

При создании issue приложите:

- шаги воспроизведения;
- ожидаемое и фактическое поведение;
- версию PHP / браузера (для UI);
- фрагмент лога или скриншот, если уместно.

## Лицензия

Внося изменения, вы соглашаетесь с тем, что вклад распространяется на условиях [MIT License](LICENSE), как и остальной код проекта.
