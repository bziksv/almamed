# SearchPro — чеклист полного переписывания

> Цель: плагин **не должен тормозить ни HTML, ни UX**.  
> Контекст аудита: [OPTIMIZATION-CHECKLIST.md](OPTIMIZATION-CHECKLIST.md) · params категорий: [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md)

**Текущий масштаб:** ~71 PHP-файл, ~12K строк, `frontend.field.js` 120 KB (webpack-монолит), `FrontendPage.action.php` ~1100 строк.

**Стратегия:** переписать **изнутри того же плагина** (`wa-apps/shop/plugins/searchpro/`), сохранив внешний контракт — URL, `{shopSearchproPluginViewHelper::field()}`, шаблоны темы `searchpro_plugin_*.html`, настройки в БД. Старый код удалять по фазам, не одним коммитом.

---

## Целевые метрики (prod, warm)

| Что | Сейчас (оценка) | Цель |
|-----|-----------------|------|
| `field()` в TTFB категории | +200–2000 ms (popular SQL, Smarty, optional getTree) | **0 SQL, <5 ms** — только HTML-оболочка |
| `searchpro-plugin/config/` | секунды (PHP bootstrap + `{rand()}`) | **убрать endpoint** или **<50 ms**, Cache-Control 24h |
| `searchpro-plugin/dropdown/` warm | 0.6–3+ s | **<150 ms** |
| `/search/<query>/` warm | ~1.5–3 s | **<400 ms** (HTML cache + lean finder) |
| JS на первой загрузке | config + 120 KB field.js sync chain | **≤25 KB** total, lazy после idle/focus |
| `shop_searchpro_query` getVisible | ~240 ms / 157K rows без индекса | **<2 ms** |

---

## Что сохраняем (контракт)

| Компонент | Нельзя ломать |
|-----------|---------------|
| Routing | `search/<query>/`, `search/<cat>/<query>/`, `searchpro-plugin/*` |
| ViewHelper | `shopSearchproPluginViewHelper::field()` |
| Тема | `searchpro_plugin_field.html`, `searchpro_plugin_page.html`, CSS в `wa-data/.../themes/` |
| Breadcrumbs | `shopBreadcrumbsSearchproBreadcrumbsElement` |
| Настройки | `shop_searchpro_*` таблицы, ключи storefront settings |
| URL товаров | path `/search/.../` (не `/search/?query=`) |

---

## Фаза 0 — «остановить кровь» (0.5–1 день)

Без архитектурного рефакторинга, максимальный ROI.

- [x] **0.1** Индекс на popular queries — `lib/updates/2.0/1749446400.php` (`idx_status_frequency`, `idx_last_datetime`)

- [x] **0.2** Убрать `{rand()}` из `FrontendOutput.html` — только `?v{version}`

- [x] **0.3** Кэш `getVisible()` в `waSerializeCache`, TTL 600 s, group `shop/searchpro/popular`

- [x] **0.4** Popular/history lazy: endpoint `searchpro-plugin/helper/`, `field()` без `helperDropdown(current)`, загрузка в `FrontendField.html` (focus + idle)

- [x] **0.5** `getCachedCategoryTree()` — `waSerializeCache` 3600 s при `category_filter_status`

- [x] **0.6** Замер local (2026-06-11):

| Метрика | До | После |
|---------|-----|-------|
| `getVisible(5)` ×100 | ~242 ms avg | **~0.05 ms** avg |
| Popular HTML в category page | ~6 KB embedded | **0** (lazy) |
| config URL | `?v1.5?RAND` | `?v1.5` |
| `helper/` endpoint | — | ~11 ms |

**Критерий готовности:** ✅ config cacheable; 0 SQL popular на page load; popular не в HTML.

---

## Фаза 1 — новое ядро (2–3 дня)

Заменить «зоопарк классов» одним слоем сервисов.

### 1.1 Структура каталогов (новая)

```
lib/
  v2/
    SearchproPlugin.class.php          # тонкая обёртка, делегирует в v2
    Contract/
      SearchServiceInterface.php
      CacheInterface.php
    Service/
      SearchService.php                # единая точка: search products/categories/brands
      QueryNormalizer.php              # trim, min length, encoding
      CorrectorPipeline.php            # linear, early exit, max 1 fallback
      PopularService.php               # top-N + cache
      CategoryTreeService.php          # cached tree slice for field filter
    Cache/
      ResultCache.php                  # waSerializeCache, единый формат ключей
      PageHtmlCache.php
    Repository/
      QueryRepository.php              # shop_searchpro_query
      GramsRepository.php
    Response/
      JsonResponse.php
      HtmlFragmentRenderer.php         # Smarty только здесь
    Action/                            # тонкие контроллеры 30–80 строк
      FieldAction.php
      SuggestAction.php                # бывший dropdown
      PageAction.php
      ConfigAction.php                 # опционально — удалить позже
```

