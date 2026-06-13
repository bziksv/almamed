(function ($) {
    'use strict';

    function showMessage(text, isError) {
        if ($.wa && $.wa.notify) {
            $.wa.notify(isError ? 'error' : 'success', text);
            return;
        }
        alert(text);
    }

    function parseError(resp) {
        if (!resp) {
            return 'Неизвестная ошибка';
        }
        if (resp.errors) {
            var err = resp.errors;
            if ($.isArray(err)) {
                return err.map(function (item) {
                    return $.isArray(item) ? item[0] : item;
                }).join('\n');
            }
            if (typeof err === 'object') {
                return Object.values(err).join('\n');
            }
            return String(err);
        }
        return 'Ошибка';
    }

    function postAction(action, data, $btn, callback) {
        data = data || {};
        data._csrf = window.userlogCsrf || '';
        if ($btn && $btn.length) {
            $btn.prop('disabled', true).addClass('is-loading');
        }
        $.post('?module=backend&action=' + action, data, function (resp) {
            if (!resp || resp.status === 'fail' || resp.errors) {
                showMessage(parseError(resp), true);
                if ($btn && $btn.length) {
                    $btn.prop('disabled', false).removeClass('is-loading');
                }
                return;
            }
            callback(resp.data || resp);
        }, 'json').fail(function (xhr) {
            var text = 'Ошибка сети';
            if (xhr.status === 403) {
                text = 'Ошибка CSRF — обновите страницу и попробуйте снова';
            } else if (xhr.responseText) {
                text += ': ' + xhr.responseText.substring(0, 160);
            }
            showMessage(text, true);
            if ($btn && $btn.length) {
                $btn.prop('disabled', false).removeClass('is-loading');
            }
        });
    }

    $(document).on('click', '.js-event-rollback', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var id = $btn.data('id');
        if (!id) {
            showMessage('Не указано событие для отката', true);
            return;
        }
        if (!confirm('Откатить это действие?\n\nБудет восстановлено:\n' + ($btn.data('restore') || 'значения из колонки «Было»'))) {
            return;
        }
        postAction('rollback', { event_id: id }, $btn, function (data) {
            showMessage(data.message || 'Действие отменено');
            window.setTimeout(function () {
                window.location.reload();
            }, 400);
        });
    });

    $(document).on('click', '.js-trash-restore', function () {
        var $btn = $(this);
        var id = $btn.data('id');
        if (!confirm('Восстановить объект из корзины?')) {
            return;
        }
        postAction('restore', { trash_id: id }, $btn, function (data) {
            showMessage(data.message || 'Восстановлено');
            window.setTimeout(function () {
                window.location.reload();
            }, 400);
        });
    });

}(jQuery));
