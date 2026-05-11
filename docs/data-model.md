# Модель данных

Ниже — логические сущности и типовые связи. Точная схема колонок — в файлах `database/migrations/*.php`.

## Пользователь (`users`)

- Аутентификация: имя, email, пароль, remember token.
- **`github_personal_access_token`**: зашифрованное поле для API GitHub (Gist).
- **`ollama_model`**: необязательное имя модели Ollama; если пусто — используется значение из конфига приложения.

## Сниппет (`snippets`)

- Принадлежит пользователю (`user_id`).
- **`uuid`**: генерируется при создании, для публичных URL.
- Поля: заголовок, код, язык, флаг публичности, мягкое удаление (`SoftDeletes`).
- Поля ИИ (если миграция применена): `ai_summary`, `ai_explanation`, `ai_generated_test`.
- Связи:
  - **теги** — many-to-many через `snippet_tag`;
  - **папки** — many-to-many через `folder_snippet`;
  - **smart-коллекции** — many-to-many через `smart_collection_snippet`;
  - **ревизии** — `hasMany` `SnippetRevision`.

## Ревизия (`snippet_revisions`)

- Снимок состояния сниппета для истории и отката.

## Тег (`tags`)

- Имя, slug и др.; связь со сниппетами many-to-many.

## Сохранённый поиск (`saved_searches`)

- Параметры фильтров на пользователя (для экрана поиска сниппетов).

## Папка (`folders`)

- Пользовательская папка; связь со сниппетами many-to-many.

## Smart-коллекция (`smart_collections`)

- Пользователь; **`filters_json`**: массив правил подборки сниппетов; флаг `is_system` при необходимости.

## Шаблон сниппета (`snippet_templates`)

- Пользователь; шаблоны заголовка и кода с переменными `[[...]]`; язык; JSON тегов по умолчанию; избранное.

## Служебные таблицы Laravel

- `cache`, `jobs`, сессии (если `SESSION_DRIVER=database`) — по стандартным миграциям в репозитории.

## Индексация поиска (Scout)

Модель `Snippet` использует трейт `Searchable`; в `toSearchableArray()` попадают поля, по которым строится индекс (заголовок, код, язык, пользователь, публичность).

## Связанные документы

- [feature-snippets.md](feature-snippets.md)
- [feature-search.md](feature-search.md)
- [feature-folders-collections-templates.md](feature-folders-collections-templates.md)
