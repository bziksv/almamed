# Git — workflow almamed.su

Репозиторий: **https://github.com/bziksv/almamed** (private)  
Ветка: **main**

---

## Три слоя (не смешивать)

| Слой | Где | Git |
|------|-----|-----|
| Код (PHP, темы, плагины) | `wa-apps/`, `wa-data/public/*/themes/` | ✅ |
| Конфиг | `wa-config/` | ❌ только на сервере |
| Данные (БД, картинки, кеш) | `.db-dump/`, `wa-data/.../products/` | ❌ rsync/дамп |

---

## Local → GitHub

```bash
cd ~/Documents/projects/almamed

./start-dev.sh
# ... правки ...

git status                    # нет wa-config/, .db-dump/, products/
git add -A
git commit -m "theme: fix header on mobile"
git push origin main
```

SSH ключ: `~/.ssh/id_ed25519` → GitHub account keys.

---

## Prod ← GitHub

```bash
cd /var/www/almamed.su/data/www/almamed.su

git pull origin main
chown -R almamed.su:almamed.su .
find wa-cache -mindepth 1 -delete

# ⚠️ При правках PHP — ОБЯЗАТЕЛЬНО сброс OPcache (clearCache его не чистит!)
printf '%s' '<?php opcache_reset(); echo "ok";' > _opc.php \
  && chown almamed.su:almamed.su _opc.php \
  && curl -s -A Mozilla -k -H "Host: almamed.su" https://213.139.209.184/_opc.php \
  && rm -f _opc.php
```

> 🔴 OPcache на prod не перечитывает файлы по mtime → без сброса исполняется
> старый код после `git pull`. Подробности и альтернативы — `docs/PRODUCTION.md` → Деплой.

SSH ключ на prod (root): отдельный, добавлен в GitHub.

### Первичная настройка prod (сделано 2026-06-10)

```bash
git init
git remote add origin git@github.com:bziksv/almamed.git
git fetch origin
git checkout -f -B main origin/main
chown -R almamed.su:almamed.su .
```

`wa-config/`, `/.htaccess`, uploads — **остаются на диске** (в `.gitignore`).

---

## Что в .gitignore

См. корневой `.gitignore`. Главное:

- `wa-config/*` — пароли, routing prod
- `wa-cache/`, `wa-log/`, `.db-dump/`
- `wa-data/public/shop/*` кроме `themes/`
- `/.htaccess` — prod IP-блоки (2700+ строк)
- `.htaccess` в `wa-apps/` — **в git** (security Deny)

---

## rsync (данные, не код)

Картинки товаров, webp, свежий дамп БД — **не через git**.

**Рекомендуемый сценарий** (remote БД уже подключена + недостающие папки товаров):

```bash
cp .local/sync-prod.env.example .local/sync-prod.env   # один раз, настроить SSH
./.local/sync-from-prod.sh                             # use-db-remote + missing products
```

**Только файлы** (режимы):

```bash
./.local/sync-wa-data-from-prod.sh missing    # товары из БД без папки local (~быстро)
./.local/sync-wa-data-from-prod.sh recent     # последние 50 товаров (--limit N)
./.local/sync-wa-data-from-prod.sh webp       # только *.webp (догнать webpimages)
./.local/sync-wa-data-from-prod.sh products   # весь products/ инкрементально (~13 GB)
./.local/sync-wa-data-from-prod.sh shop       # products + img + brands + themes …
```

Полный rsync всего сайта (затирает `db.php`):

```bash
rsync -avc --exclude 'wa-cache/' --exclude 'wa-log/' --exclude 'wa-config/' \
  root@45.90.35.63:/var/www/almamed.su/data/www/almamed.su/ \
  ~/Documents/projects/almamed/
```

После rsync восстановить local-only:

```bash
cp wa-config/db.php.local wa-config/db.php
# routing.php — alias localhost в конце файла
./start-dev.sh
```

---

## Облегчённый local dev (~25+ GB)

