# Фронтенд, ассеты и локализация

## Сборка

- **Vite** — `vite.config.js`, скрипты `npm run dev` / `npm run build`.
- **Tailwind CSS v4** — через `@tailwindcss/vite`.
- Подключение в Blade: директива `@vite` в layout-приложения.

## CodeMirror

Редактор кода на страницах сниппетов использует **CodeMirror 6** и языковые пакеты из `package.json` (`@codemirror/lang-*`, legacy modes при необходимости).

## UI: Livewire Flux

Компоненты **`flux:*`** (кнопки, поля, модалки, таблицы и т.д.) из пакета **`livewire/flux`**.

## Локализация

- Файлы переводов в формате JSON: **`lang/en.json`**, **`lang/ru.json`** (ключи с точками, например `nav.settings`).
- Переключатель языка: маршрут **`GET /locale/{locale}`** (`locale.switch`), допустимые значения `en`, `ru`.
- Middleware **`SetLocaleFromSession`** выставляет локаль из сессии для web-запросов.

Строки для новых экранов добавляйте в оба JSON-файла, чтобы не ломать переключение языка.

## Настройки (Volt)

Файлы в **`resources/views/livewire/settings/`**:

- `profile.blade.php` — профиль, email, GitHub token, Ollama;
- `password.blade.php` — смена пароля;
- `appearance.blade.php` — светлая / тёмная / системная тема (Flux appearance).

## Тосты

Глобальные уведомления диспатчатся с фронта/Livewire событием вроде **`app-toast`** (см. использование в компонентах).

## Связанные документы

- [architecture.md](architecture.md)
