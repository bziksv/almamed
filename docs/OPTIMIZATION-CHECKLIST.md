# Чеклист оптимизации almamed.su

> Живой документ. Все находки в процессе аудита/правок — сюда.  
> Params категорий (не ломать): [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md)

**Базовый TTFB local (warm, MySQL local):** главная ~2 s, категория ~2–3 s, поиск ~3 s, блог ~0.05 s.

---

## Статус задач

| # | Раздел | Задача | Статус | Эффект / примечание |
|---|--------|--------|--------|---------------------|
| 1 | Shop / `list-thumbs.html` | N+1 `$wa->shop->product($id)` → `shopCustom::getProductParamsByIds()` | ✅ | −N SQL на списке; warm категория ~2–3 s |
| 2 | Shop / SEO plugin | N+1 `extendProduct()` → `getProductSeoNamesByIds()` | ✅ | 1 SQL вместо N× collect() |
| 3a | Shop / SearchPro | N+1 `getProductsCategories()` (dropdown) | ✅ | 1 batch SQL |
| 3b | Shop / SearchPro | N+1 `getRoutes()` + `parent_name` на странице поиска | ✅ | |
| 3c | Shop / SearchPro | Шаблон: `$wa->shop->category()` в цикле | ✅ | `searchpro_plugin_page.html` |
| 3d | Shop / категория / SEO | N+1 `extendCategory()` в сетке подкатегорий | ✅ | `getCategorySeoNamesByIds()` |
| 4 | Shop / `/brands/` / productbrands | 1504 бренда в одном HTML (547 KB) | ✅ | Пагинация 120/стр → **~169 KB**; стили `block paging-nav` |
| 5 | Shop / SearchPro | Кэш результатов страницы (`page_results_cache`) | 🟡 | См. раздел ниже — уже включён, узкое место не только кэш |
| 6 | Shop / SearchPro | Рендер страницы после finder (HTML + категории) | 🟡 | Кэш `page_html_*` + `page_cats_*`; fast path SQL; warm **~1.5 s** (было ~2.8 s) |
| 7 | Shop / productbrands | `getBrands()` грузит **все** бренды в память на каждый запрос | 🟡 | `getBrandsPage()` — только 120/стр; кэш sorted IDs 1 ч |
| 8 | Shop / productbrands | Шаблон брендов в БД: `src` + `data-src` на каждом `<img>` | ✅ | Убран `data-src`, `loading="lazy"` в action |
| 9 | Shop / sitemap | `sitemap-shop-1.xml` ~3.7 MB | ⏳ | Разбивка / лимит URL |
| 10 | Shop / seofilter | 404 на несуществующий фильтр ~4+ s | ⏳ | `empty_page_http_code`; ранний exit без полного рендера |
| 11 | Shop / категория | `subcategoriesFilters($id)` в цикле при `?brend=` | ✅ | `subcategoriesFiltersByIds()` — 1 SQL |
| 12 | Shop / категория | `getTagsByCategory()` — params всех подкатегорий дерева | ✅ | 1 SQL вместо N× `get()` при `meta=true` |
| 13 | Site / page cache | Статические shop-страницы без кэша | ⏳ | |
| 14 | Site / шапка | SearchPro: иконка лупы на кнопке | ✅ | Белая на бирюзовом фоне |

**Легенда:** ✅ сделано · 🟡 частично / проверено · ⏳ в очереди

---

## SearchPro — кэш (`page_results_cache`)

### Настройка

| Параметр | Где | Default в коде | **Local (факт)** |
|----------|-----|----------------|------------------|
| `page_results_cache` | Shop → SearchPro → настройки витрины | `0` (выкл.) | **`86400`** (24 ч) |
| `dropdown_results_cache` | то же | `10800` | **`43200`** (12 ч) |

Код: `shopSearchproPluginFrontendPage.action.php` → `cache_actuality` → `shopSearchproFinder`.

### Что кэшируется

- **Finder:** ID товаров и категорий по слову запроса (`shop_searchpro_results_*`, TTL `page_results_cache`).
- **Страница (новое):** готовый HTML списка товаров (`page_html_*`) и блок категорий (`page_cats_*`) в `wa-cache/.../shop/searchpro/cache/`, тот же TTL.
- Ключ HTML: query + category_id + sort/order; только page=1, без GET-фильтров.
- **Не кэшируется:** обёртка SearchPro + layout темы (sidebar, header).

### URL поиска

