(function ($) {

    var PALETTE = [
        '#0497c0', '#2e9e5b', '#6366f1', '#e07a2e', '#d64545',
        '#0d9488', '#7c3aed', '#db2777', '#ca8a04', '#0284c7'
    ];

    $.productmanagerAdmin = {
        managers: {},
        categories: [],
        categoryById: {},
        summary: {},
        searchActive: false,

        init: function (config) {
            var self = this;
            this.managers = config.managers || {};
            this.categories = config.categories || [];
            this.summary = config.summary || {};
            this.managerPool = config.managerPool || null;

            this.$root = $('#pm-dashboard');
            this.urlAssign = this.$root.data('url-assign');
            this.urlStats = this.$root.data('url-stats');

            this.buildCategoryIndex();
            this.bindEvents();
            this.syncManagerPoolField();
            this.renderCategoryList();
        },

        getSearchQuery: function () {
            return ($('#pm-category-search').val() || '').toLowerCase().trim();
        },

        onSearchChange: function () {
            this.renderCategoryList();
        },

        renderCategoryList: function () {
            var query = this.getSearchQuery();
            if (query) {
                this.renderSearchResults(query);
            } else {
                this.renderCategories();
                this.applyFilters();
            }
            this.updateSearchUi();
        },

        findSearchMatches: function (query) {
            query = (query || '').toLowerCase().trim();
            var hideEmpty = $('#pm-hide-empty').prop('checked');
            var matches = [];

            $.each(this.categories, function (_, row) {
                var name = (row.name || '').toLowerCase();
                var url = (row.full_url || '').toLowerCase();
                if (name.indexOf(query) === -1 && url.indexOf(query) === -1) {
                    return;
                }
                if (hideEmpty && !row.total) {
                    return;
                }
                matches.push(row);
            });

            matches.sort(function (a, b) {
                return String(a.name || '').localeCompare(String(b.name || ''), 'ru');
            });

            return matches;
        },

        renderSearchResults: function (query) {
            var self = this;
            var matches = this.findSearchMatches(query);
            var $body = $('#pm-category-body').empty();

            if (!matches.length) {
                $body.append(
                    $('<tr class="pm-row pm-row--empty"/>').append(
                        $('<td colspan="7"/>').append(
                            $('<div class="pm-search-empty"/>').text('Ничего не найдено по запросу «' + query + '»')
                        )
                    )
                );
                this.searchActive = true;
                return;
            }

            $.each(matches, function (_, row) {
                $body.append(self.buildSearchRow(row, query));
            });
            this.searchActive = true;
        },

        escapeHtml: function (text) {
            return $('<div/>').text(text || '').html();
        },

        highlightMatch: function (text, query) {
            text = text || '';
            query = query || '';
            var lower = text.toLowerCase();
            var idx = lower.indexOf(query);
            if (idx < 0 || !query) {
                return this.escapeHtml(text);
            }
            return this.escapeHtml(text.substr(0, idx))
                + '<mark class="pm-search-mark">' + this.escapeHtml(text.substr(idx, query.length)) + '</mark>'
                + this.escapeHtml(text.substr(idx + query.length));
        },

        getCategoryBreadcrumb: function (row) {
            if (row.full_url) {
                return row.full_url.split('/').join(' › ');
            }

            var parts = [];
            var current = row;
            var guard = 0;

            while (current && guard++ < 50) {
                parts.unshift(current.name || '');
                var parentId = parseInt(current.parent_id, 10) || 0;
                if (!parentId) {
                    break;
                }
                current = this.categoryById[parentId];
            }

            return parts.join(' › ');
        },

        updateSearchUi: function () {
            var query = this.getSearchQuery();
            var count = query ? $('#pm-category-body tr.pm-row:not(.pm-row--empty)').length : 0;

            $('#pm-search-clear').prop('hidden', !query);
            if (query) {
                $('#pm-search-count').text('Найдено: ' + count).prop('hidden', false);
                $('#pm-category-table').addClass('pm-table--search');
            } else {
                $('#pm-search-count').prop('hidden', true).text('');
                $('#pm-category-table').removeClass('pm-table--search');
            }
        },

        buildCategoryIndex: function () {
            var map = {};
            $.each(this.categories, function (_, row) {
                map[row.id] = row;
            });
            this.categoryById = map;
        },

        bindEvents: function () {
            var self = this;

            $('#pm-refresh').on('click', function () {
                self.refreshStats(true);
            });

            var searchTimer = null;
            $('#pm-category-search').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    self.onSearchChange();
                }, 200);
            }).on('keydown', function (e) {
                if (e.key === 'Escape') {
                    $(this).val('');
                    self.onSearchChange();
                }
            });

            $('#pm-search-clear').on('click', function () {
                $('#pm-category-search').val('').focus();
                self.onSearchChange();
            });

            $('#pm-hide-empty').on('change', function () {
                self.onSearchChange();
            });

            $('#pm-select-all-categories').on('change', function () {
                var checked = $(this).prop('checked');
                $('#pm-category-body .pm-category-check:visible').prop('checked', checked);
            });

            $('#pm-assign-selected').on('click', function () {
                self.assignSelected(false);
            });

            $('#pm-clear-selected').on('click', function () {
                if (!confirm('Снять менеджеров с выбранных категорий?')) {
                    return;
                }
                self.assignSelected(true);
            });

            $(document).on('click', '.pm-row-assign', function () {
                var $row = $(this).closest('tr');
                var id = parseInt($row.data('id'), 10);
                var managerId = parseInt($row.find('.pm-bind-select').val(), 10);
                if (managerId) {
                    self.setManagerForCategory(id, managerId);
                } else {
                    self.assignCategories([id], false);
                }
            });

            $(document).on('click', '.pm-set-manager', function () {
                var $row = $(this).closest('tr');
                var id = parseInt($row.data('id'), 10);
                var managerId = parseInt($row.find('.pm-bind-select').val(), 10);
                self.setManagerForCategory(id, managerId);
            });

            $(document).on('click', '.pm-bind-apply', function () {
                var $row = $(this).closest('tr');
                var id = parseInt($row.data('id'), 10);
                var managerId = parseInt($row.find('.pm-bind-select').val(), 10);
                self.bindCategory(id, managerId);
            });

            $(document).on('click', '.pm-bind-clear', function () {
                var id = parseInt($(this).closest('tr').data('id'), 10);
                self.unbindCategory(id);
            });

            $(document).on('change', '#pm-category-body .pm-category-check', function () {
                $('#pm-select-all-categories').prop('checked', false);
            });

            $('#pm-save-pool').on('click', function () {
                self.saveManagerPool(true);
            });

            $(document).on('change', '.pm-manager-check', function () {
                self.syncManagerPoolField();
            });

            $(document).on('submit', '#plugins-settings-form', function () {
                self.syncManagerPoolField();
            });
        },

        getManagerPoolField: function () {
            var $form = $('#plugins-settings-form');
            if (!$form.length) {
                return $();
            }
            var name = 'shop_productmanager[manager_pool]';
            var $input = $form.find('input[name="' + name + '"]');
            if (!$input.length) {
                $input = $('<input type="hidden"/>').attr('name', name).appendTo($form);
            }
            return $input;
        },

        syncManagerPoolField: function () {
            var ids = this.getSelectedManagerIds();
            this.getManagerPoolField().val(ids.join(','));
        },

        setPoolStatus: function (text, type) {
            var $s = $('#pm-pool-status');
            $s.removeClass('is-ok is-err').text(text || '');
            if (type) {
                $s.addClass('is-' + type);
            }
        },

        saveManagerPool: function (manual) {
            var self = this;
            var ids = this.getSelectedManagerIds();

            if (!ids.length) {
                if (manual) {
                    alert('Выберите хотя бы одного менеджера');
                }
                return;
            }

            if (manual) {
                this.setPoolStatus('Сохранение…');
            }

            $.post(this.urlAssign, {
                mode: 'save_pool',
                manager_ids: ids,
                _csrf: $('#pm-dashboard input[name=_csrf]').first().val() || ''
            }, function (resp) {
                if (!resp || resp.status !== 'ok') {
                    self.setPoolStatus('Ошибка сохранения', 'err');
                    return;
                }
                self.syncManagerPoolField();
                if (manual) {
                    self.setPoolStatus('Выбор сохранён', 'ok');
                    window.setTimeout(function () { self.setPoolStatus(''); }, 2500);
                }
            }, 'json').fail(function () {
                self.setPoolStatus('Ошибка сети', 'err');
            });
        },

        managerColor: function (id) {
            id = parseInt(id, 10) || 0;
            return PALETTE[Math.abs(id) % PALETTE.length];
        },

        getSelectedManagerIds: function () {
            return $('.pm-manager-check:checked').map(function () {
                return parseInt($(this).val(), 10);
            }).get().filter(Boolean);
        },

        getSelectedCategoryIds: function () {
            return $('#pm-category-body .pm-category-check:visible').map(function () {
                return parseInt($(this).val(), 10);
            }).get().filter(Boolean);
        },

        setStatus: function (text, type) {
            var $s = $('#pm-status');
            $s.removeClass('is-ok is-err').text(text || '');
            if (type) {
                $s.addClass('is-' + type);
            }
        },

        refreshStats: function (silent) {
            var self = this;
            if (!silent) {
                this.setStatus('Обновление…');
            }

            var hideEmpty = $('#pm-hide-empty').prop('checked') ? 1 : 0;

            $.get(this.urlStats, { hide_empty: hideEmpty }, function (resp) {
                if (!resp || resp.status !== 'ok') {
                    self.setStatus('Ошибка загрузки', 'err');
                    return;
                }
                self.managers = resp.managers || self.managers;
                self.categories = resp.categories || [];
                self.summary = resp.summary || {};
                self.buildCategoryIndex();
                self.renderSummary();
                self.renderManagers();
                self.renderCategoryList();
                self.setStatus('Данные обновлены', 'ok');
                window.setTimeout(function () { self.setStatus(''); }, 2500);
            }, 'json').fail(function () {
                self.setStatus('Ошибка сети', 'err');
            });
        },

        postAssign: function (data, successMessage) {
            var self = this;

            data._csrf = $('#pm-dashboard input[name=_csrf]').first().val() || '';
            data.include_subcategories = 1;

            $.post(this.urlAssign, data, function (resp) {
                if (!resp || resp.status !== 'ok') {
                    self.setStatus('Ошибка операции', 'err');
                    return;
                }
                self.categories = resp.categories || self.categories;
                self.summary = resp.summary || self.summary;
                if (resp.managers) {
                    self.managers = resp.managers;
                }
                self.buildCategoryIndex();
                self.renderSummary();
                self.renderManagers();
                self.renderCategoryList();
                self.setStatus(successMessage.replace('%n', resp.updated || 0), 'ok');
            }, 'json').fail(function () {
                self.setStatus('Ошибка сети', 'err');
            });
        },

        assignSelected: function (clearMode) {
            var ids = this.getSelectedCategoryIds();
            if (!ids.length) {
                alert('Выберите хотя бы одну категорию');
                return;
            }
            this.assignCategories(ids, clearMode);
        },

        assignCategories: function (categoryIds, clearMode) {
            var managerIds = this.getSelectedManagerIds();

            if (!clearMode && !managerIds.length) {
                alert('Выберите хотя бы одного менеджера');
                return;
            }

            if (!clearMode && !confirm('Случайно назначить менеджеров для ' + categoryIds.length + ' категор(ии)?')) {
                return;
            }

            this.setStatus('Назначение…');
            this.postAssign({
                category_ids: categoryIds,
                manager_ids: managerIds,
                only_unassigned: $('#pm-only-unassigned').prop('checked') ? 1 : 0,
                mode: clearMode ? 'clear' : 'assign'
            }, 'Обновлено товаров: %n');
        },

        setManagerForCategory: function (categoryId, managerId) {
            if (!managerId) {
                alert('Выберите менеджера в списке');
                return;
            }

            this.setStatus('Назначение…');
            this.postAssign({
                mode: 'set_manager',
                category_id: categoryId,
                manager_id: managerId
            }, 'Назначено товаров: %n');
        },

        bindCategory: function (categoryId, managerId) {
            if (!managerId) {
                alert('Выберите менеджера для привязки');
                return;
            }

            if (!confirm('Привязать менеджера к категории и назначить его на все товары (включая уже назначенные)?')) {
                return;
            }

            this.setStatus('Привязка…');
            this.postAssign({
                mode: 'bind',
                category_id: categoryId,
                manager_id: managerId
            }, 'Назначено товаров: %n');
        },

        unbindCategory: function (categoryId) {
            if (!confirm('Снять привязку менеджера с категории? Товары не изменятся.')) {
                return;
            }

            this.setStatus('Снятие привязки…');
            this.postAssign({
                mode: 'unbind',
                category_id: categoryId
            }, 'Привязка снята');
        },

        renderSummary: function () {
            var s = this.summary;
            $('#pm-summary [data-field="total"]').text(s.total || 0);
            $('#pm-summary [data-field="assigned"]').text(s.assigned || 0);
            $('#pm-summary [data-field="unassigned"]').text(s.unassigned || 0);
            $('#pm-summary [data-field="categories_with_gaps"]').text(s.categories_with_gaps || 0);
        },

        renderManagers: function () {
            var self = this;
            $('#pm-manager-list .pm-manager').each(function () {
                var id = String($(this).data('id'));
                var m = self.managers[id];
                if (!m) {
                    return;
                }
                var $count = $(this).find('.pm-manager__count');
                if (!$count.length) {
                    $count = $('<span class="pm-manager__count"/>').appendTo($(this).find('.pm-manager__meta'));
                }
                $count.text((m.assigned_count || 0) + ' товаров');
            });
        },

        renderCategories: function () {
            var self = this;
            var $body = $('#pm-category-body').empty();

            $.each(this.categories, function (_, row) {
                $body.append(self.buildRow(row));
            });
        },

        buildManagerSelect: function (selectedId) {
            var self = this;
            var $select = $('<select class="pm-bind-select"/>')
                .append($('<option value=""/>').text('—'));

            $.each(this.managers, function (id, manager) {
                $select.append(
                    $('<option/>')
                        .val(id)
                        .text(manager.name || ('#' + id))
                        .prop('selected', parseInt(selectedId, 10) === parseInt(id, 10))
                );
            });

            return $select;
        },

        buildManagerCell: function (row) {
            var hasUnassigned = (parseInt(row.unassigned, 10) || 0) > 0;
            var $td = $('<td class="pm-col-mgr"/>');
            var $cell = $('<div class="pm-mgr-cell"/>');

            $cell.append(this.buildManagerSelect(row.bound_manager || 0));
            $cell.append(
                $('<button type="button" class="pm-btn pm-btn--go pm-set-manager pm-btn-apply"/>')
                    .text('На товары')
                    .prop('disabled', !hasUnassigned)
                    .attr('title', 'Проставить выбранного менеджера на все товары без менеджера в этой категории')
            );
            $cell.append(
                $('<span class="pm-bound-dot"/>').attr('title', 'Категория привязана — новые товары получат этого менеджера')
            );
            $td.append($cell);
            return $td;
        },

        buildActionsCell: function (row) {
            var hasUnassigned = (parseInt(row.unassigned, 10) || 0) > 0;
            var $td = $('<td class="pm-col-actions"/>');
            var $actions = $('<div class="pm-actions"/>');

            $actions.append(
                $('<button type="button" class="pm-act pm-bind-apply"/>')
                    .text('Привязать')
                    .attr('title', 'Закрепить менеджера за категорией и назначить на все товары')
            );
            $actions.append(
                $('<button type="button" class="pm-act pm-act--warn pm-bind-clear"/>')
                    .text('Снять')
                    .prop('disabled', !row.bound_manager)
                    .attr('title', 'Снять привязку категории')
            );
            $actions.append(
                $('<button type="button" class="pm-act pm-row-assign"/>')
                    .text('Случайно')
                    .prop('disabled', !hasUnassigned)
                    .attr('title', 'Случайно из отмеченных менеджеров в колонке слева')
            );

            $td.append($actions);
            return $td;
        },

        buildRow: function (row) {
            var self = this;
            var rowClass = 'pm-row pm-row--root'
                + (row.unassigned > 0 ? ' pm-row--gap' : '')
                + (row.bound_manager ? ' pm-row--bound' : '');

            var $tr = $('<tr/>', {
                'class': rowClass,
                'data-id': row.id,
                'data-name': (row.name || '').toLowerCase(),
                'data-empty': row.total ? '0' : '1'
            });

            $tr.append($('<td class="pm-col-check"/>').append(
                $('<input type="checkbox" class="pm-category-check"/>').val(row.id)
            ));

            var $nameCell = $('<td class="pm-col-name"/>');
            $nameCell.append(
                $('<div class="pm-cat-head"/>').append(
                    $('<span class="pm-cat-name"/>').text(row.name || '')
                )
            );

            if (row.full_url) {
                $nameCell.append(
                    $('<span class="pm-cat-url"/>').text('/' + row.full_url)
                );
            }

            $tr.append($nameCell);

            $tr.append($('<td class="pm-col-num"/>').text(row.total || 0));

            var $un = $('<td class="pm-col-num"/>');
            if (row.unassigned > 0) {
                $un.append($('<span class="pm-pill pm-pill--warn"/>').text(row.unassigned));
            } else {
                $un.append($('<span class="pm-muted"/>').text('0'));
            }
            $tr.append($un);

            $tr.append($('<td class="pm-col-dist"/>').append(self.buildDistribution(row)));
            $tr.append(this.buildManagerCell(row));
            $tr.append(this.buildActionsCell(row));

            return $tr;
        },

        buildSearchRow: function (row, query) {
            var self = this;
            var $tr = $('<tr/>', {
                'class': 'pm-row pm-row--search' + (row.unassigned > 0 ? ' pm-row--gap' : ''),
                'data-id': row.id,
                'data-name': (row.name || '').toLowerCase(),
                'data-empty': row.total ? '0' : '1'
            });

            $tr.append($('<td class="pm-col-check"/>').append(
                $('<input type="checkbox" class="pm-category-check"/>').val(row.id)
            ));

            var $nameCell = $('<td class="pm-col-name"/>');
            $nameCell.append(
                $('<div class="pm-cat-head pm-cat-head--search"/>').append(
                    $('<span class="pm-cat-name"/>').html(self.highlightMatch(row.name || '', query))
                )
            );
            $nameCell.append(
                $('<span class="pm-cat-path"/>').text(self.getCategoryBreadcrumb(row))
            );
            if (row.full_url) {
                $nameCell.append(
                    $('<span class="pm-cat-url"/>').html(self.highlightMatch('/' + row.full_url, query))
                );
            }

            $tr.append($nameCell);

            $tr.append($('<td class="pm-col-num"/>').text(row.total || 0));

            var $un = $('<td class="pm-col-num"/>');
            if (row.unassigned > 0) {
                $un.append($('<span class="pm-pill pm-pill--warn"/>').text(row.unassigned));
            } else {
                $un.append($('<span class="pm-muted"/>').text('0'));
            }
            $tr.append($un);

            $tr.append($('<td class="pm-col-dist"/>').append(self.buildDistribution(row)));
            $tr.append(this.buildManagerCell(row));
            $tr.append(this.buildActionsCell(row));

            return $tr;
        },

        buildDistribution: function (row) {
            var self = this;
            var $wrap = $('<div/>');
            var total = parseInt(row.total, 10) || 0;
            var managers = row.managers || {};

            if (!total) {
                $wrap.append($('<span class="pm-muted"/>').text('нет товаров'));
                return $wrap;
            }

            var keys = Object.keys(managers);
            if (!keys.length) {
                $wrap.append($('<span class="pm-muted"/>').text('—'));
                return $wrap;
            }

            var $bar = $('<div class="pm-dist"/>');
            $.each(keys, function (_, mid) {
                var cnt = parseInt(managers[mid], 10) || 0;
                var width = total ? (100 * cnt / total) : 0;
                $bar.append($('<span class="pm-dist__seg"/>').css({
                    width: width + '%',
                    background: self.managerColor(mid)
                }).attr('title', self.managerName(mid) + ': ' + cnt));
            });
            $wrap.append($bar);

            var $legend = $('<div class="pm-dist-legend"/>');
            var parts = [];
            $.each(keys, function (_, mid) {
                var cnt = parseInt(managers[mid], 10) || 0;
                parts.push(self.managerName(mid) + ': ' + cnt);
            });
            $legend.text(parts.join(', '));
            $wrap.append($legend);

            return $wrap;
        },

        managerName: function (id) {
            id = String(id);
            if (this.managers[id] && this.managers[id].name) {
                return this.managers[id].name;
            }
            return '#' + id;
        },

        applyFilters: function () {
            if (this.getSearchQuery()) {
                return;
            }

            var hideEmpty = $('#pm-hide-empty').prop('checked');
            this.searchActive = false;

            $('#pm-category-body tr.pm-row').each(function () {
                var empty = String($(this).data('empty')) === '1';
                $(this).toggle(!(hideEmpty && empty));
            });
        }
    };

})(jQuery);
