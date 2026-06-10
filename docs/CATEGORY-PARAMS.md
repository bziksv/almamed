# Дополнительные параметры категорий (Shop-Script)

> **Критичная бизнес-логика витрины.** Параметры задаются в админке:  
> **Shop → Каталог → категория → «Дополнительные параметры»** (`shop_category_params`).

Prod-тема: `osnovnaja_new_header_footer_form`.  
Любые правки в перечисленных файлах — только с проверкой поведения на категории с параметрами.

---

## Где реализовано

| Параметр(ы) | Файл | PHP-хелпер |
|-------------|------|------------|
| `h1`, `meta`, `plitka*`, `menu`, `cat`, `rec*`, URL-ключ | `wa-data/public/shop/themes/osnovnaja_new_header_footer_form/category.html` | `shopCustom::*` |
| `bar_tags*` | `.../sidebar.html` | `shopCustom::array_filter` |
| `select_view`, `product_per_buy`, `product_per_price` | `.../list-thumbs.html` | — |
| `products_per_page` | `wa-apps/shop/lib/actions/frontend/shopFrontendCategory.action.php:129` | `setCollection()` |
| `products_per_page` (fallback) | `wa-apps/shop/lib/actions/frontend/shopFrontend.action.php:45` | `setCollection()` |
| `product_additional=hide` | `category.html` + плагин **productadditional** | `shopProductadditionalPlugin::getAdditionalProduct` |
| `bm=no` | `wa-data/public/site/themes/osnovnaja_new_header_footer_form/at.html` | `renderMenuItem` |
| `buy=true` | `.../product.cart.html` (param товара, не категории) | product params |
| `rec*` блоки | `shopCustom::getListProducts()` | `wa-apps/shop/lib/classes/shopCustom.class.php` |

---

## Справочник параметров

### `h1=true`

Делает заголовок категории тегом `<h1>`. Без параметра — `<div class="title_h1">`.

SEO-имя берётся из плагина SEO (`shopCustom::getSeoField`) → `$category.seo_name`, иначе `$category.name`.

**Файл:** `category.html` (~206–217)

---

### `meta=true`

Включает **верхнюю плитку тегов** (`.subcategories` / `.tag-slider`):

1. Подкатегории с `menu=plitka` — через `shopCustom::getTagsByCategory($category.id)`
2. Произвольные ссылки — параметры `plitka01`, `plitka02`, …

**Формат plitka:** `Текст ссылки@/url/`

```
plitka01=Анатомическая модель легких@/category/anatomicheskie-modeli/legkie/
plitka02=Анатомическая модель@/category/...
```

**Файл:** `category.html` (~266–284), фильтр ключей: `shopCustom::array_filter($category.params, 'plitka')`

---

### `bar_tags01`, `bar_tags02`, …

Плитка тегов в **левом сайдбаре**. Блок показывается, если задан `bar_tags01` (триггер).

**Формат:** `Текст@/url/` (как у plitka)

**Файл:** `sidebar.html` (~63–81)

---

### `menu=plitka`

Подкатегория **не выводится** в сетке подкатегорий (`.piicsl`), а попадает в верхнюю плитку тегов при `meta=true` у родителя.

Скрытые категории (`status=0`) с этим параметром видны в плитке через `getTagsByCategory`.

**Файлы:** `category.html` (~242), `shopCustom::getTagsByCategory`

---

### `cat=off`

На **подкатегории**: не показывать её в сетке подкатегорий родителя (даже если категория активна).

**Файл:** `category.html` (~242) — `{continue}` в цикле `$category.subcategories`

---

### `bm=no`

Скрывает категорию из **дерева меню** (шапка, сайдбар). Категория остаётся доступной по прямому URL.

**Файл:** `wa-data/public/site/themes/osnovnaja_new_header_footer_form/at.html` — `empty($menu.params.bm)` в `renderMenuItem`

---

### `products_per_page=50`

Количество товаров на странице категории (по умолчанию 30 из настроек Shop-Script).

**Ядро (кастом):** лимит передаётся в `setCollection`:

```php
$this->setCollection($collection, $category['params']['products_per_page']);
```

**Файлы:**
- `wa-apps/shop/lib/actions/frontend/shopFrontendCategory.action.php:129`
- `wa-apps/shop/lib/actions/frontend/shopFrontend.action.php:45` — `$limit` из param категории

> При обновлении Shop-Script проверять, что эта строка не затёрта.

