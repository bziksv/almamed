# Плагин «Заявки» (shop/plugins/leads)

Журнал заявок с форм сайта в админке Shop: `?plugin=leads&module=report`.

Версия: **1.2.0** (этапы 1–5, local)

## Каналы

| source | Форма | Где пишется | Настройка |
|--------|--------|-------------|-----------|
| `kp` | Запросить КП | `nbpopupform` FrontendSend | `log_kp` |
| `zayavka` | Оставить заявку | `shopFormPlugin::getFormApp` | `log_zayavka` |
| `404` | Форма на 404 | `shopFormPlugin::getForm` | `log_404` |
| `wait` | Окно при уходе | `wait` Waitsendemail | `log_wait` |

Письма на Planfix уходят как раньше.

## Админка

- Список, фильтры, пагинация, карточка, статусы
- Badge «(N)» в меню (выкл. в настройках); пункт меню вставляется сразу после «Заказы» (до ElasticMenu / «Ещё»)
- CSV, массовая смена статуса
- Антидубли (минуты в настройках; 0 = выкл.)
- «Скрыть дубли», «Очистить старые» (по `retention_months`)
- Ссылка «Настройки» → `?plugin=leads&module=settings` (также Плагины → `#/leads`)

## Настройки плагина

Плагины → Заявки (`#/leads`) или кнопка в списке заявок → `?plugin=leads&module=settings`:

- вкл/выкл каналов
- хранить payload JSON
- badge в меню
- окно антидублей (мин)
- срок хранения (мес.) для очистки

**404 на `#/leads`:** обычно устаревший `wa-cache/.../autoload.php` без `shopLeadsPluginSettingsAction`. Лечится `find wa-cache -mindepth 1 -delete` (+ OPcache на prod).

## Local / prod

- Local: `'leads' => true` в `wa-config/apps/shop/plugins.php` (**не в git**)
- Таблица: `php wa-apps/shop/plugins/leads/lib/config/install_table.php`
- После появления `duplicate_of`:  
  `ALTER TABLE shop_leads_plugin_lead ADD COLUMN duplicate_of INT(11) NULL, ADD KEY duplicate_of (duplicate_of);`
- Prod (выкатили **2026-08-25**, commit `0368f6a`): `'leads' => true` в `plugins.php`, таблица создана, OPcache сброшен.

## Важно

Не удалять `shopLeadsPlugin::logLead()` из form / nbpopupform / wait.  
Права: админ shop или `settings`.
