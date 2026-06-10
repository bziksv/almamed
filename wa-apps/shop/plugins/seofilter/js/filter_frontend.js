window.seofilterInit = function ($, data) {
	$(document).trigger('shop_seofilter.pre_init', [data]);

	var filter_form_selector = '.filters form, .filters2 form, .c-filters__form, .bs_plugin_filters form, .sfilter_plugin_filters form';

	var category_url = data.category_url;
	var filter_url = data.filter_url;
	var current_filter_params = data.current_filter_params;
	var keep_page_number_param = data.keep_page_number_param;
	var block_empty_feature_values = data.block_empty_feature_values;

	var price_min = data.price_min;
	var price_max = data.price_max;

	var excluded_get_params = data.excluded_get_params;

	var yandex_counter_code = data.yandex_counter_code;

	var stop_propagation_in_frontend_script = data.stop_propagation_in_frontend_script;

	var feature_value_ids = data.feature_value_ids;
	if (typeof feature_value_ids !== "object")
	{
		feature_value_ids = {};
	}

	if (window.seofilterOnFilterSuccessCallbacks === undefined)
	{
		window.seofilterOnFilterSuccessCallbacks = [];
	}

	if (!Array.isArray(excluded_get_params))
	{
		excluded_get_params = [];
	}
	excluded_get_params.push('sort');
	excluded_get_params.push('order');
	excluded_get_params.push('page');

	var currentUrl = window.location.toString();

	var getFilterForm = function () {
		var $form = $(filter_form_selector).filter(':visible').first();
		if ($form.length === 0) {
			$form = $(filter_form_selector).first();
		}

		return $form;
	};

	var getFeatureParams = function ($filterForm) {
		var fields = $filterForm.serializeArray();
		var params = [];
		var range_params = [];

		var price_temp;

		var filtered_codes = [];
		for (var i = 0; i < fields.length; i++)
		{
			if ($.inArray(fields[i].name, excluded_get_params) != -1)
			{
				continue;
			}

			var attr = fields[i].name.match(/^(.+)\[(.*)\]$/);
			if (attr && $.inArray(attr[2], ['min', 'max', 'unit']) != -1)
			{
				var feature_code = attr[1];
				var value_index = attr[2];
				var value_obj;

				var index;
				index = null;

				for (var stored_params_index in range_params)
				{
					if (range_params[stored_params_index].name === feature_code)
					{
						index = stored_params_index;
						break;
					}
				}

				if (index === null)
				{
					value_obj = {
						name: feature_code,
						value: {}
					};

					if (fields[i].value !== '')
					{
						value_obj.value[value_index] = fields[i].value;
					}

					range_params.push(value_obj);
				}
				else if (fields[i].value !== '')
				{
					range_params[index].value[value_index] = fields[i].value;
				}
			}
			else if ((price_min || parseInt(price_min) === 0) && fields[i].name == 'price_min')
			{
				price_temp = parseFloat(fields[i].value.replace(/[^0-9]/g, ''));
				// убираем цену из параметра в "кривых" темах, в которых не могут граничные значения прятать placeholder'ом
				if (!isNaN(price_temp) && price_temp != price_min)
				{
					params.push({
						name: 'price_min',
						value: price_temp
					});
					filtered_codes.push(fields[i].name);
				}
			}
			else if (price_max && fields[i].name == 'price_max')
			{
				price_temp = parseFloat(fields[i].value.replace(/[^0-9]/g, ''));
				if (!isNaN(price_temp) && price_temp != price_max)
				{
					params.push({
						name: 'price_max',
						value: price_temp
					});
					filtered_codes.push(fields[i].name);
				}
			}
			else if (fields[i].value !== '')
			{
				var attr_name = attr || $.inArray(fields[i].name, ['price_min', 'price_max']) != -1 ? fields[i].name
					: (fields[i].name + "[]");
				params.push({
					name: attr_name,
					value: fields[i].value
				});
				filtered_codes.push(attr_name);
			}
		}

		current_filter_params.forEach(function (item) {
			if (filtered_codes.indexOf(item.name) === -1)
			{
				params.push(item);
			}
		});

		for (i in range_params)
		{
			if (range_params[i].value.min === undefined && range_params[i].value.max === undefined)
			{
				continue;
			}

			for (attr in range_params[i].value)
			{
				params.push({
					name: range_params[i].name + '[' + attr + ']',
					value: range_params[i].value[attr]
				});
			}
		}

		return params;
	};

	var getAdditionalParams = function (url) {
		var query_match = url.match(/\?(.*)$/);
		var additional_params = [];

		if (query_match === null || !query_match[1])
		{
			return [];
		}

		var query_parts = query_match[1].split('&');

		for (var i in query_parts)
		{
			var part = query_parts[i].split('=');
			if (part.length != 2 || part[0] == '_')
			{
				continue;
			}

			if ($.inArray(part[0], excluded_get_params) != -1 && part[1] !== '')
			{
				additional_params.push({
					name: part[0],
					value: part[1]
				});
			}
		}

		return additional_params;
	};

	var featuresToString = function (features) {
		var result = ['_=' + new Date().getTime()];

		features.sort(function (f1, f2) {
			var cmp_s = f1.name.localeCompare(f2.name);

			return cmp_s == 0
				? f1.value.localeCompare(f2.value)
				: cmp_s;
		});

		for (var i in features)
		{
			featureToString(result, features[i].name, features[i].value)
		}

		return result.join('&');
	};

	var featureToString = function (result, name, value) {
		if ($.isArray(value))
		{
			for (var i in value)
			{
				var kname = name;

				if (typeof i == 'string')
				{
					kname += '[' + i + ']';
				}
				else
				{
					kname += '[]';
				}

				featureToString(result, kname, value[i]);
			}
		}
		else
		{
			result.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
		}
	};

	var isEnableHistory = function () {
		return !!(history.replaceState && history.state !== undefined);
	};

	var removeParamsFromQueryString = function (query, params) {
		var prepend_question = false;

		if (query && query.charAt(0) == '?') {
			query = query.substr(1);

			prepend_question = true;
		}

		var vars = query.split("&");
		var result_parts = [];

		for (var i = 0; i < vars.length; i++) {
			var pair = vars[i].split("=");
			if (pair.length != 2) {
				continue;
			}

			var key = decodeURIComponent(pair[0]);

			if (params.indexOf(key) === -1) {
				result_parts.push(vars[i]);
			}
		}

		return (prepend_question && result_parts.length > 0 ? '?' : '') + result_parts.join('&');
	};

	var replaceCurrentUrl = function(url) {
		var current_link = document.createElement('a');

		current_link.href = url;

		var params_to_remove = ['_'];
		if (!keep_page_number_param) {
			params_to_remove.push('page');
		}
		current_link.search = removeParamsFromQueryString(current_link.search, params_to_remove);

		if (current_link.search === '')
		{
			current_link.search = '';
		}

		if (current_link.search === '?' || current_link.href.substring(current_link.href.length - 1) === '?')
		{
			current_link.href = current_link.href.substring(0, current_link.href.length - 1);
		}

		var current_url = current_link.href;

		var state = {
			filter_url: current_url,
			prev_url: window.location.href,
			is_seofilter: true
		};
		window.history.pushState(state, '', current_url);
	};

	var getLinkPathname = function (link) {
		// fix для ie
		var pathname = link.pathname;
		if (!pathname.length || pathname[0] === '?')
		{
			pathname = location.pathname + pathname;
		}
		else if (pathname[0] !== '/')
		{
			pathname = '/' + pathname;
		}

		return pathname;
	};

	var updateFilterFormValueAvailability = function ($form, feature_value_ids) {
		if (!block_empty_feature_values || !$form.length) {
			return;
		}

		$form
			.find('input').prop('disabled', false).trigger('refresh')
			.closest('label').removeClass('disabled sfilter-plugin__filter-feature-value_disabled');


		if (!feature_value_ids) {
			return;
		}

		var feature_codes = Object.keys(feature_value_ids);

		feature_codes.forEach(function (feature_code) {
			if (!feature_value_ids.hasOwnProperty(feature_code) || feature_value_ids[feature_code] === 'all')
			{
				return;
			}

			var $inputs = $form.find('[name="' + feature_code + '"]').not(':checked');
			if (!$inputs.length)
			{
				$inputs = $form.find('[name="' + feature_code + '[]"]').not(':checked');
			}

			$inputs.each(function (index, input) {
				var $input = $(input);
				var value = $input.val();
				if (value && feature_value_ids[feature_code].indexOf(value) === -1)
				{
					$input
						.prop('disabled', true)
						.trigger('refresh')
						.closest('label').addClass('disabled sfilter-plugin__filter-feature-value_disabled');
				}
			});


		});
	};

	var hasSortParams = function(params) {
		for (var i in params) {
			if (params[i].name === 'sort' || params[i].name === 'order') {
				return true;
			}
		}

		return false;
	};

	$(document).ajaxSend(function (event, jqXHR, ajaxOptions) {
		if (!ajaxOptions) {
			return;
		}

		var link = document.createElement('a');
		var ajaxUrl = ajaxOptions.url;
		link.href = ajaxUrl;

		var pathname = getLinkPathname(link);
		var $filterForm = getFilterForm();

		if (
			$filterForm.length > 0
			&& (pathname == category_url || pathname == filter_url || pathname == encodeURI(category_url))
		)
		{
			var params = getFeatureParams($filterForm);
			var additional_params_from_ajax = getAdditionalParams(ajaxUrl);

			if (!hasSortParams(additional_params_from_ajax)) {
				getAdditionalParams(window.location.href)
					.filter(function(param) {return param.name.toLowerCase() === 'sort' || param.name.toLowerCase() === 'order';})
					.forEach(function(param) {
						additional_params_from_ajax.push(param);
					});
			}

			ajaxOptions.url = category_url + '?' + featuresToString(params.concat(additional_params_from_ajax));
		}
	});

	$(document).ajaxSuccess(function (event, jqXHR, ajaxOptions, response) {
		if (!ajaxOptions) {
			return;
		}

		var link = document.createElement('a');
		link.href = ajaxOptions.url;

		var pathname = getLinkPathname(link);

		if (pathname == category_url || pathname == filter_url || pathname == encodeURI(category_url))
		{
			var $response = $('<div></div>').html(response);
			var meta_title = $response.find('#filter_meta_title').val();
			var h1 = $response.find('#filter_h1').val();
			var description = $response.find('#filter_description').val();
			// var additional_description = $response.find('#filter_additional_description').val();
			var breadcrumbs = $response.find('.breadcrumbs');
		
			var $additional_description = $response.find('.filter-additional-desc');
			var additional_description = $additional_description.length > 0
				? $additional_description.html()
				: '';

			var new_feature_value_ids = $response.find('#feature_value_ids').data('feature_value_ids');

			var $form = getFilterForm();
			updateFilterFormValueAvailability($form, new_feature_value_ids);

			var current_url;
			var $filter_current_url = $response.find('#filter_current_url');
			if ($filter_current_url.length && $filter_current_url.val().trim().length > 0 && isEnableHistory())
			{
				current_url = $filter_current_url.val();
				replaceCurrentUrl(current_url);

				currentUrl = window.location.href;
				filter_url = window.location.pathname;
			}
			else
			{
				current_url = '';
			}

			var yaVarName = 'yaCounter'.concat(yandex_counter_code);
			if (yandex_counter_code && window[yaVarName] !== undefined && typeof window[yaVarName].hit === 'function')
			{
				window[yaVarName].hit(link.href);
			}

			if (meta_title)
			{
				$('title').html(meta_title);
			}
			
			$('.category-name, .filter-h1, .s-global-header').html(h1);
			$('.category-desc, .filter-desc').html(description);
			$('.filter-additional-desc').html(additional_description);
			$('.breadcrumbs').html(breadcrumbs[0].innerHTML);

			for (var i in window.seofilterOnFilterSuccessCallbacks || [])
			{
				if (typeof window.seofilterOnFilterSuccessCallbacks[i] === 'function')
				{
					window.seofilterOnFilterSuccessCallbacks[i]($response, current_url);
				}
			}

			$(document).trigger('shop_seofilter.filter_success', [$response, current_url]);
		}
	});

	$(function () {
		$(window).on('popstate', function (event) {
			if (currentUrl != window.location.toString())
			{
				window.location.reload();
			}
		});

		$('.filter-link:not(.sfilter-link_init), .seofilter-link:not(.sfilter-link_init)').each(function () {
			var $this = $(this);
			$this.addClass('sfilter-link_init');
			$this.on('click', function (e) {
				$this.closest('label').trigger('click');
				e.preventDefault();

				if (!stop_propagation_in_frontend_script) {
					e.stopPropagation();
				}
			});
		});

		// $(window).on('click', '.filter-link', function(e) {
		// 	var $this = $(this);
		//
		// 	$this.closest('label').trigger('click');
		// 	e.preventDefault();
		//
		// 	if (!stop_propagation_in_frontend_script) {
		// 		e.stopPropagation();
		// 	}
		// });

		if (category_url !== undefined && category_url !== null && category_url.length)
		{
			$('a.filters-reset').prop('href', category_url);
		}

		var $filter_form = getFilterForm();
		if ($filter_form.length)
		{
			updateFilterFormValueAvailability($filter_form, feature_value_ids);

			$filter_form
				.find('input[name="sort"],input[name="order"]')
				.prop('disabled', true);
		}
	});

	$(document).trigger('shop_seofilter.init');
};
