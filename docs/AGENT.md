# Шпаргалка агента — almamed.su

> Быстрая ориентация для Cursor Agent. Полная дока: [README.md](README.md).

---

## Суть проекта

| | |
|---|---|
| Сайт | almamed.su — медоборудование |
| Стек | Webasyst 1.14.4 + Shop-Script 7.5.1, **PHP 7.2** |
| Prod тема | `osnovnaja_new_header_footer_form` (shop child of site) |
| Git | https://github.com/bziksv/almamed (private) |

---

## Пути

| Среда | Путь |
|-------|------|
| **Local** | `~/Documents/projects/almamed/` |
| **Prod** | `/var/www/almamed.su/data/www/almamed.su/` |
| **Prod owner** | `almamed.su:almamed.su` |

---

## Local dev — команды

```bash
cd ~/Documents/projects/almamed
./start-dev.sh    # nginx + php-fpm 7.2, OPcache, :8080
./stop-dev.sh
```

- URL: **http://localhost:8080/** (только HTTP)
- БД: `127.0.0.1`, `almamed` / `localdev`, `almamed_su_db` (MySQL **8.0**, не 9)
- Переключение БД:
  - `./.local/use-db-remote.sh` — боевая MySQL (45.90.35.63), видны правки из prod-админки; медленнее
  - `./.local/use-db-local.sh` — local MySQL 127.0.0.1
  - вручную: `cp wa-config/db.php.local|db.php.remote wa-config/db.php` + `php cli.php webasyst clearCache`
- **Файлы с prod** (картинки, webp — БД remote, файлы local):
  - `cp .local/sync-prod.env.example .local/sync-prod.env` + SSH-ключ на `root@45.90.35.63`
  - `./.local/sync-from-prod.sh` — remote БД + папки товаров, которых нет local
  - `./.local/sync-wa-data-from-prod.sh missing|recent|webp|products` — см. `docs/GIT-WORKFLOW.md`
  - `./.local/setup-light-dev.sh` — удалить ~28 GB wa-data локально, картинки с prod через nginx proxy (подробно: [GIT-WORKFLOW.md](GIT-WORKFLOW.md) § «Облегчённый local dev»)

---

## Чеклист перед сдачей (блокер)

**Не писать «готово» без цифр.**

```bash
./start-dev.sh
curl -sS -o /tmp/check.html -w "главная HTTP %{http_code} %{time_total}s\n" --max-time 30 http://localhost:8080/
grep -c globalheader /tmp/check.html          # > 0
grep -c 'pages-top\|link-top' /tmp/check.html  # > 0
curl -sS -o /dev/null -w "категория HTTP %{http_code}\n" -L --max-redirs 3 --max-time 30 \
  http://localhost:8080/category/anesteziologiya/
curl -sS -o /dev/null -w "jquery HTTP %{http_code}\n" \
  http://localhost:8080/wa-content/js/jquery/jquery-1.11.1.min.js
```

Ожидание: главная **200** >100KB ~1–2s, категория **200**, jquery **200**.

При fail → `wa-log/error.log`, `.local/run/nginx-error.log` → чинить → повтор.

---

## Prod — деплой

```bash
cd /var/www/almamed.su/data/www/almamed.su
git pull origin main
chown -R almamed.su:almamed.su .
find wa-cache -mindepth 1 -delete
sudo -u almamed.su php cli.php webasyst clearCache
chown -R almamed.su:almamed.su wa-cache wa-log
```

Проверка **на prod** (не 127.0.0.1 — nginx слушает IP сайта):

```bash
curl -sS -L -o /dev/null -w "HTTP %{http_code}\n" -H "Host: almamed.su" http://213.139.209.184/
curl -sS -L -o /dev/null -w "HTTPS %{http_code}\n" -H "Host: almamed.su" -k https://213.139.209.184/
```

- DNS `almamed.su` → **213.139.209.184**
- Nginx vhost: `/etc/nginx/fastpanel2-available/almamed.su/almamed.su.conf`
- Стек: nginx (frontend) + **httpd** (Apache, php-cgi 7.2 для almamed.su)
- **45.90.35.63** — другой vhost, не использовать для проверки almamed

---

## Git — local

```bash
git add -A && git status   # нет wa-config/, wa-cache/, .db-dump/, products/
git commit -m "описание"
git push origin main
```

---

## Где править что

