# Production — almamed.su

---

## Сервер

С **2026-11-02** прод — бывший запасной сервер. Старый Beget (`213.139.209.184`, пользователь `almamed.su`, PHP 7.2) недоступен.

| | |
|---|---|
| Панель | **FastPanel 2** |
| SSH | `ssh redbox-db` → `root@178.250.157.140`, ключ `~/.ssh/id_ed25519_redbox_178` |
| Путь сайта | `/var/www/almamed_su_usr/data/www/almamed.su/` |
| Unix user/group | `almamed_su_usr` |
| PHP | **8.2** (`/opt/php82/bin/php-cgi`, обёртка сайта `/var/www/almamed_su_usr/data/php-bin/almamed.su/php`) |
| Git | `main` @ github.com/bziksv/almamed |
| Deploy-ключ | `/root/.ssh/id_ed25519_almamed_deploy`, GitHub: **almamed-spare-178**, только чтение |
| DNS A | `almamed.su` → **178.250.157.140** |

---

## Сеть

| | |
|---|---|
| DNS A | **178.250.157.140** ← almamed.su |
| Nginx listen | `178.250.157.140:80`, `:443` |
| MySQL | локально на этом же сервере, база `almamed_su` |

⚠️ **45.90.35.63** — IP на том же nginx, но **другой vhost**. Для проверки almamed использовать **213.139.209.184** + `Host: almamed.su`.

⚠️ `curl http://127.0.0.1/` на prod → **connection refused** (nginx не слушает localhost).

---

## Стек запроса

```
Client → nginx (178.250.157.140:443)
       → Apache httpd (php-cgi /opt/php82/)
       → index.php → Webasyst
```

---

## Проверка с prod-сервера

```bash
curl -sk --resolve almamed.su:443:178.250.157.140 -o /dev/null -w "HTTPS %{http_code}\n" https://almamed.su/
```

Не проверять через `127.0.0.1` без `Host: almamed.su` и публичного IP: на 443 без нужного vhost отвечает другой сайт.

---

## Деплой

```bash
ssh redbox-db
cd /var/www/almamed_su_usr/data/www/almamed.su
git pull --ff-only origin main
chown -R almamed_su_usr:almamed_su_usr .
find wa-cache -mindepth 1 -delete
```

OPcache здесь `validate_timestamps=On`, после pull новый PHP подхватывается без `opcache_reset`. На старом Beget было наоборот (`validate_timestamps=0`).

Проверка: `curl -sk --resolve almamed.su:443:178.250.157.140 -o /dev/null -w '%{http_code}\n' https://almamed.su/`.

> На **старом Beget** OPcache был с `validate_timestamps=0`: после pull без
> `opcache_reset()` через web исполнялся старый PHP. На новом сервере это не так.

**Почему `bump-theme-edition.php`:** nginx кэширует `/wa-data/public/` на **1 год** (`immutable`). Браузер обновит JS/CSS только если в HTML новый `?v=`. Версия берётся из `edition` в `theme.xml` — при деплое через git/rsync она **сама не растёт** (только через редактор тем в админке). Консоль с «Disable cache» обходит кэш — отсюда иллюзия, что «помогает только F12».

Pull делать **от root** допустимо, но после — **обязательно chown** на `almamed_su_usr`.  
`php cli.php webasyst clearCache` — от пользователя сайта, бинарник `/opt/php82/bin/php`, не `/usr/bin/php` (это PHP 5.4).

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

### 504 / upstream timed out (nginx → Apache:81)

Симптом: в `almamed.su-frontend.error.log` — `upstream timed out … while reading response header from upstream` на `127.0.0.1:81`; в access — HTTP 504 (витрина + `/webasyst/` виджеты). При полном забитии PHP-слотов — **503** (`mod_fcgid: can't apply process slot`).

Причина 2026-10-28…29: мало php-cgi на класс + короткий **proxy_read_timeout** + пик скрейперов (AI/магазинные боты, `/category` `/search` `/brand`), которые держат воркеры и душат админку.

