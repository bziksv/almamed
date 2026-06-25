# Production — almamed.su

---

## Сервер

| | |
|---|---|
| Панель | **FastPanel 2** |
| Путь сайта | `/var/www/almamed.su/data/www/almamed.su/` |
| Unix user/group | `almamed.su` / `almamed.su` |
| PHP | **7.2** (php-cgi через Apache suexec) |
| Git | `main` @ github.com/bziksv/almamed |

---

## Сеть

| | |
|---|---|
| DNS A | **213.139.209.184** ← almamed.su |
| Nginx listen | `213.139.209.184:80`, `:443 ssl http2` |
| Nginx config | `/etc/nginx/fastpanel2-available/almamed.su/almamed.su.conf` |
| MySQL host (db.php) | `45.90.35.63` (на том же или связанном сервере) |

⚠️ **45.90.35.63** — IP на том же nginx, но **другой vhost**. Для проверки almamed использовать **213.139.209.184** + `Host: almamed.su`.

⚠️ `curl http://127.0.0.1/` на prod → **connection refused** (nginx не слушает localhost).

---

## Стек запроса

```
Client → nginx (213.x:443, SSL, http2)
       → Apache httpd (php-cgi /opt/php72/)
       → index.php → Webasyst
```

---

## Проверка с prod-сервера

```bash
# HTTP
curl -sS -L -o /dev/null -w "HTTP %{http_code}\n" \
  -H "Host: almamed.su" http://213.139.209.184/

# HTTPS
curl -sS -L -o /dev/null -w "HTTPS %{http_code}\n" \
  -H "Host: almamed.su" -k https://213.139.209.184/
```

302 на HTTP → норма (редирект на HTTPS). С `-L` должно дойти до 200.

---

## Деплой

```bash
cd /var/www/almamed.su/data/www/almamed.su
git pull origin main
chown -R almamed.su:almamed.su .
php .local/bump-theme-edition.php   # новый ?v= для CSS/JS темы (обязательно!)
find wa-cache -mindepth 1 -delete
sudo -u almamed.su php cli.php webasyst clearCache
chown -R almamed.su:almamed.su wa-cache wa-log

# ⚠️ ОБЯЗАТЕЛЬНО при правках PHP — сброс OPcache (clearCache его НЕ чистит!)
printf '%s' '<?php opcache_reset(); echo "ok";' > _opc.php \
  && chown almamed.su:almamed.su _opc.php \
  && curl -s -A Mozilla -k -H "Host: almamed.su" https://213.139.209.184/_opc.php \
  && rm -f _opc.php
# Альтернатива: systemctl restart php-fpm
```

> 🔴 **OPcache — главная грабля деплоя.** `webasyst clearCache` и `find wa-cache`
> чистят только app-кэш. PHP-байткод живёт в **OPcache** (validate_timestamps=0
> на prod → файлы НЕ перечитываются по mtime). После `git pull` без сброса OPcache
> **исполняется старый код** — правка «не применяется», сколько кэш ни три.
> Сброс — `opcache_reset()` через web-запрос (zero-downtime, проверено) или
> `systemctl restart php-fpm`. CLI `php -r 'opcache_reset()'` **не поможет** —
> у CLI отдельный OPcache, не тот, что обслуживает сайт.

**Почему `bump-theme-edition.php`:** nginx кэширует `/wa-data/public/` на **1 год** (`immutable`). Браузер обновит JS/CSS только если в HTML новый `?v=`. Версия берётся из `edition` в `theme.xml` — при деплое через git/rsync она **сама не растёт** (только через редактор тем в админке). Консоль с «Disable cache» обходит кэш — отсюда иллюзия, что «помогает только F12».

Pull делать **от root** допустимо, но после — **обязательно chown**.  
`php cli.php webasyst clearCache` — **только от пользователя `almamed.su`**

### Приложение «Лог пользователей» (userlog)

Код в git (`wa-apps/userlog/`, плагины `shop/userlog`, `blog/userlog`), но **`wa-config/` не в git** — на prod после первого деплоя userlog нужно вручную:

1. **git pull** — чтобы появилась папка `wa-apps/userlog/`
2. **`wa-config/apps.php`** — строка `'userlog' => true` (или включить в *Настройки → Приложения*)
3. **`wa-config/apps/shop/plugins.php`** и **`blog/plugins.php`** — `'userlog' => true`  
   (plugmein включает только плагины магазина, не само приложение в шапке)
