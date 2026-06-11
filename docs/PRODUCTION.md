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
find wa-cache -mindepth 1 -delete
sudo -u almamed.su php cli.php cache clear
chown -R almamed.su:almamed.su wa-cache wa-log
```

Pull делать **от root** допустимо, но после — **обязательно chown**.  
`php cli.php cache clear` — **только от пользователя `almamed.su`**, иначе кеш снова root-owned → «Ошибка #0» в магазине и **HTML-кеш категорий не читается** (Webasyst требует `is_writable` на файл кеша).

```bash
# Проверка кеша категории после деплоя (2-й запрос должен быть category-hit и TTFB < 0.05s)
curl -s -D - -o /dev/null -H "Host: almamed.su" -k "https://213.139.209.184/category/veterinariya/" | grep -i x-shop-cache
curl -s -D - -o /dev/null -H "Host: almamed.su" -k "https://213.139.209.184/category/veterinariya/" | grep -i x-shop-cache
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

Sitemap seofilter: URL фильтров в `https://almamed.su/filter-sitemap.xml` — добавить в Google Search Console отдельно (WA 1.14.4 не включает его в главный `sitemap.xml` автоматически).

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