- [x] **1.2** `SearchService::suggest()` — `lib/classes/v2/shopSearchproV2SearchService.class.php`

- [x] **1.3** `CorrectorPipeline` — auto-disable grams при пустой `shop_searchpro_grams`

- [x] **1.4** `ResultCache` — `shopSearchproV2ResultCache`, TTL из `dropdown_results_cache`

- [x] **1.5** `V2Settings` — делегирует в `staticallyGetSettings($name)`

- [x] **1.6** Feature flag `use_v2=1` + route `searchpro-plugin/suggest/`, dropdown → SuggestController

**Критерий:** suggest JSON count≈171, warm ~50–700 ms (v2 cache layer + finder). Профайлер: `.local/profile-searchpro-v2.php`

---

## Фаза 2 — поле поиска «нулевая стоимость» (1–2 дня)

Убить всю серверную логику из `field()`.

- [x] **2.1** `V2FieldRenderer` + `FrontendFieldShell.html` — 0 SQL, inline `searchpro_config` + `field_params` JSON

- [x] **2.2** `searchpro-plugin/categories/` — JSON, lazy при клике на селектор (`field-v2.js`)

- [x] **2.3** `searchpro-plugin/popular/` — JSON top-N (cached query model)

- [x] **2.4** History — cookie client-side; helper только lazy через `helper_url`

- [x] **2.5** Нет `searchpro-plugin/config/` — inline loader + lazy `field-v2.js`

- [x] **2.6** Поле видимо сразу (`type="search"`, без `display:none`)

- [x] **2.7** CSS-классы `searchpro__field*` сохранены в `FrontendFieldShell.html`

**Замер local (2026-06-11, category_filter=0):**

| Метрика | До ф.2 | После |
|---------|--------|-------|
| `searchpro-plugin/config` в HTML | 1× | **0** |
| `display:none` на поле | да | **нет** |
| helper_dropdown в JSON | ~6 KB | **~44 B** (template only) |
| categories в HTML | 0 | **0** (lazy) |

**Критерий:** ✅ 0 SQL на page load; config endpoint убран.

---

## Фаза 3 — dropdown → suggest API (2 дня)

- [x] **3.1** Route `searchpro-plugin/suggest/?q=` (JSON); `dropdown/` → proxy на SuggestController при `use_v2=1`

- [x] **3.2** JSON через `shopSearchproV2SuggestPresenter`; рендер dropdown в `frontend.field-v2.js`

- [x] **3.3** Debounce 250 ms, abort previous XHR (`findDropdown` patch), min length из settings

- [x] **3.4** Batch SQL: `parent_name` в `workupCategories`, batch `getById` в `V2SuggestPresenter`, `category_name` в `getVisible()` JOIN

- [x] **3.5** v2 dropdown: `event_frontend_products` = false (как на page) — без hook `frontend_products` на каждый suggest

- [x] **3.6** Cache-Control на suggest: private, max-age=120; waSerializeCache на backend (ResultCache)

**Замер local (2026-06-11, после cold-path оптимизаций):**

| Метрика | До | После |
|---------|-----|-------|
| suggest cold «стетоскоп» | ~1.5 s | **~0.62 s** |
| suggest cold garbage query | ~3.6 s | **~0.56 s** |
| suggest warm | ~10 ms | **~10 ms** |

Изменения: dropdown без grams/combine, `getProductsSuggest()`, категории из товаров, skip brands если пусто, cache v8.

**Критерий:** suggest warm <150 ms local ✅ (cache); cold — products finder, дальше только профайл SQL.

---

## Фаза 4 — страница поиска (2–3 дня)

- [x] **4.1** `shopSearchproV2PageService::build()` + `shopSearchproV2PageContext` — finder через v2 pipeline

- [x] **4.2** Full-page cache: `page_full_*` в `postExecute` → `getCachedPageShell()` при `use_v2=1`, page=1, без GET-фильтров

- [x] **4.3** Категории — `getCategoriesByProductIds()` (уже было в Page.action)

- [x] **4.4** Lazy filters: без GET-фильтров `shopSearchproFilters` не инициализируется (`shouldBuildFilters`)