4. **Миграции БД** (от `almamed.su`):
   ```bash
   php cli.php userlog install
   ```
5. **Права** — *Команда* → пользователь → доступ к приложению «Лог пользователей» (backend)
6. **Кеш**: `php cli.php webasyst clearCache`

Проверка на сервере:

```bash
php .local/verify-userlog.php
```

Иконка — в **верхней полосе Webasyst** (рядом с Магазин, Блог, Логи). Прямая ссылка: `https://almamed.su/webasyst/userlog/`

> ⚠️ **Full-page кэш категории ОТКЛЮЧЁН** (`canUseCategoryCache()` → `false`).
> Причина: SEO-плагин формирует `<title>`/description/og и расширенное имя
> категории во время полного рендера (события `frontend_category`→`applyInner`,
> `frontend_head`→`applyOuter` в layout). Кэш хранил только внутренний фрагмент
> без `<head>` → на cache-hit мета была пустая (`<title></title>`). Отдача всегда
> `category-miss`, TTFB ~0.3–0.6s (прочие оптимизации держат скорость).
> **Не включать обратно** без решения проблемы SEO-меты на cache-hit.

```bash
# После деплоя: мета категории должна быть заполнена (title/desc/keywords/og)
curl -s -A "Mozilla/5.0" "https://almamed.su/category/veterinariya/" \
  | grep -oiE '<title>[^<]*</title>|<meta name="(Description|Keywords)" content="[^"]{0,40}'
```

### После деплоя — smoke + TTFB

```bash
curl -sS -L -o /dev/null -w "home %{http_code} %{time_starttransfer}s\n" \
  -H "Host: almamed.su" -k https://213.139.209.184/
curl -sS -o /dev/null -w "category %{time_starttransfer}s\n" \
  -H "Host: almamed.su" -k "https://213.139.209.184/category/ginekologiya/"
curl -sS -o /dev/null -w "search %{time_starttransfer}s\n" \
  -H "Host: almamed.su" -k "https://213.139.209.184/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"
curl -sS -o /dev/null -w "suggest %{time_total}s\n" \
  -H "Host: almamed.su" -k "https://213.139.209.184/searchpro-plugin/suggest/?q=%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF"
curl -sS -o /dev/null -w "static page %{time_starttransfer}s\n" \
  -H "Host: almamed.su" -k "https://213.139.209.184/kontakty/"
```

SearchPro: в настройках витрины `use_v2=1`, `page_results_cache=86400`.

Sitemap seofilter: `filter-sitemap.xml` автоматически в `/sitemap.xml` (при `use_sitemap_hook=1`). Дополнительно можно добавить в GSC; в `robots.txt` — строка `Sitemap: .../filter-sitemap.xml` (опционально).

### Post-deploy smoke (на сервере)

```bash
./.local/verify-deploy.sh
./.local/check-sitemap.sh          # sitemap-shop-1 ~3.5 MB, filter-sitemap отдельно
php .local/opcache-audit.php       # CLI skip на prod — см. php -i | grep opcache
```

---

## Файлы только на prod (не в git)

| Файл | Зачем |
|------|-------|
| `wa-config/*` | db, routing, mail, auth |
| `/.htaccess` | IP-блоки, rewrite (~2700 строк) |
| `wa-data/public/shop/products/` | картинки товаров |
| `wa-cache/`, `wa-log/` | runtime |

---

## Диагностика «сайт не открывается»

1. **Не 127.0.0.1** — проверять `213.139.209.184` + Host
2. `systemctl status nginx httpd`
3. `tail -30 /var/log/nginx/error.log`
4. `tail -30 wa-log/error.log`
5. HTTP/2 empty reply → убрать `http2` из `listen ...443` в vhost, `nginx -t && systemctl reload nginx`
6. После полной очистки wa-cache — 1–2 мин медленная прогрузка (кеш пересобирается)
7. `disable_symlinks if_not_owner` в nginx conf — после git от root нужен `chown -R almamed.su`

---

## Бэкап конфига перед экспериментами

```bash
cp -a wa-config wa-config.bak.$(date +%F)
```

---

## SSH / GitHub

Prod-сервер: отдельный SSH-ключ root → GitHub (account keys или deploy key read-only).