| Задача | Файл |
|--------|------|
| Шапка, layout | `wa-data/public/site/themes/osnovnaja_new_header_footer_form/index.html` |
| Меню сверху | `links.pages.top.html`, `links.pages.top.fix.html` |
| Главная shop | `wa-data/public/shop/themes/osnovnaja_new_header_footer_form/home.html` |
| Список товаров | `wa-data/public/shop/themes/.../list-thumbs.html` |
| **Params категорий** | [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md) — **не ломать** |
| **Заявки (админка)** | [LEADS.md](LEADS.md) — плагин `shop/plugins/leads`, URL `?plugin=leads&module=report` |
| Кастом PHP shop | `wa-apps/shop/lib/classes/shopCustom.class.php` |
| Маршруты доменов | `wa-config/routing.php` |
| SSL редирект | `wa-config/apps/site/domains/almamed.su.php` |
| БД | `wa-config/db.php` (**не в git**) |
| Local nginx | `.local/nginx/nginx.conf` |

### Local-only (не ломать prod)

| Файл | Зачем |
|------|-------|
| `wa-config/routing.php` | alias `localhost:8080` → `'almamed.su'` (**строка**, не массив!) |
| `wa-config/auth.php` | auth для localhost |
| `wa-config/db.php.local` | local MySQL |
| `.local/`, `start-dev.sh` | dev stack |
| `index.php` | skip clickfrog на localhost — **безопасно на prod** |

---

## Архитектура темы

```
site:osnovnaja_new_header_footer_form  ← index.html, шапка, CSS parent
    └── shop:osnovnaja_new_header_footer_form  ← home, category, product (child)
```

Shop-тема **без своего index.html** — layout из site parent.

---

## Производительность

| БД | TTFB главной |
|----|----------------|
| Local MySQL | ~1.3 s |
| Remote MySQL (45.90.35.63) | ~112 s (N+1 × RTT) |

Причина (частично исправлено): SEO-плагины + тяжёлые коллекции.  
N+1 `$wa->shop->product($p.id)` в `list-thumbs.html` заменён на `shopCustom::getProductParamsByIds()`.

**Params категорий** (plitka, rec, h1, …) — см. [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md). Не ломать при оптимизации.  
**Чеклист оптимизации** — [OPTIMIZATION-CHECKLIST.md](OPTIMIZATION-CHECKLIST.md) (обновлять при новых находках).

---

## .gitignore — что НЕ в git

`wa-config/`, `wa-cache/`, `wa-log/`, `.db-dump/`, `wa-data/public/shop/products/` (13 GB), `/.htaccess` (prod IP-блоки).

**В git:** код, плагины, `wa-data/public/*/themes/`.

---

## Типичные грабли

1. `https://localhost:8080` → ERR_CONNECTION_CLOSED
2. `$routes['localhost:8080'] = $routes['almamed.su']` — **ломает** шапку (копия массива). Нужно: `= 'almamed.su'`
3. rsync с prod затирает `db.php`, routing aliases
4. prod curl на `127.0.0.1:80` → refused (nginx на IP 213.x, не localhost)
5. После `git pull` от root → `chown almamed.su:almamed.su`
6. Полная очистка `wa-cache` → 1–2 мин медленная прогрузка
7. `short_open_tag` Off → только `<?php`

---

## Логи

| | |
|---|---|
| Webasyst | `wa-log/error.log` |
| Local nginx | `.local/run/nginx-error.log` |
| Prod nginx | `/var/log/nginx/error.log`, vhost error_log в fastpanel conf |

---

## Документация

| Файл | Содержание |
|------|------------|
| [README.md](README.md) | Общая дока проекта |
| [WEBASYST-CHEATSHEET.md](WEBASYST-CHEATSHEET.md) | Webasyst API/поток |
| [GIT-WORKFLOW.md](GIT-WORKFLOW.md) | Git local ↔ prod |
| [PRODUCTION.md](PRODUCTION.md) | Prod-сервер, FastPanel, IP |
| [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md) | Доп. параметры категорий (plitka, rec, h1, …) |
| [LEADS.md](LEADS.md) | Плагин «Заявки» — журнал форм КП / ostavit-zayavku |
| [OPTIMIZATION-CHECKLIST.md](OPTIMIZATION-CHECKLIST.md) | **Чеклист оптимизации** (живой, обновлять при находках) |
| `.cursor/rules/category-params.mdc` | Защита params категорий (always apply) |
| `.cursor/rules/external-scripts.mdc` | **Не трогать Метрику**; другие внешние скрипты — только после согласования |
