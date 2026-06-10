jQuery(function($) {
	if (window.breadcrumbsPlugin === undefined)
	{
		return;
	}

	if (!window.breadcrumbsPlugin.show_subcategories)
	{
		return;
	}

	var toggleMenu = function($menu, toggle)
	{
		var document_width_before = parseInt($(document).width());

		if (toggle === undefined) {
			$menu.toggleClass('breadcrumbs-plugin__item__brothers__menu_visible');
		}
		else {
			$menu.toggleClass('breadcrumbs-plugin__item__brothers__menu_visible', !!toggle);
		}

		if ($menu.hasClass('breadcrumbs-plugin__item__brothers__menu_visible')) {
			var document_width_after = parseInt($(document).width());

			if (document_width_after > document_width_before) {
				$menu.css('left', document_width_before - document_width_after - 15);
			}
		}
		else {
			$menu.css('left', '');
		}
	};

	var click_is_initialized = false;

	$('.js-breadcrumbs-plugin__item-wrapper').each(function(index, item) {
		var $item = $(item);
		var breadcrumbs_index = $item.data('breadcrumbs_index');

		var $menu_arrow = $item.find('.js-breadcrumbs-plugin__item__brothers__arrow');
		var $brothers_menu = $('<div class="js-breadcrumbs-plugin__item__brothers__menu breadcrumbs-plugin__item__brothers__menu"></div>');

		var breadcrumbs_item = breadcrumbs_index == 'current'
			? window.breadcrumbsPlugin.current_page_item
			: window.breadcrumbsPlugin.breadcrumbs[breadcrumbs_index];

		if (!breadcrumbs_item || !breadcrumbs_item.brothers || !Object.keys(breadcrumbs_item.brothers).length || !$menu_arrow.length) {
			return;
		}

		for (var brother_id in breadcrumbs_item.brothers) {
			if (!breadcrumbs_item.brothers.hasOwnProperty(brother_id)) {
				continue;
			}

			var brother = breadcrumbs_item.brothers[brother_id];

			var $link = $('<a class="breadcrumbs-plugin__item__brothers__menu__link"></a>');
			$link.prop('href', brother.frontend_url);
			$link.text(brother.name);

			$brothers_menu.append($link);
		}

		$item.append($brothers_menu);

		var hide_timeout = null;

		if (window.breadcrumbsPlugin.show_subcategories_on_hover) {
			$menu_arrow.addClass('js-breadcrumbs-plugin__item__brothers__arrow_hover');

			var $hover_controls = $menu_arrow.add($brothers_menu);

			$hover_controls.on('mouseenter', function(e) {
				if (hide_timeout != null) {
					clearTimeout(hide_timeout);
					hide_timeout = null;
				}

				toggleMenu($brothers_menu, true);
			});

			$hover_controls.on('mouseleave', function(e) {
				hide_timeout = setTimeout(function() {
					toggleMenu($brothers_menu, false);
				}, 240);
			});
		}
		else
		{
			$menu_arrow.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				$('.js-breadcrumbs-plugin__item__brothers__menu').not($brothers_menu).each(function(index, menu) {
					toggleMenu($(menu), false);
				});

				toggleMenu($brothers_menu);
			});

			click_is_initialized = true;
		}
	});

	if (click_is_initialized) {
		$(document).on('click', function (e) {
			var $target = $(e.target);

			if ($target.closest('.js-breadcrumbs-plugin__item__brothers__menu').length == 0) {
				$('.js-breadcrumbs-plugin__item__brothers__menu').each(function (index, menu) {
					toggleMenu($(menu), false);
				});
			}
		});
	}
});