- [x] **4.5** Query logging async: `register_shutdown_function` в `scheduleQueryLog()`

- [x] **4.6** Empty state popular — `PopularService::getTop()` в `emptyPage()` при v2

**Замер local (2026-06-09):** `/search/стетоскоп/` warm ~0.06 s (2-й прогон), cold ~0.76 s.

**Критерий:** `/search/стетоскоп/` warm <400 ms prod — ✅ local.

---

## Фаза 5 — новый фронтенд (2–3 дня)

Выбросить webpack-монолит 120 KB.

- [x] **5.1** `frontend.field-v2.js` — vanilla ~23 KB (unminified), fetch + DOM; заменяет `field.js` (120 KB)

- [x] **5.2** В одном файле: init, suggest (JSON render), popular/history (helper), categoryFilter

- [x] **5.3** Config endpoint убран в v2 (inline JSON в `FrontendFieldV2.html`) — ✅ с фазы 2

- [x] **5.4** Lazy load: `requestIdleCallback` + first `focus` на input → грузится только `field-v2.js`

- [x] **5.5** Event `shop-searchpro-field-loaded` сохранён

- [x] **5.6** `frontend.page-v2.js` + `frontend.filters-v2.js` вместо `frontend.page.js` (389 KB) — только на `/search/`

**Итого фаза 5:** field 120 KB → ~23 KB lazy; page 389 KB → ~7 KB (page-v2 + filters-v2).

---

## Фаза 6 — база данных и индексы (1 день)

- [x] **6.1** `shop_searchpro_query`: индекс `(status, frequency DESC)` + `(last_datetime)` — `1749446400.php`

- [x] **6.2** Ротация лога запросов: top 10K + 90 дней; CLI `php cli.php shop searchproPluginCleanupQueries` (`--dry-run 1`, `--top`, `--days`)

- [x] **6.3** Grams auto-disable в `V2CorrectorPipeline` + баннер в Settings при пустом индексе

- [x] **6.4** CLI `php cli.php shop searchproPluginRebuildGrams` — batch 500, progress; `UpdateGrams` extends RebuildGrams

- [x] **6.5** Version `2.0` + migration `1749446600.php` (autoload + settings cache)

**Замер local (2026-06-11):**

| Метрика | Результат |
|---------|-----------|
| `getVisible(5)` ×100 | ~5.85 ms (cache warm) |
| cleanup dry-run | 157182 rows → delete 28091, keep 129091 |
| CLI autoload | после `clearAutoloadCache('shop')` или migration 1749446600 |

**Критерий:** ✅ getVisible <2 ms; grams auto-off или notice; CLI работает.

---

## Фаза 7 — удаление мёртвого кода (1–2 дня)

После прохождения регрессии с `use_v2=1`.

- [ ] **7.1** Удалить: `detector/*` — **пока нельзя**: `FrontendPage.action::workupQuery()` использует detector для redirect-правил

- [ ] **7.2** Удалить: отдельные `*Finder.class.php` ×5 → логика в `SearchService` (v2 всё ещё использует `shopSearchproFinder`)

- [ ] **7.3** Удалить: `shopSearchproFrontend.class.php` god-object → `FieldRenderer` + actions

- [ ] **7.4** Удалить: `FrontendOutput.html`, `shopSearchproSmartyResource`, duplicate template layers

- [ ] **7.5** Удалить legacy JS: ~~`frontend.suggest-render.js`~~, ~~`frontend.field-init.js`~~ ✅; **`frontend.field.js`**, **`frontend.page.js`** — после регрессии (fallback при `use_v2=0`)

- [ ] **7.6** Упростить settings UI — скрыть неиспользуемые corrector combine modes

- [ ] **7.7** Перенести v2 из `lib/classes/v2/` в `lib/` — финальная структура

**Критерий:** −40% файлов плагина; нет дублирующих code paths.

---

## Фаза 8 — интеграция с темой almamed (0.5 дня)

- [x] **8.1** `searchform.html` — один `{shopSearchproPluginViewHelper::field()}` (desktop header + mobile pane через `$wa_active_theme_path`)

- [x] **8.2** `searchpro_plugin_field.html` — v2 использует `FrontendFieldShell.html`; тема CSS `searchpro_plugin_field.css` сохранён

- [x] **8.3** `header.top.css.html` — стили `.searchpro__field` в header сохранены

- [x] **8.4** `LocalDevRouting` — `wa-config/factories.php` + aliases localhost→almamed.su

