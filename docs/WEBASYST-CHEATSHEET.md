# Webasyst — шпаргалка для almamed

Краткая выжимка из [developers.webasyst.ru/docs](https://developers.webasyst.ru/docs/) под наш проект.

---

## Поток запроса (фронтенд)

```
HTTP → index.php → waDispatch → waRouting (wa-config/routing.php)
     → app (shop/site/blog) → controller/action
     → waLayout + Smarty theme → HTML
```

Домен берётся из `HTTP_HOST`. Порт `:8080` сохраняется (не как `:80`/`:443`).

---

## Ключевые классы

| Класс | Назначение |
|-------|------------|
| `waSystem` | Ядро, `wa()`, `getVersion()` |
| `waRouting` | Домены, маршруты, `getDomain()` |
| `waRequest` | GET/POST/param/server |
| `waViewController` | Контроллер с шаблоном |
| `waLayout` | Обёртка layout (index.html) |
| `waModel` | Работа с БД |

---

## Где что лежит

| Задача | Куда смотреть |
|--------|---------------|
| URL → app | `wa-config/routing.php` |
| URL внутри shop | `wa-apps/shop/lib/config/routing.php` |
| Настройки домена (SSL) | `wa-config/apps/site/domains/{domain}.php` |
| Шаблон страницы | `wa-data/public/{app}/themes/{theme}/` |
| Плагин shop | `wa-apps/shop/plugins/{name}/` |
| Хук/событие | `@event` в шаблонах, `wa()->event()` в PHP |
| Ошибки | `wa-log/error.log` |
| Кеш шаблонов | `wa-cache/` |

---

## Smarty в темах

```smarty
{$wa->shop->products(...)}     {* список товаров *}
{$wa->shop->product($id)}      {* полный товар — дорого, N+1 *}
{include file="list-thumbs.html" products=$products}
{* @event frontend_homepage.%plugin_id% *}
```

Переменные parent theme: `$wa_parent_theme_url`, `$wa_parent_theme_path`, `$wa_active_theme_url`.

---

## Бекенд

URL: `http://localhost:8080/webasyst/` (или prod домен).  
Приложения: Shop-Script, Site, Blog, Installer, Contacts...

---

## Отладка

- `wa-config/config.php` → `'debug' => true` (осторожно на prod)
- Лог: `waLog::log($msg, 'custom.log')`
- Очистка кеша: удалить `wa-cache/*`

---

## Типичные грабли (наш проект)

1. **Дочерняя тема shop без index.html** — layout из site-темы; если site domain не настроен, шапка ломается.
2. **short_open_tag** — PHP 7.2 local: Off; файлы только `<?php`.
3. **Удалённая БД** — каждый `$wa->shop->product()` = несколько round-trip × 30ms.
4. **rsync** — затирает local-only конфиги.
