# Безопасность и доступ к данным

## Аутентификация

- Регистрация, вход, сброс пароля и подтверждение email — маршруты в **`routes/auth.php`** (часть на Volt).
- Выход: `POST /logout` (`Logout` Livewire).

## Авторизация

- **Policies** (например `SnippetPolicy`) ограничивают операции над сниппетами владельцем.
- Закрытые разделы приложения обёрнуты в middleware **`auth`** и при необходимости **`verified`**.

## Публичные сниппеты

- Открытый URL **`/p/snippets/{uuid}`** отдаёт только **публичные** сниппеты.
- Подписанная ссылка **`/shared/snippets/{uuid}`** использует middleware **`signed`**: без корректной подписи Laravel ответ будет ошибкой.

## Секреты пользователя

- **`github_personal_access_token`** хранится с cast **`encrypted`** на модели `User` и не попадает в JSON-сериализацию пользователя (`$hidden`).

## Рекомендации для продакшена

- `APP_DEBUG=false`, надёжный `APP_KEY`, HTTPS (`APP_URL` с `https://`).
- Ограничить доступ к **`/horizon`** и любым отладочным маршрутам.
- Регулярно обновлять зависимости Composer/npm и образы Docker.

## Связанные документы

- [architecture.md](architecture.md)
- [configuration.md](configuration.md)
