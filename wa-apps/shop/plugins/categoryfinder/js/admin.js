(function ($) {
    'use strict';

    var assetsLoaded = false;
    var assetsLoading = null;

    var ASSETS = {
        css: [
            'https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css',
            'https://cdn.datatables.net/buttons/3.2.2/css/buttons.dataTables.css'
        ],
        js: [
            'https://cdn.datatables.net/2.2.2/js/dataTables.js',
            'https://cdn.datatables.net/buttons/3.2.2/js/dataTables.buttons.js',
            'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
            'https://cdn.datatables.net/buttons/3.2.2/js/buttons.html5.min.js'
        ]
    };

    function loadStylesheet(href) {
        if ($('link[href="' + href + '"]').length) {
            return;
        }
        $('<link>', { rel: 'stylesheet', href: href }).appendTo('head');
    }

    function loadScript(src) {
        return $.Deferred(function (defer) {
            if ($('script[src="' + src + '"]').length) {
                defer.resolve();
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.onload = function () { defer.resolve(); };
            script.onerror = function () { defer.reject(); };
            document.head.appendChild(script);
        }).promise();
    }

    function loadAssets() {
        if (assetsLoaded) {
            return $.when();
        }
        if (assetsLoading) {
            return assetsLoading;
        }

        ASSETS.css.forEach(loadStylesheet);

        assetsLoading = ASSETS.js.reduce(function (chain, src) {
            return chain.then(function () {
                return loadScript(src);
            });
        }, $.when()).then(function () {
            assetsLoaded = true;
        });

        return assetsLoading;
    }

    function collectFilters($root) {
        return {
            filter_level: $root.find('#cf-filter-level').val(),
            filter_cnt: $root.find('#cf-filter-cnt').val(),
            filter_active: $root.find('#cf-filter-active').val(),
            filter_redirect: $root.find('#cf-filter-redirect').val(),
            filter_without_prod: $root.find('#cf-filter-without-prod').val(),
            filter_storefront: $root.find('#cf-filter-storefront').val(),
            filter_duplicate: $root.find('#cf-filter-duplicate').val(),
            filter_duplicate_similarity: $root.find('#cf-filter-duplicate-similarity').val()
        };
    }

    function toggleDuplicateSimilarity($root) {
        var mode = $root.find('#cf-filter-duplicate').val();
        var enabled = mode === 'url' || mode === 'both';
        $root.find('#cf-filter-duplicate-similarity').prop('disabled', !enabled);
        $root.find('.categoryfinder-filter--similarity').toggleClass('is-disabled', !enabled);
    }

    function renderCheckbox(data) {
        var checked = data ? ' checked' : '';
        return '<input type="checkbox" class="cf-without-prod-check" value="1"' + checked + '>';
    }

    function escapeAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function columnTitle(label, tip) {
        return '<span class="cf-th-wrap">' +
            '<span class="cf-th-text">' + escapeHtml(label) + '</span>' +
            '<span class="cf-tip cf-tip--th" tabindex="0" data-tip="' + escapeAttr(tip) + '">(?)</span>' +
            '</span>';
    }

    var TABLE_COLUMNS = [
        { title: columnTitle('#', 'Порядковый номер строки в текущих результатах поиска.') },
        { title: columnTitle('Уровень', 'Глубина категории в дереве каталога. 1 — раздел верхнего уровня, 2 — подраздел и т.д.') },
        { title: columnTitle('ID', 'Идентификатор категории в базе Shop-Script. Используется в админке и ссылках.') },
        { title: columnTitle('Свои', 'Товары, напрямую привязанные к этой категории (поле count). Не включает товары из подкатегорий.') },
        { title: columnTitle('Подкат.', 'Включена ли в настройках категории опция Shop-Script «Включать подкатегории» (include_sub_categories).') },
        { title: columnTitle('В поддер.', 'Сумма count по всем подкатегориям в дереве ниже текущей категории (без учёта её собственных товаров).') },
        { title: columnTitle('На витрине', 'Как категория выглядит для покупателя: Пусто — товаров нет; Из подкат. — свои 0, но выводятся товары подкатегорий; Свои — есть прямые товары; Свои+подк. — и свои, и из подкатегорий.') },
        { title: columnTitle('Акт.', 'Опубликована ли категория на витрине (status > 0 в Shop-Script).') },
        { title: columnTitle('Название', 'Название категории. Ссылка ведёт в админку Shop-Script на список товаров этой категории.') },
        { title: columnTitle('URL', 'Символьный код (slug) категории — часть ЧПУ-адреса страницы.') },
        { title: columnTitle('Ссылка', 'Полный публичный URL страницы категории на сайте.') },
        { title: columnTitle('Дубликаты', 'Совпадения с другими категориями: по названию, по схожести URL или оба признака. Заполняется при включённом фильтре «Дубликаты».') },
        {
            title: columnTitle('Без товаров', 'Отметка without_prod в доп. параметрах категории. Помечает родительские категории-контейнеры, где товары выводятся из подкатегорий. Можно изменить прямо в таблице.'),
            render: renderCheckbox
        }
    ];

    function setStatus($root, message, state) {
        var $status = $root.find('#cf-status');
        if (!$status.length) {
            return;
        }

        $status
            .removeClass('categoryfinder-status--loading categoryfinder-status--ok categoryfinder-status--empty categoryfinder-status--error')
            .toggleClass('categoryfinder-status--' + state, !!state)
            .text(message || '')
            .prop('hidden', !message);
    }

    function fetchRows(listUrl, $root) {
        return $.ajax({
            url: listUrl,
            type: 'POST',
            dataType: 'json',
            timeout: 120000,
            data: collectFilters($root)
        });
    }

    function initPage($root) {
        if ($root.data('cf-inited')) {
            return;
        }
        $root.data('cf-inited', true);

        var listUrl = $root.data('list-url');
        var updateUrl = $root.data('update-url');
        var table = null;

        function loadRows() {
            var $btn = $root.find('#cf-filter-btn');
            $btn.prop('disabled', true);
            setStatus($root, 'Загрузка…', 'loading');

            fetchRows(listUrl, $root)
                .done(function (resp) {
                    var rows = (resp && resp.data) ? resp.data : [];
                    if (!table) {
                        setStatus($root, 'Ошибка инициализации таблицы', 'error');
                        return;
                    }
                    table.clear();
                    if (rows.length) {
                        table.rows.add(rows);
                    }
                    table.draw(false);
                    if (rows.length) {
                        setStatus($root, 'Найдено: ' + rows.length, 'ok');
                    } else {
                        setStatus($root, 'Ничего не найдено — измените фильтры и нажмите «Найти»', 'empty');
                    }
                })
                .fail(function (xhr, status) {
                    if (status === 'timeout') {
                        setStatus($root, 'Превышено время ожидания — сузьте фильтры и попробуйте снова', 'error');
                        return;
                    }
                    setStatus($root, 'Ошибка загрузки (' + xhr.status + ')', 'error');
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        }

        loadAssets().done(function () {
            table = $root.find('#cf-category-table').DataTable({
                data: [],
                paging: false,
                ordering: false,
                searching: false,
                autoWidth: false,
                dom: 'Bfrt',
                language: {
                    info: '',
                    infoEmpty: '',
                    infoFiltered: 'Всего записей: _MAX_',
                    emptyTable: 'Нет данных'
                },
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Скачать Excel'
                    }
                ],
                columns: TABLE_COLUMNS,
                initComplete: function () {
                    var api = this.api();
                    api.columns().every(function (index) {
                        var header = TABLE_COLUMNS[index] && TABLE_COLUMNS[index].title;
                        if (header) {
                            $(api.column(index).header()).html(header);
                        }
                    });
                }
            });

            toggleDuplicateSimilarity($root);

            $root.on('change', '#cf-filter-duplicate', function () {
                toggleDuplicateSimilarity($root);
            });

            $root.find('.dt-container, .dt-buttons').css('clear', 'none');

            $root.on('click', '#cf-filter-btn', function (e) {
                e.preventDefault();
                loadRows();
            });

            $root.on('change', '.cf-without-prod-check', function () {
                var $checkbox = $(this);
                var row = table.row($checkbox.closest('tr')).data();
                if (!row) {
                    return;
                }

                $.post(updateUrl, {
                    id: row[2],
                    without_prod: $checkbox.prop('checked')
                });
            });

            loadRows();
        }).fail(function () {
            setStatus($root, 'Не удалось загрузить DataTables', 'error');
        });
    }

    function scanForPage() {
        $('#wa-plugins-content .categoryfinder-admin').each(function () {
            initPage($(this));
        });
    }

    $(function () {
        scanForPage();

        var content = document.getElementById('wa-plugins-content');
        if (content && window.MutationObserver) {
            new MutationObserver(scanForPage).observe(content, {
                childList: true,
                subtree: true
            });
        }
    });
})(jQuery);