Удалить локальные картинки/uploads, оставить темы. Nginx отдаёт отсутствующие файлы из `wa-data/public/` с prod.

```bash
./.local/setup-light-dev.sh              # интерактивно
./.local/setup-light-dev.sh --yes        # без подтверждения
./.local/setup-light-dev.sh --dry-run    # только показать объём

./start-dev.sh                           # proxy: PROD_MEDIA_HOST=almamed.su
./.local/use-db-remote.sh                # опционально: prod БД
```

Точечно вернуть файлы офлайн:

```bash
./.local/sync-wa-data-from-prod.sh missing
./.local/sync-wa-data-from-prod.sh recent --limit 20
```

**Не удаляется:** `wa-data/public/*/themes/` (в git).  
**Proxy не покрывает:** `wa-data/protected/` (скачивания через PHP) — для них нужен rsync при необходимости.

### Git vs prod

| Что | В git | На prod после `git pull` |
|-----|-------|---------------------------|
| `.local/setup-light-dev.sh`, `.local/nginx/nginx.conf`, `start-dev.sh` | ✅ | **Не используется** — только local dev |
| Код плагинов (`wa-apps/shop/plugins/…`) | ✅ | Работает после pull + clearCache |
| `wa-config/apps/shop/plugins.php` | ❌ | Включение плагинов вручную на сервере |
| `wa-data/public/shop/products/` и др. медиа | ❌ | **На prod не трогать** — там свой полный `wa-data` |

⚠️ **`setup-light-dev.sh` на prod не запускать** — скрипт удаляет локальные картинки; на сервере это сломает витрину.

Коммит light-dev в git нужен **команде** (одинаковый local dev у всех), а не деплою медиа на prod.

### Как работает proxy картинок

1. HTML отдаёт **относительные** URL: `/wa-data/public/shop/products/...`
2. Браузер запрашивает `http://localhost:8080/wa-data/...`
3. Nginx (`.local/nginx/nginx.conf`): файл есть локально → с диска; **нет** → proxy на `https://almamed.su` (`PROD_MEDIA_HOST` в `.local/sync-prod.env`)
4. Темы (`wa-data/public/*/themes/`) — **только локально**, proxy не применяется

Проверка после `setup-light-dev` + `start-dev.sh`:

```bash
# локального файла нет, но 200 — тянется с prod
curl -sS -o /dev/null -w "HTTP %{http_code}\n" \
  "http://localhost:8080/wa-data/public/shop/products/99/15/1599/images/75162/75162.750.jpg"
```

В выводе `start-dev.sh` должно быть: `OK: media proxy … → HTTP 200`.

---

## БД

| Файл | Назначение |
|------|------------|
| `wa-config/db.php` | active (local или remote) |
| `wa-config/db.php.local` | 127.0.0.1, almamed/localdev |
| `wa-config/db.php.remote` | prod MySQL 45.90.35.63 |
| `.local/use-db-remote.sh` | переключить на prod БД + clearCache |
| `.local/use-db-local.sh` | вернуть local MySQL |
| `.local/sync-wa-data-from-prod.sh` | rsync картинок/webp с prod |
| `.local/setup-light-dev.sh` | удалить тяжёлые wa-data + proxy медиа с prod |
| `.local/sync-from-prod.sh` | remote БД + missing product dirs |
| `.db-dump/almamed_su_db.sql.gz` | дамп ~91 MB |

---

## Чеклист деплоя

**Local перед push:**
- [ ] `./start-dev.sh` — OK
- [ ] curl главная 200, категория 200
- [ ] `git status` — нет секретов

**Prod после pull:**
- [ ] `git log -1` — нужный commit
- [ ] `ls wa-config/db.php` — на месте
- [ ] `chown -R almamed.su:almamed.su .`
- [ ] `curl -H "Host: almamed.su" http://213.139.209.184/` → 302/200
- [ ] https://almamed.su/ в браузере
