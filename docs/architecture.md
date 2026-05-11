# Архитектура приложения

## Слои

1. **HTTP**: контроллеры (`app/Http/Controllers`), Form Request-ы, политики (`app/Policies`).
2. **Livewire**: полноценные страницы и виджеты (`app/Livewire`) — основной UI для авторизованной зоны.
3. **Volt**: однофайловые компоненты в `resources/views/livewire` (например настройки `settings/*`).
4. **Сервисы** (`app/Services`): бизнес-логика, не привязанная к HTTP (сниппеты, ревизии, импорт, ИИ).
5. **Репозитории** (`app/Repositories`): абстракция доступа к данным; интерфейсы в `Contracts`, реализации в `Eloquent`; регистрация в `AppServiceProvider`.
6. **DTO** (`app/DTOs`): перенос структурированных данных между слоями (фильтры поиска, импорт, архив).
7. **Модули** (`app/Modules`): обособленные подсистемы — ИИ-провайдеры, GitHub Gist HTTP-клиент, парсеры.

## Маршрутизация

- **`routes/web.php`**: главная, дашборд, сниппеты, папки, коллекции, теги, шаблоны, импорт/экспорт, Volt-страницы настроек.
- **`routes/auth.php`**: вход, регистрация, сброс пароля, подтверждение email (Volt + один контроллер верификации).
- Публичные сниппеты:
  - `/p/snippets/{uuid}` — открытая страница (только `is_public`).
  - `/shared/snippets/{uuid}` — подписанная ссылка (`signed`), может открыть и непубличный сниппет при корректной подписи.

Middleware: для основной группы — `auth`, `verified` где указано; глобально на web добавлен `SetLocaleFromSession` для локали.

## Livewire и раскладка

- Страницы с `#[Layout('components.layouts.app')]` используют Blade-шаблон `resources/views/components/layouts/app.blade.php`.
- Для Volt заданы `layout()` / `title()` в соответствующих файлах и/или путь к layout в `config/livewire.php` (namespace `layouts` → `resources/views/components/layouts`).

## Очереди и задания

- Классы заданий в `app/Jobs` (например генерация тегов через Ollama).
- Обработка: `queue:work` в контейнере или Horizon при `QUEUE_CONNECTION=redis`.

## Автозагрузка и соглашения

- PSR-4: `App\` → `app/`.
- Тесты: `Tests\` → `tests/`.

## Связанные документы

- [data-model.md](data-model.md) — модели и таблицы.
- [infrastructure.md](infrastructure.md) — Docker и очереди.
