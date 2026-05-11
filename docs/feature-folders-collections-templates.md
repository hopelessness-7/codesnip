# Папки, smart-коллекции и шаблоны сниппетов

## Папки (Folders)

- CRUD: список, создание, редактирование.
- Связь **многие-ко-многим** между `Folder` и `Snippet` (pivot `folder_snippet`).
- Сервис: **`FolderService`**, репозиторий: **`FolderRepository`**.

Маршруты: `folders.index`, `folders.create`, `folders.edit`.

## Smart-коллекции (Smart collections)

- Коллекции снимков сниппетов по правилам.
- Правила хранятся в **`filters_json`** (массив в PHP через cast).
- Связь со сниппетами many-to-many (`smart_collection_snippet`).
- Сервис: **`SmartCollectionService`**, репозиторий: **`SmartCollectionRepository`**.

Маршруты: `smart-collections.index`, `smart-collections.create`, `smart-collections.edit`.

DTO правил: `App\DTOs\SmartCollectionRulesData` (при использовании в коде).

## Шаблоны сниппетов (Snippet templates)

- Пользовательские шаблоны с полями: название, описание, шаблон заголовка, шаблон кода, язык, теги по умолчанию (JSON), флаг избранного.
- В шаблонах используются плейсхолдеры **`[[variable_name]]`** для подстановки при создании сниппета.
- Сервис: **`SnippetTemplateService`**, репозиторий: **`SnippetTemplateRepository`**.

Маршруты: `snippet-templates.index`, `snippet-templates.create`, `snippet-templates.edit`.

## Livewire-компоненты

| Область | Классы (папка `app/Livewire`) |
|---------|-------------------------------|
| Папки | `Folders\Index`, `Create`, `Edit` |
| Коллекции | `SmartCollections\Index`, `Create`, `Edit` |
| Шаблоны | `SnippetTemplates\Index`, `Create`, `Edit` |

## Связанные документы

- [data-model.md](data-model.md)
- [feature-snippets.md](feature-snippets.md)
