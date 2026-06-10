# almamed.su — документация проекта

> Живая шпаргалка для разработки. Обновлять по мере изменений.  
> Официальная дока Webasyst: [developers.webasyst.ru/docs](https://developers.webasyst.ru/docs/)

---

## Что это

**almamed.su** — интернет-магазин медоборудования на **Webasyst + Shop-Script**.

| Среда | Домен | PHP | Путь на сервере |
|-------|-------|-----|-----------------|
| Production | almamed.su | 7.2 | `/var/www/almamed.su/data/www/almamed.su/` |
| Dev (staging) | dev.almamed.su | — | тема `main_05_2021` |
| Test | almamed-test.ru | — | тема `main_05_2021` |
| Local | localhost:8080 | **7.2** | `~/Documents/projects/almamed/` |

---

## Версии (актуально на 2026-06-10)

| Компонент | Версия |
|-----------|--------|
| **Webasyst (framework)** | 1.14.4.556 |
| **Shop-Script** | 7.5.1.287 |
| **Site** | 2.5.18.229 |
| **Blog** | 1.4.5.42 |
| **Installer** | 1.14.4.556 |
| Contacts | 1.1.6 |
| Logs | 1.1.2 |
| Priceparse | 1.0.0 (кастом) |

Проверить версии:
```bash
/opt/homebrew/opt/php@7.2/bin/php -r "
require 'wa-config/SystemConfig.class.php';
\$wa = waSystem::getInstance(null, new SystemConfig());
echo wa()->getVersion('webasyst'), PHP_EOL;
echo wa()->getVersion('shop'), PHP_EOL;
"
```

---

## Установленные приложения

Из `wa-config/apps.php`:

`shop`, `site`, `blog`, `contacts`, `installer`, `logs`, `priceparse`, `docs`, `checklists`, `stickies`, `clock`, `news`, `weather`

Основные для фронта: **shop** (витрина), **site** (CMS/страницы), **blog** (новости, врачи, болезни).

---

## Как устроен Webasyst (кратко)

По [документации разработчика](https://developers.webasyst.ru/docs/):

1. **Фронтенд** — публичный сайт. Запрос → `index.php` → маршрутизация → приложение → тема (Smarty).
2. **Бекенд** — админка по `/webasyst/`.
3. **Приложение** — shop, site, blog и т.д. в `wa-apps/`.
4. **Плагин** — расширение приложения в `wa-apps/{app}/plugins/`.
5. **Тема** — HTML/CSS/JS шаблоны в `wa-data/public/{app}/themes/` (или `wa-apps/{app}/themes/` до кастомизации).

### Маршрутизация

Два уровня ([дока: маршрутизация фронтенда](https://developers.webasyst.ru/docs/cookbook/routing/)):

| Уровень | Файл | Что делает |
|---------|------|------------|
| Системный | `wa-config/routing.php` | Какой app обрабатывает URL на каком домене |
| Внутри app | `wa-apps/{app}/lib/config/routing.php` | URL → module/action внутри приложения |

**almamed.su** (prod): правило `*` → **shop**, тема `osnovnaja_new_header_footer_form`.  
Блог: `novosti/*`, `doctors/*`, `bolezn/*`, `company-news/*`, `punkty-vydachi/*`.

**Локально:** alias `$routes['localhost:8080'] = $routes['almamed.su'];` в конце `routing.php`.

Псевдонимы доменов: `'alias.ru' => 'main.ru'` — alias использует правила основного домена.

### Темы (родитель / дочерняя)

Продакшн-тема витрины: **`osnovnaja_new_header_footer_form`** (Profitbuy / wm-site).

- Shop-тема — **дочерняя**, родитель: `site:osnovnaja_new_header_footer_form`
- `index.html` и общий CSS берутся из **site-темы** (`parent="1"` в `theme.xml`)
- Кастомные файлы лежат в `wa-data/public/shop/themes/osnovnaja_new_header_footer_form/`
- Site-тема: `wa-data/public/site/themes/osnovnaja_new_header_footer_form/`

[Дока: родительские и дочерние темы](https://developers.webasyst.ru/docs/cookbook/themes/parent-themes/)

---

## Структура каталогов

```
almamed/
├── index.php              # фронт-контроллер
├── start-dev.sh           # nginx + php-fpm 7.2 (local)
├── stop-dev.sh            # остановка local stack
├── .local/                # nginx/php-fpm конфиги (только local)
│   ├── nginx/nginx.conf
│   ├── php/               # fpm pool, opcache
│   └── run/               # pid, сгенерированные конфиги (gitignore)
├── router.php             # legacy: router для php -S (не используется)
├── clickfrogru_udp_tcp.php # антибот (отключён на localhost)
├── wa-config/             # конфиги (НЕ в git — секреты)
│   ├── routing.php        # маршруты по доменам
│   ├── db.php             # БД
│   ├── config.php         # debug, mod_rewrite
│   └── apps/site/domains/ # настройки доменов (ssl_all и т.д.)
├── wa-apps/               # приложения и плагины (код)
│   └── shop/
│       ├── lib/classes/shopCustom.class.php  # кастомная логика
│       └── plugins/       # 44 плагина
├── wa-data/public/        # темы, загрузки, публичные данные
│   ├── shop/themes/
│   └── site/themes/
├── wa-cache/              # кеш (не синкать / не коммитить)
├── wa-log/                # логи
└── docs/                  # эта документация
```

---

## Плагины Shop-Script (44)

`seo`, `searchpro`, `productbrands`, `yandexmarket`, `cml1c`, `form`, `breadcrumbs`, `watermark`, `wholesale`, `related`, `slider`, `yoss`, `xml`, `wmimageincat`, `vendorlink`, `wait`, `seofilter`, `seofield`, `quickeditor`, `productmanager`, `producthidden`, `productfeatures`, `productadditional`, `plugmein`, `nbpopupform`, `invoiceru`, `ic`, `error301`, `emptycategory`, `emptybrand`, `editprice`, `easyinvoicephys`, `directedit`, `dev`, `descriptionmanager`, `description`, `customfield`, `currencyrates`, `copyproduct`, `consignmentru`, `cartsreport`, `carts`, `article`, `accordion`

Критичные для SEO/витрины: **seo**, **searchpro**, **seofield**, **seofilter**, **breadcrumbs**.

---

## Локальная разработка

### Запуск (агент делает сам, не пользователь)

```bash
./start-dev.sh
# → http://localhost:8080/
# nginx + php-fpm 7.2 (OPcache), порт FPM 9072
./stop-dev.sh   # остановить
```

Стек как на prod: **nginx** отдаёт статику параллельно, **php-fpm** с **OPcache**.  
Конфиги в `.local/` — при старте подставляются абсолютные пути в `.local/run/`.

Проверка после старта:
```bash
curl -sS -o /tmp/check.html -w "HTTP %{http_code}\n" http://localhost:8080/
grep -c globalheader /tmp/check.html
curl -sS http://localhost:8080/.local/opcache-check.php   # opcache_enabled=1
```

### Локальные патчи (не деплоить на prod как есть)

| Файл | Зачем |
|------|-------|
| `.local/` | nginx + php-fpm + opcache конфиги |
| `start-dev.sh` / `stop-dev.sh` | запуск/остановка local stack |
| `router.php` | legacy для `php -S` (больше не используется) |
| `wa-config/routing.php` | alias `localhost:8080` |
| `wa-config/apps/site/domains/localhost:8080.php` | `ssl_all => false` |
| `index.php` | skip `clickfrogru_udp_tcp.php` на localhost |

### База данных

Local → **MySQL 8.0** на `127.0.0.1:3306`, БД `almamed_su_db`, user `almamed` / `localdev`.

> MySQL 9 несовместим с PHP 7.2. Локально: **mysql@8.0** (`brew services stop mysql`).  
> `sql_mode` без `ONLY_FULL_GROUP_BY` (как на prod) — иначе Shop-Script падает с **error 3065**.

| Файл | Назначение |
|------|------------|
| `wa-config/db.php` | local |
| `wa-config/db.php.remote` | prod backup |
| `.db-dump/almamed_su_db.sql.gz` | дамп ~91 MB |

⚠️ **rsync с prod перезаписывает `db.php`** — после синка вернуть local.

### Известные проблемы local

| Проблема | Причина | Статус |
|----------|---------|--------|
| Медленная загрузка ассетов | раньше `php -S` (один процесс, без OPcache) | **исправлено** — nginx + php-fpm |
| N+1 на списках товаров | `list-thumbs.html` — `$wa->shop->product($p.id)` на каждый товар | открыто |
| Шапка ≠ prod | alias домена + ssl_all редирект на https | **исправлено** 2026-06-10 |
| shopCustom 500 | был `<?` вместо `<?php` (short_open_tag Off) | **исправлено** |

### Очистка кеша

```bash
rm -rf wa-cache/*
# + перезапуск start-dev.sh
```

---

## Кастомный код

| Что | Где |
|-----|-----|
| Кастомная логика магазина | `wa-apps/shop/lib/classes/shopCustom.class.php` |
| Шаблон списка товаров (N+1) | `wa-data/public/shop/themes/.../list-thumbs.html:109` |
| Главная витрины | `wa-data/public/shop/themes/.../home.html` |
| Шапка (site parent) | `wa-data/public/site/themes/.../index.html` |

---

## Синхронизация с prod

```bash
/opt/homebrew/bin/rsync -avc --info=progress2 --stats \
  --exclude 'wa-cache/' --exclude 'wa-log/' --exclude 'nginx_cache/' \
  root@45.90.35.63:/var/www/almamed.su/data/www/almamed.su/ \
  ~/Documents/projects/almamed/
```

После rsync: восстановить `db.php`, `routing.php` alias, `router.php`, `start-dev.sh`.

---

## Git

Репозиторий инициализирован, коммитов нет. `.gitignore` — доработать (исключить `wa-cache`, `wa-log`, `wa-config/db.php`, uploads).

---

## Полезные ссылки Webasyst

- [Документация разработчика](https://developers.webasyst.ru/docs/)
- [Маршрутизация фронтенда](https://developers.webasyst.ru/docs/cookbook/routing/)
- [Родительские темы](https://developers.webasyst.ru/docs/cookbook/themes/parent-themes/)
- [Файловая структура](https://developers.webasyst.ru/docs/cookbook/files/) *(раздел в общем оглавлении)*

---

## Changelog доки

| Дата | Что |
|------|-----|
| 2026-06-10 | Первая версия: версии, структура, routing, темы, local dev, плагины |