---

### `select_view=thumbs|list|short-list`

Вид списка товаров на категории:

| Значение | Описание |
|----------|----------|
| `thumbs` | Плитка с картинкой |
| `list` | Строка с картинкой |
| `short-list` | Строка без картинки |

Приоритет: cookie `shop_select_view` → param категории → `$theme_settings.select_view`.

**Файлы:** `category.html` (~309–312), `list-thumbs.html` (~73–83)

---

### `product_per_buy=3` / `product_per_price=3`

Для **индексации**: у первых N товаров (index 0..N-1) скрывают текст «Купить» и «Цена:» через `data-text` (контент остаётся в DOM для JS/SEO).

У товаров с index **>** значения param текст виден.

**Файл:** `list-thumbs.html` (~243–263)

---

### `price_text=true` *(устарел)*

Раньше менял «В корзину» → «Купить» и подставлял «Цена:».  
Сейчас «Купить» — дефолт в теме (`$add2cart_label`). Param можно не использовать.

---

### `rec01`, `rec02`, … — доп. блоки товаров

Выводит **второй список товаров** после основного каталога.

**Формат:**

```
rec01=Заголовок блока@{"21380":["Уникальное имя","Анонс"],"21381":["",""]}@list
```

| Часть `@` | Описание |
|-----------|----------|
| 1 | Заголовок `<div class="h3">` |
| 2 | JSON: `{product_id: [list_name, summary]}` |
| 3 | Вид: `thumbs`, `list`, `short-list` |
| 4 | `@off` — без кнопки «Купить» и блока цены |

**Пример с @off:**

```
rec01=Название@{"21380":["Товар 1","Анонс"]}@list@off
```

**Файлы:** `category.html` (~331–336), `shopCustom::getListProducts()`

Блоки **не выводятся**, если активен фильтр (`$filter_value` не пуст).

---

### Param с ключом = URL страницы

Альтернатива `rec*`: имя param = **полный URL категории** (как `$wa->currentUrl()`), значение — тот же формат `@`:

```
/category/anesteziologiya/=Блок@{"123":["",""]}@thumbs
```

**Файл:** `category.html` (~324–327)

---

### `product_additional=hide`

Отключает плагин **productadditional** («схожие товары» при малом числе позиций в категории).

**Файл:** `category.html` (~338–340)  
**Плагин:** `wa-apps/shop/plugins/productadditional/`  
**Админка:** `/webasyst/shop/?action=plugins#/productadditional`

---

### `buy=true` *(param товара, не категории)*

На **скрытом товаре** разрешает кнопку «Купить» в корзине.

**Файл:** `product.cart.html` — `$product.params.buy === "true"`

Управление также через плагины **producthidden** / **emptycategory**.

---

## Вспомогательные методы `shopCustom`

| Метод | Назначение |
|-------|------------|
| `array_filter($params, 'plitka')` | Ключи по regex (`plitka`, `bar_tags`, `rec`) |
| `getTagsByCategory($id)` | Подкатегории с `menu=plitka` |
| `subcategoriesFilters($id)` | Есть ли товары в подкатегории с текущими GET-фильтрами |
| `getListProducts($json, $title, $view, $cart)` | Рендер rec-блока через `list-thumbs.html` |
| `getProductParamsByIds($ids)` | Batch product params (SEO-имя по URL категории в списке) |

---

## Связь с SEO product params в списке

В `list-thumbs.html` для каждого товара может подставляться `$p.seo_name` из **product params**, где ключ = `$wa->currentUrl()` (URL категории). Это **не** category param, но используется на странице категории.

Реализация: `shopCustom::getProductParamsByIds()` — один SQL на список, не `$wa->shop->product($id)` в цикле.

---

## Чеклист регрессии

После правок в теме shop / ядре категорий:

```bash
./start-dev.sh
# категория с meta=true и plitka — плитка сверху
curl -sS -o /dev/null -w "category HTTP %{http_code}\n" \
  http://localhost:8080/category/anesteziologiya/
# категория с products_per_page — кол-во li в product-list
# категория с rec01 — второй блок после списка
# категория с bm=no — нет в HTML меню, но URL открывается
```

Проверить вручную в админке 2–3 категории с разными наборами params.

---

## См. также

- [AGENT.md](AGENT.md) — пути и чеклист агента
- [.cursor/rules/category-params.mdc](../.cursor/rules/category-params.mdc) — правило для Cursor
