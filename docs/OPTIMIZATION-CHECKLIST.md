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
| 5 | Shop / SearchPro | Кэш результатов страницы (`page_results_cache`) | ✅ | 86400 s prod; finder warm ~11 ms |
| 6 | Shop / SearchPro | Рендер страницы после finder (HTML + категории) | ✅ | `page_full_*` + v2; warm search **~0.09 s** local |
| 7 | Shop / productbrands | `getBrands()` грузит **все** бренды в память на каждый запрос | ✅ | `getBrandsPage()` + кэш `feature_values_*` 24 ч (cold sorted_ids без повторного getFeatureValues) |
| 8 | Shop / productbrands | Шаблон брендов в БД: `src` + `data-src` на каждом `<img>` | ✅ | Убран `data-src`, `loading="lazy"` в action |
| 9 | Shop / sitemap | `sitemap-shop-1.xml` ~3.7 MB | ✅ | Seofilter URL **убраны** из `sitemap-shop-1`; фильтры → `/filter-sitemap.xml` |
| — | Shop / sitemap | `filter-sitemap.xml` в корневом `/sitemap.xml` | ✅ local | `webasystSitemapConfig` + hook; fix `ifset($route,'app',null)` + `$event_params` by ref |
| 10 | Shop / seofilter | 404 на пустой фильтр ~4+ s | ✅ | Ранний exit в routing (`respondIfEmptySeofilterPage`); warm **~0.12 s** (было ~4+ s) |
| 11 | Shop / категория | `subcategoriesFilters($id)` в цикле при `?brend=` | ✅ | `subcategoriesFiltersByIds()` — 1 SQL |
| 12 | Shop / категория | `getTagsByCategory()` — params всех подкатегорий дерева | ✅ | 1 SQL вместо N× `get()` при `meta=true` |
| 13 | Site / page cache | Статические shop-страницы без кэша | ✅ | `shopFrontendPageAction` — full HTML cache 1 ч; warm **~0.03 s** (dostavka) |
| 14 | Site / шапка | SearchPro: иконка лупы на кнопке | ✅ | Белая на бирюзовом фоне |
| 15 | Shop / категория | Full HTML cache гостей (15 мин) | ✅ | Fix ключа + readable fallback; warm TTFB **~0.01 s**; `X-Shop-Cache: category-hit` |
| 16 | Site / frontend | defer JS, third-party после load | ✅ | Metrika без изменений; Roistat idle+interaction; Talk-Me click/defer |
| 17 | Site / slider | FOUC баннера при load | ✅ | Sync lightslider CSS/JS; 1-й слайд до init |
| 18 | Site / шапка | Баннер-слайдер только на главной | ✅ | `lightslider` не грузится на категориях/поиске |
| 19 | Shop / каталог | Lazy-load превью товаров (после 4-й) | ✅ | `loading=lazy` в `list-thumbs.html` |
| 20 | Site / шрифты | Roboto 400/500/700 вместо 8 начертаний | ✅ | −5 woff2 с fonts.gstatic.com |
| 21 | Site / шрифты | Убрать Font Awesome с каждой страницы | ✅ | SVG в `share-light.html` (только карточка товара) |
| 22 | SearchPro | Отключить дубль Searchpro-Roboto | ✅ | `design_custom_fonts_status=0`, theme CSS → Roboto |
| 23 | SearchPro / mobile | Один instance поля поиска на mobile | ✅ | header skip + pane skip на shop; `content-search-bar` only |
| 24 | Shop / главная | Full HTML cache гостей (15 мин) | ✅ | `shopFrontend.action.php`; `X-Shop-Cache: home-hit` |
| 25 | Ops / prod | OPcache audit script | ✅ | `.local/opcache-audit.php` в post-deploy |
| 26 | Ops / prod | Sitemap smoke script | ✅ | `.local/check-sitemap.sh` — размеры + filter-sitemap |
| 27 | SearchPro | Legacy templates v1 | ✅ | удалены `FrontendOutput.html`, `FrontendField.html` |
| 28 | CrUX / CLS | Cookie banner: не скрывать `#footer-pane` до «Принять» | ✅ | desktop CLS origin 0.6 → ожидаем снижение |
| 29 | Shop / категория | LCP: первые 2 подкатегории — eager + `fetchpriority` | ✅ | не `lazy-fadein opacity:0` для LCP-кандidate |
| 30 | Site / slider | Резерв высоты `.header-slider-wrap` на mobile | ✅ | меньше CLS до init слайдера |
| 31 | CrUX / TTFB | Prod: home/category HTML cache (`X-Shop-Cache`) | ⏳ | field TTFB 1.4 s; проверить deploy + warm cache |
| 32 | CrUX / CLS | Material Icons async → скачок иконок шапки | ⏳ | рассмотреть inline SVG или sync MI только в header |
| 33 | Lab / images | WebP/next-gen для wmimageincat | ⏳ | PSI «Serve images in next-gen formats» |
| 34 | Site / 3rd-party | Roistat: idle + first interaction (не сразу на load) | ✅ | returning visit cookie → быстрее (2.5 s idle) |
| 35 | Site / Talk-Me | Mobile: без автoload в syncTriggerMode; defer 10 s / scroll | ✅ | клик «Напишите нам» — сразу |

