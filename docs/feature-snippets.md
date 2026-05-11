# Сниппеты, редактор и ревизии

## Маршруты (авторизованный пользователь)

| Путь | Назначение |
|------|------------|
| `GET /snippets` | Список и поиск сниппетов |
| `GET /snippets/create` | Создание |
| `GET /snippets/{snippet}/edit` | Редактирование |
| `GET /snippets/import-export` | Импорт и экспорт |

Имена маршрутов: `snippets.index`, `snippets.create`, `snippets.edit`, `snippets.import-export`.

## Политики

`SnippetPolicy` ограничивает доступ к операциям только владельцу сниппета (где применимо).

## Публичный просмотр

- **`/p/snippets/{uuid}`** (`snippets.publicOpen`): только если `is_public = true`.
- **`/shared/snippets/{uuid}`** (`snippets.publicView`): URL с подписью Laravel (`signed`); удобно для временной выдачи ссылки.

Контроллер: `App\Http\Controllers\Web\V1\SnippetController`.

## Редактор

На странице редактирования используется **CodeMirror 6** с языковыми пакетами из `package.json` (PHP, JS, JSON, SQL, YAML, Markdown и др.).

Типичные функции UI (см. Livewire-компонент редактирования):

- сохранение с клавиатуры;
- черновик в `localStorage`;
- опциональный автосохранение;
- форматирование JSON;
- перенос строк, копирование;
- диагностика JSON и предупреждения о пробелах в конце строк.

## Ревизии

Сервис **`SnippetRevisionService`** и репозиторий ревизий создают снимки при осмысленных изменениях (без лишних записей, если данные не менялись).

На UI (панель ревизий, Livewire):

- список версий;
- режимы diff: выбранная vs текущая, выбранная vs предыдущая;
- откат к выбранной ревизии.

## Сервисы и репозитории

- **`SnippetService`**: основные операции со сниппетами.
- **`SnippetRepository`**: выборки, привязка тегов и т.д. (см. интерфейс `SnippetRepositoryInterface`).

## Связанные документы

- [feature-ai-ollama.md](feature-ai-ollama.md) — действия ИИ на карточке сниппета.
- [feature-import-export.md](feature-import-export.md) — перенос сниппетов между окружениями.
