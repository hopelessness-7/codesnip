# Тестирование и качество кода

## PHPUnit

Тесты расположены в **`tests/`** (Feature и Unit по структуре Laravel).

Запуск всех тестов (внутри контейнера приложения):

```bash
docker compose exec laravel.test php artisan test
```

Примеры выборочного запуска (из корневого `README.md`):

```bash
docker compose exec laravel.test php artisan test --filter=SnippetRevisionTest
docker compose exec laravel.test php artisan test --filter=SnippetSearchTest
```

## Laravel Pint

Форматирование PHP под стиль проекта:

```bash
./vendor/bin/pint
```

(или `docker compose exec laravel.test ./vendor/bin/pint`).

## Рекомендации для новых фич

- Для сценариев с HTTP/Livewire предпочтительны **Feature**-тесты с `Livewire::test()` или вызовами маршрутов.
- Проверяйте политики доступа и граничные случаи импорта (дубликаты UUID, dry run).

## Связанные документы

- [getting-started.md](getting-started.md)