**Легенда:** ✅ сделано · 🟡 частично / проверено · ⏳ в очереди

---

## PageSpeed Insights (field data, Jun 2026)

Отчёты: [главная mobile](https://pagespeed.web.dev/analysis/https-almamed-su/66ql6nlt72?form_factor=mobile) · [категория](https://pagespeed.web.dev/analysis/https-almamed-su-category-ginekologiya/gkv4hqt3hc?form_factor=mobile) · [товар](https://pagespeed.web.dev/analysis/https-almamed-su-product-oftalmoskop-ruchnoy-zerkalnyy-orz-01/jy4o3epzkg?form_factor=mobile)

| Метрика | URL главная (mobile) | Origin mobile | Origin desktop | Цель |
|---------|----------------------|---------------|----------------|------|
| LCP | **2.1 s** ✅ | 2.3 s 🟡 | 1.4 s ✅ | ≤ 2.5 s |
| CLS | **0.02** ✅ | **0.13** 🟡 | **0.6** ❌ | ≤ 0.1 |
| TTFB | n/a | **1.4 s** 🟡 | 1.1 s 🟡 | ≤ 0.8 s |
| INP | n/a | 124 ms ✅ | 62 ms ✅ | ≤ 200 ms |

Категория/товар в CrUX **без своих данных** — смотрим origin + lab Lighthouse.

**Lab local (warm cache):** главная score ~85, LCP 3.7 s (TTFB 1.7 s cold); категория score ~71, LCP 5.6 s (lazy LCP-картинки — п.29).

**Не трогаем без согласования:** Яндекс.Метрика, Roistat, Talk-Me — см. `.cursor/rules/external-scripts.mdc`.

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
./.local/regression-perf.sh

# prod (с сервера или с -k к IP):
BASE_URL=https://213.139.209.184 HOST_HEADER=almamed.su ./.local/regression-perf.sh
# или полный smoke (perf + HTML markers):
BASE_URL=https://almamed.su ./.local/verify-deploy.sh
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
| 2026-06-11 | П.13 — HTML cache shop info pages; п.7 — кэш feature_values брендов 24 ч |
| 2026-06-11 | П.15–17 — category HTML cache, defer third-party, FOUC slider; deploy `webasyst clearCache` |
| 2026-06-11 | П.5–6 SearchPro v2 — warm search ~0.09 s, suggest ~20 ms |
| 2026-06-12 | П.18 — баннер только на главной; п.19 — lazy превью в list-thumbs |
| 2026-06-12 | П.20–22 — Roboto 3 начертания, без Font Awesome, SearchPro без дубля шрифта |
| 2026-06-12 | П.23 — mobile: один SearchPro field; `.local/verify-deploy.sh` для prod smoke |
| 2026-06-13 | п.34–35 Roistat/Talk-Me defer (с согласия); п.28–30 CLS/LCP; rule `external-scripts.mdc` |
