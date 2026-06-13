(function ($) {
    'use strict';

    function normalize(str) {
        return (str || '').toLowerCase().replace(/ё/g, 'е').trim();
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function highlight(label, query) {
        if (!query) {
            return escapeHtml(label);
        }
        var nLabel = normalize(label);
        var nQuery = normalize(query);
        var idx = nLabel.indexOf(nQuery);
        if (idx === -1) {
            return escapeHtml(label);
        }
        var before = label.slice(0, idx);
        var match = label.slice(idx, idx + query.length);
        var after = label.slice(idx + query.length);
        return escapeHtml(before) + '<span class="form-city-mark">' + escapeHtml(match) + '</span>' + escapeHtml(after);
    }

    function FormCitySelect($root, data) {
        this.$root = $root;
        this.data = data;
        this.$input = $root.find('.form-city-input');
        this.$hidden = $root.find('.form-city-value');
        this.$dropdown = $root.find('.form-city-dropdown');
        this.$customWrap = $root.find('.form-city-custom');
        this.$customInput = $root.find('.form-city-custom-input');
        this.query = '';
        this.activeIndex = -1;
        this.isOpen = false;
        this.selectedValue = this.$hidden.val() || '';
        this.selectedLabel = this.$input.val() || '';

        this.init();
    }

    FormCitySelect.prototype.init = function () {
        var self = this;

        if (this.selectedValue && this.selectedValue !== this.data.otherValue) {
            this.$input.val(this.selectedValue);
        } else if (this.selectedValue === this.data.otherValue) {
            this.$input.val(this.data.otherLabel);
            this.$customWrap.show();
        }

        this.renderList('');
        this.bindEvents();
    };

    FormCitySelect.prototype.bindEvents = function () {
        var self = this;

        this.$input.on('focus click', function () {
            self.open();
        });

        this.$input.on('input', function () {
            self.query = self.$input.val();
            self.selectedValue = '';
            self.$hidden.val('');
            self.$customWrap.hide();
            self.$customInput.val('');
            self.open();
            self.renderList(self.query);
        });

        this.$root.find('.form-city-toggle').on('click', function (e) {
            e.preventDefault();
            if (self.isOpen) {
                self.close();
            } else {
                self.$input.focus();
                self.open();
            }
        });

        this.$dropdown.on('click', '.form-city-option button', function (e) {
            e.preventDefault();
            var value = $(this).data('value');
            var label = $(this).data('label');
            self.select(value, label);
        });

        this.$customInput.on('input', function () {
            if (self.selectedValue === self.data.otherValue) {
                self.$hidden.val(self.data.otherValue);
            }
        });

        this.$input.on('keydown', function (e) {
            var $options = self.$dropdown.find('.form-city-option:visible');
            if (e.keyCode === 40) {
                e.preventDefault();
                self.activeIndex = Math.min(self.activeIndex + 1, $options.length - 1);
                self.syncActive($options);
            } else if (e.keyCode === 38) {
                e.preventDefault();
                self.activeIndex = Math.max(self.activeIndex - 1, 0);
                self.syncActive($options);
            } else if (e.keyCode === 13) {
                if (self.isOpen && self.activeIndex >= 0) {
                    e.preventDefault();
                    $options.eq(self.activeIndex).find('button').trigger('click');
                }
            } else if (e.keyCode === 27) {
                self.close();
            }
        });

        $(document).on('click.formCity', function (e) {
            if (!$.contains(self.$root[0], e.target)) {
                self.close();
            }
        });
    };

    FormCitySelect.prototype.syncActive = function ($options) {
        this.$dropdown.find('.form-city-option').removeClass('is-active');
        if (selfActive($options, this.activeIndex)) {
            var $item = $options.eq(this.activeIndex);
            $item.addClass('is-active');
            this.scrollIntoView($item);
        }
    };

    function selfActive($options, index) {
        return index >= 0 && index < $options.length;
    }

    FormCitySelect.prototype.scrollIntoView = function ($item) {
        if (!$item.length) {
            return;
        }
        var container = this.$dropdown[0];
        var item = $item[0];
        if (item.offsetTop < container.scrollTop) {
            container.scrollTop = item.offsetTop;
        } else if (item.offsetTop + item.offsetHeight > container.scrollTop + container.clientHeight) {
            container.scrollTop = item.offsetTop + item.offsetHeight - container.clientHeight;
        }
    };

    FormCitySelect.prototype.open = function () {
        this.isOpen = true;
        this.$root.addClass('is-open');
        this.activeIndex = -1;
    };

    FormCitySelect.prototype.close = function () {
        this.isOpen = false;
        this.$root.removeClass('is-open');
        if (this.selectedValue) {
            this.$input.val(this.selectedLabel);
        }
    };

    FormCitySelect.prototype.select = function (value, label) {
        this.selectedValue = value;
        this.selectedLabel = label;
        this.$hidden.val(value);
        this.$input.val(label);
        this.$input.removeClass('error');

        if (value === this.data.otherValue) {
            this.$customWrap.slideDown(150);
            this.$customInput.focus();
        } else {
            this.$customWrap.hide();
            this.$customInput.val('');
        }

        this.close();
    };

    FormCitySelect.prototype.filterCities = function (cities, query) {
        if (!query) {
            return cities;
        }
        var nQuery = normalize(query);
        return cities.filter(function (city) {
            return normalize(city).indexOf(nQuery) !== -1;
        });
    };

    FormCitySelect.prototype.renderList = function (query) {
        var html = [];
        var nQuery = normalize(query);
        var hasQuery = nQuery.length > 0;

        var showOther = !hasQuery
            || normalize(this.data.otherLabel).indexOf(nQuery) !== -1
            || nQuery.indexOf('нет') !== -1
            || nQuery.indexOf('спис') !== -1;

        if (showOther) {
            html.push(this.renderOption(this.data.otherValue, this.data.otherLabel, true, query));
        }

        var top = hasQuery ? this.filterCities(this.data.top20, query) : this.data.top20;
        var rest = hasQuery ? this.filterCities(this.data.rest, query) : this.data.rest;

        if (!hasQuery && top.length) {
            html.push('<li class="form-city-separator" role="presentation"><span>' + escapeHtml(this.data.top20Separator || 'Топ-20 городов по численности населения') + '</span></li>');
        }
        top.forEach(function (city) {
            html.push(this.renderOption(city, city, false, query));
        }, this);

        if (!hasQuery && rest.length) {
            html.push('<li class="form-city-separator" role="presentation"><span>' + escapeHtml(this.data.allSeparator || 'Все города России (А–Я)') + '</span></li>');
        }
        rest.forEach(function (city) {
            html.push(this.renderOption(city, city, false, query));
        }, this);

        if (!html.length) {
            html.push('<li class="form-city-empty">Город не найден. Выберите «Моего города нет в списке».</li>');
        }

        this.$dropdown.html(html.join(''));
        this.activeIndex = -1;
    };

    FormCitySelect.prototype.renderOption = function (value, label, isOther, query) {
        var selected = this.selectedValue === value ? ' is-selected' : '';
        var otherClass = isOther ? ' form-city-option--other' : '';
        return '<li class="form-city-option' + selected + otherClass + '" role="option">'
            + '<button type="button" data-value="' + escapeHtml(value) + '" data-label="' + escapeHtml(label) + '">'
            + highlight(label, query)
            + '</button></li>';
    };

    FormCitySelect.prototype.validate = function () {
        var value = this.$hidden.val();
        if (!value) {
            this.$input.addClass('error');
            return false;
        }
        if (value === this.data.otherValue) {
            var custom = $.trim(this.$customInput.val());
            if (!custom) {
                this.$customInput.addClass('error');
                return false;
            }
            this.$customInput.removeClass('error');
        }
        this.$input.removeClass('error');
        return true;
    };

    window.initFormCitySelect = function (selector, data) {
        return $(selector).map(function () {
            return new FormCitySelect($(this), data);
        }).get();
    };

    $(function () {
        if (window.formAppCityData) {
            window.formAppCityWidgets = initFormCitySelect('.form-city-select', window.formAppCityData);

            $('.wa-form.app form').on('submit', function () {
                var ok = true;
                (window.formAppCityWidgets || []).forEach(function (widget) {
                    if (!widget.validate()) {
                        ok = false;
                    }
                });
                return ok;
            });
        }
    });
})(jQuery);
