# A/B отображение (плагин `abdisplay`)

Каркас для тестов вёрстки **без клонирования темы**. Одна тема, ветки `if/include` по sticky cookie.

Офтальмологию (v1/v2) этот плагин **не трогает**.

## Как устроено

1. В **Магазин → Отчёты → A/B-тесты** создаёте тест с вариантами `A` и `B`.
2. В **Плагины → A/B отображение** привязываете логический ключ (например `product_card`) к id этого теста и включаете галку.
3. Посетителю клеится cookie `waabt{id}`; шаблон читает `shopAbdisplayPlugin::variant('product_card')`.
4. События:
   - `view` — просмотр карточки (если эксперимент включён)
   - `cart` — `cart_add`
   - `lead` — любая заявка через `shopLeadsPlugin::logLead`
   - `order` — `order_action.create` (+ нативно варианты в params заказа для отчёта Sales)

Метрика: в `frontend_head` уходят `ym(..., 'params', { ab_product_card: 'A' })`. Файл `metrika.counter.html` **не меняется**.

## Карточка товара (скелет)

| Файл | Роль |
|------|------|
| `product.html` | ветка: B → `product.ab-b.html`, иначе `product.cart.html` |
| `product.ab-b.html` | stub = текущий buy box (пока) |
| `product.cart.html` | вариант A (текущий) |

Ключ `product_card` по умолчанию **выключен** — на витрине ничего не ротируется.

### Включить после редизайна B

1. Сверстать новую карточку в `product.ab-b.html` (или вынести общий кусок).
2. Создать тест в Отчётах → A/B (A/B).
3. Плагины → A/B отображение: выбрать тест, включить `product_card`.
4. Staff-превью: `?abdisplay_force[product_card]=B` на URL товара.
5. Смотреть сводку в настройках плагина + Отчёты → A/B по заказам.
6. Победителя закрепить: выключить эксперимент и оставить нужный include в `product.html`.

## Новый эксперимент (главная / категория / форма)

1. Добавить ключ в `shopAbdisplayHelper::defaultExperiments()` (или сохранить через settings JSON).
2. Создать тест в Отчётах.
3. В шаблоне:

```smarty
{$ab = ''}
{if class_exists('shopAbdisplayPlugin')}{$ab = shopAbdisplayPlugin::variant('home_hero')}{/if}
{if $ab == 'B'}
  {include file="home.hero-b.html"}
{else}
  {include file="home.hero-a.html"}
{/if}
```

4. Включить ключ в настройках плагина.

## Установка

Плагин: `wa-apps/shop/plugins/abdisplay/`.  
Включить в `wa-config/apps/shop/plugins.php`: `'abdisplay' => true`.  
Таблица: `php wa-apps/shop/plugins/abdisplay/lib/config/install_table.php`

## Риски

- Page-cache: не кэшировать HTML карточки без учёта cookie варианта.
- Ботам всегда отдаётся контрольный вариант (A).
- Мало заказов → смотреть lead/cart.