| URL | Action | SearchPro page cache |
|-----|--------|----------------------|
| `/search/стетоскоп/` | `searchpro` → `frontend/page` | ✅ |
| `/search/?query=стетоскоп` | shop → `frontend/search` | ❌ (нативный поиск) |

Поле SearchPro в шапке ведёт на path-URL (`/search/%QUERY%/`).

### Замер (local, path-URL `/search/стетоскоп/`)

| Запрос | cold | warm (2–5-й) |
|--------|------|--------------|
| до оптимизации | ~3.0 s | ~2.8–3.6 s |
| после (#6) | ~2.3 s | **~1.5–1.7 s** |

CLI-компоненты (warm finder ~11 ms, `getCollectionCategories` ~2 ms): `.local/profile-searchpro.php`

Вывод: узкое место после finder — **рендер `search.html` + layout**; кэш HTML снимает ~40% warm TTFB. Остаток — тема (#13).

### Рекомендации (prod)

1. Убедиться, что `page_results_cache` ≥ `3600` (лучше `86400`, как dropdown).
2. Не отключать при деплое (настройки в `wa_app_settings`, app `shop.searchpro`).
3. После массового импорта товаров — сброс `wa-cache` / дождаться TTL.

---

## `/brands/` — детали

- **Было:** 1504 × `item-brand`, 547 KB, TTFB ~2.3 s.
- **Стало:** 120/стр, ~169 KB, пагинация `?page=N`.
- **Файл:** `wa-apps/shop/plugins/productbrands/lib/actions/shopProductbrandsPluginFrontendBrands.action.php`
- **Пагинация:** обёртка `block paging-nav` (как в каталоге), не голый `menu-h`
- **PHP:** `getBrandsPage()` / `getBrandsCount()` — `getById` только для текущей страницы (120)
- **Кэш:** `waSerializeCache` sorted IDs + names + counts, TTL 3600 (`shop/productbrands`)
- **Осталось:** cold request всё ещё читает все feature values (~1504); шаблон в настройках плагина (не в git)

---

## Ключевые файлы (уже трогали)

| Область | Файл |
|---------|------|
| Список товаров | `wa-data/public/shop/themes/osnovnaja_new_header_footer_form/list-thumbs.html` |
| Категория | `.../category.html` |
| SearchPro util | `wa-apps/shop/plugins/searchpro/lib/classes/util/shopSearchproUtil.class.php` |
| SearchPro page | `.../shopSearchproPluginFrontendPage.action.php` |
| SEO batch | `wa-apps/shop/plugins/seo/lib/shopSeoViewHelper.class.php` |
| Product params | `wa-apps/shop/lib/classes/shopCustom.class.php` |
| Brands | `wa-apps/shop/plugins/productbrands/lib/actions/shopProductbrandsPluginFrontendBrands.action.php` |

---

## Регрессия после правок

```bash
./start-dev.sh
curl -sS -o /dev/null -w "home %{time_starttransfer}s\n" http://localhost:8080/
curl -sS -o /dev/null -w "category %{time_starttransfer}s\n" http://localhost:8080/category/ginekologiya/
curl -sS -o /dev/null -w "search %{time_starttransfer}s\n" "http://localhost:8080/search/%D1%81%D1%82%D0%B5%D1%82%D0%BE%D1%81%D0%BA%D0%BE%D0%BF/"
curl -sS -o /dev/null -w "brands %{size_download}B %{time_starttransfer}s\n" http://localhost:8080/brands/
```

Категория с `meta=true` / `rec01` — вручную. См. [CATEGORY-PARAMS.md](CATEGORY-PARAMS.md).

---

## История

| Дата | Изменение |
|------|-----------|
| 2026-06-10 | Создан чеклист; п.1–4, 3a–3d, SearchPro cache audit |
| 2026-06-10 | П.14 — белая лупа SearchPro в шапке |
| 2026-06-10 | П.7 — lazy-load брендов + кэш sorted IDs; пагинация стили |
| 2026-06-11 | П.8 — убран дубль data-src на /brands/; п.11 — batch subcategoriesFilters |
| 2026-06-11 | Cold suggest: grams/combine off in dropdown, getProductsSuggest, categories from products, sidebar tree cache |
| 2026-06-11 | П.6 — SearchPro: кэш page_html/page_cats, fast path категорий, упрощён SQL |
| 2026-06-09 | SearchPro — план полного переписывания: [SEARCHPRO-REWRITE-CHECKLIST.md](SEARCHPRO-REWRITE-CHECKLIST.md) |
