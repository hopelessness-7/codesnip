# Поиск и сохранённые запросы

## Поведение

На индексе сниппетов доступны фильтры (текст по заголовку/коду, язык, теги, видимость, диапазоны дат создания/обновления) и сортировка (в т.ч. по релевантности при наличии текстового запроса).

Реализация опирается на DTO **`SearchFilters`** и запросы через репозиторий/сервис сниппетов.

## Laravel Scout

Модель **`Snippet`** подключена к Scout (`Searchable`, метод `toSearchableArray()`).

Драйвер по умолчанию задаётся в `.env`:

```env
SCOUT_DRIVER=collection
```

- **`collection`** — по сути поиск по коллекции в памяти / без внешнего Meilisearch (удобно для разработки).
- Для продакшена при подключённом **Meilisearch** можно переключить драйвер и выполнить индексацию (`scout:import` для модели `Snippet`), предварительно настроив индекс и переменные окружения Scout.

В `compose.yaml` уже есть сервис **meilisearch**; порт пробрасывается через `FORWARD_MEILISEARCH_PORT`.

## Сохранённые поиски

Модель **`SavedSearch`** хранит набор параметров фильтра на пользователя. Сервис **`SavedSearchService`** инкапсулирует работу с ними.

## Связанные файлы

- `app/DTOs/SearchFilters.php`
- `app/Models/Snippet.php` (Scout)
- `config/scout.php`
- `app/Models/SavedSearch.php`, `app/Services/SavedSearchService.php`