- [x] **8.5** AT/speedup — `data-script-exception="searchpro-field-v2"` на inline boot script; CSS searchpro в `$at.css_sync_pattern` (sync в head)

**Замер local (2026-06-11):** category HTML без `config/`; field-v2 lazy; script-exception present.

---

## Фаза 9 — тесты и регрессия (ongoing)

### Автоматические curl-замеры

```bash
# TTFB — SearchPro не должен добавлять >50ms к категории после фазы 2
curl -sS -o /dev/null -w "category %{time_starttransfer}s\n" \
  http://localhost:8080/category/veterinariya/

curl -sS -o /dev/null -w "suggest warm %{time_total}s\n" \
  "http://localhost:8080/searchpro-plugin/suggest/?q=стетоскоп"

curl -sS -o /dev/null -w "search warm %{time_starttransfer}s\n" \
  "http://localhost:8080/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"
```

### CLI профайлеры

- `.local/profile-searchpro.php` — finder/page
- `.local/profile-searchpro-field.php` — field() cost

### Ручные сценарии

- [ ] Поиск кириллица / латиница / опечатка раскладки
- [ ] Поиск внутри категории (category filter)
- [ ] Popular + history в dropdown
- [ ] Страница поиска: sort, pagination, filters
- [ ] Mobile: поле в header + mobile pane — **один** instance JS
- [ ] Breadcrumbs на `/search/.../`
- [ ] Цена «по запросу» в suggest (price ≤ 0)

---

## Фаза 10 — деплой prod

- [ ] **10.1** Бэкап `shop_searchpro_*` таблиц + settings

- [ ] **10.2** Деплой код + migration (индекс)

- [ ] **10.3** `searchpro_use_v2=0` → smoke → `=1`

- [ ] **10.4** Очистить `wa-cache/apps/shop/searchpro/`

- [ ] **10.5** Замер DevTools до/после на категории + search + suggest

- [ ] **10.6** Мониторинг 24h: slow query log на `shop_searchpro_query`

---

## Порядок работ (рекомендуемый)

```
Фаза 0  ████████░░  день 1     ← можно начать сегодня
Фаза 1  ████████░░  дни 2–4
Фаза 2  ████████░░  дни 4–5     ← главный выигрыш для категорий
Фаза 3  ████████░░  дни 5–7
Фаза 5  ██████░░░░  параллельно с 3–4 (JS)
Фаза 4  ████████░░  дни 7–9
Фаза 6  ████████░░  ✅
Фаза 7  ██░░░░░░░░  в процессе (orphan JS удалён)
Фаза 8  ████████░░  ✅
```

**Параллелить:** фаза 5 (JS) + фаза 1 (PHP core).

---

## Анти-паттерны старого кода (не повторять)

| Было | Будет |
|------|-------|
| SQL + Smarty в `field()` на каждой странице | Zero-cost shell + lazy API |
| `{rand()}` на config URL | Version-only cache bust |
| PHP рендер dropdown HTML | JSON + client render |
| 5 finders × corrector chain × recursive search | 1 SearchService, early exit |
| 1100-line Page action | Thin action + services |
| 120 KB webpack field.js | ≤15 KB vanilla, defer |
| 157K query log без индекса | Index + rotation |
| getTree на каждый request | Cached JSON endpoint |
| helperDropdown ×2 embedded in page | AJAX on focus |
| config.php bootstrap для 1 KB JS | Inline JSON config |

---

## Статус

| Фаза | Статус | Дата |
|------|--------|------|
| 0 | ✅ | 2026-06-11 |
| 1 | ✅ | 2026-06-11 |
| 2 | ✅ | 2026-06-11 |
| 3 | ⏳ | |
| 4 | ⏳ | |
| 5 | ⏳ | |
| 6 | ⏳ | |
| 7 | ⏳ | |
| 8 | ⏳ | |
| 9 | ⏳ | |
| 10 | ⏳ | |

**Легенда:** ⏳ не начато · 🟡 в работе · ✅ готово

---

## История

| Дата | Событие |
|------|---------|
| 2026-06-09 | Аудит: popular 157K без индекса, rand() на config, field() на каждой странице |
| 2026-06-09 | Создан чеклист полного переписывания |
| 2026-06-11 | Фаза 0: индекс, rand, cache popular, lazy helper endpoint |
| 2026-06-11 | Фаза 1: v2 core (SearchService, Suggest, use_v2 flag) |
| 2026-06-11 | Фаза 2: zero-cost field, categories/popular JSON, inline config |
