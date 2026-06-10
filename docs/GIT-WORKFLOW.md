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
```

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

Картинки товаров, свежий дамп БД — **не через git**:

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

## БД

| Файл | Назначение |
|------|------------|
| `wa-config/db.php` | active (local или remote) |
| `wa-config/db.php.local` | 127.0.0.1, almamed/localdev |
| `wa-config/db.php.remote` | prod MySQL 45.90.35.63 |
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