Актуальные значения (после фикса 2026-10-29):

| Параметр | Файл | Значение |
|----------|------|----------|
| `FcgidMaxProcesses` | `/etc/httpd/conf.d/fcgid.conf` | **120** (глобально на весь httpd) |
| `FcgidMaxProcessesPerClass` | то же | **40** (потолок на almamed) |
| `proxy_read_timeout` (витрина) | `almamed.su.conf` location `/` и `@fallback` | **90s** |
| `proxy_read_timeout` (админка) | `almamed.su.includes` `location ^~ /webasyst/` | **120s** |
| search / suggest | `almamed.su.includes` | **90s** + прежние `limit_req` |

⚠️ Расширение fcgid **не заменяет** блок ботов: при флуде 40 слотов всё равно кончаются за минуты → снова 503. Сначала режут UA/IP (ниже), лимиты — запас.

⚠️ FastPanel при «пересохранении сайта» может затереть `almamed.su.conf` / includes / fcgid — сверять бэкапы `*.bak-*` рядом с файлами.

### Блок AI / скрейперов (nginx, 2026-10-29)

Цель: не пускать в PHP ботов, которые жрут слоты и дают 504/503. **Поисковики не трогаем.**

| Что | Где |
|-----|-----|
| `map $http_user_agent $almamed_bad_bot` | `/etc/nginx/conf.d/almamed-botblock.conf` |
| `if ($almamed_bad_bot) { return 403; }` | `/etc/nginx/fastpanel2-sites/almamed.su/almamed.su.conf` (+ available-копия) |
| `deny 131.153.11.133` | тот же `almamed.su.conf` — бренд-скрейпер (200 по `/brand/*`, Chrome UA) |

**403 (заблокированы):** `GeedoShopProductFinder`, `KakaoBot`, `ElevenlabsCrawler`, `Amazonbot`, `meta-externalagent`, `meta-externalads`, `Bytespider`, `ClaudeBot`, `DotBot`, `SemrushBot`, `AhrefsBot` + IP `131.153.11.133`.

**200 (не в map / разрешены):** Googlebot, YandexBot / YandexImages, bingbot, Mail.RU_Bot, PetalBot, PerplexityBot, **GPTBot** (разбан 2026-10-29). Обычный браузерный UA — тоже 200.

`SemrushBot` / `AhrefsBot` — SEO-краулеры (не поисковая выдача), режем намеренно.

Проверка после правок:

```bash
# поисковики → 200
curl -sk -o /dev/null -w "%{http_code}\n" -A "Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)" -H "Host: almamed.su" https://213.139.209.184/
curl -sk -o /dev/null -w "%{http_code}\n" -A "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" -H "Host: almamed.su" https://213.139.209.184/
# скрейпер → 403
curl -sk -o /dev/null -w "%{http_code}\n" -A "GeedoShopProductFinder" -H "Host: almamed.su" https://213.139.209.184/
```

**fail2ban** (`nginx-siteprobe` / `nginx-botsearch`) ловит только зонды (`/wp-login`, `/.env`) и 404-botsearch — **не** вежливый обход `/brand|/category` с 200. Для объёмных скрейперов нужен этот nginx-блок или отдельный rate-jail; fail2ban их сам не банит.

### Search gate — капча на прямой `/search/`

Файл: `wa-apps/shop/lib/classes/shopSearchGate.class.php` (хуки в `shopFrontendSearch` + SearchPro page).

Прямой GET/HEAD на `/search/…` без same-site Referer и без cookie `am_sg` → страница с картинкой-капчей (не полный PHP-поиск). После успеха — cookie 14 дней. Same-site Referer (`almamed.su` / localhost) или UA поисковика → проход без капчи.

С 2026-10-29: на **любой** `/search/…` (раньше только при `?sort=`).

Добавить нового бота: строка `~*ИмяБота 1;` в `almamed-botblock.conf` → `nginx -t && nginx -s reload`.

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
