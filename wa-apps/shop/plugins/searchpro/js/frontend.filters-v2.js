(function() {
	'use strict';

	if (window.shop_searchpro_filters_v2) {
		return;
	}

	function parseQuery(search) {
		var params = {};
		(search || window.location.search).replace(/^\?/, '').split('&').forEach(function(part) {
			if (!part) {
				return;
			}
			var pair = part.split('=');
			params[decodeURIComponent(pair[0])] = decodeURIComponent(pair.slice(1).join('=') || '');
		});
		return params;
	}

	function serializeForm(form) {
		var parts = [];
		Array.prototype.forEach.call(form.elements, function(el) {
			if (!el.name || el.disabled) {
				return;
			}
			var type = (el.type || '').toLowerCase();
			if ((type === 'checkbox' || type === 'radio') && !el.checked) {
				return;
			}
			if (el.value === '') {
				return;
			}
			parts.push(encodeURIComponent(el.name) + '=' + encodeURIComponent(el.value));
		});
		return parts;
	}

	function SearchproFilters(wrapperId, pageId, isAjax) {
		this.wrapper = document.getElementById(wrapperId);
		this.pageWrapper = document.getElementById(pageId);
		this.isAjax = !!isAjax;
		if (!this.wrapper || !this.pageWrapper) {
			return;
		}
		this.init();
	}

	SearchproFilters.prototype.qs = function(selector, root) {
		return (root || this.wrapper).querySelector(selector);
	};

	SearchproFilters.prototype.qsa = function(selector, root) {
		return Array.prototype.slice.call((root || this.wrapper).querySelectorAll(selector));
	};

	SearchproFilters.prototype.getForm = function() {
		return this.qs('.js-searchpro__filters-form');
	};

	SearchproFilters.prototype.getPageRequestId = function() {
		return (this.pageWrapper.id || '').replace(/^searchpro-page-wrapper-/, '');
	};

	SearchproFilters.prototype.init = function() {
		var self = this;
		var form = this.getForm();
		if (!form) {
			return;
		}

		form.addEventListener('submit', function(e) {
			e.preventDefault();
			self.ajaxForm();
		});

		if (this.isAjax) {
			form.addEventListener('change', function() {
				self.ajaxForm();
			});
		}

		this.qsa('.js-searchpro__filter-show-more-link').forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				self.toggleShowMore(link);
			});
		});

		this.qsa('.js-searchpro__filters-toggle-button').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				self.toggleFiltersForm(btn);
			});
		});

		this.qsa('.js-searchpro__filter-toggle-button').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				self.toggleFilterSection(btn);
			});
		});

		var clearBtn = this.qs('.js-searchpro__filters-clear-button');
		if (clearBtn) {
			clearBtn.addEventListener('click', function(e) {
				e.preventDefault();
				self.clearForm();
			});
		}

		this.qsa('.js-searchpro__filters-mobile-button').forEach(function(btn) {
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				self.showMobileFilters();
			});
		});
	};

	SearchproFilters.prototype.toggleShowMore = function(link) {
		var filter = link.closest('.js-searchpro__filter');
		if (!filter) {
			return;
		}
		var expanded = filter.getAttribute('data-state') === '1';
		var hideCaption = link.getAttribute('data-hide-caption') || '';
		var showCaption = link.getAttribute('data-show-caption') || '';
		this.qsa('.js-searchpro__filter-item--hidden, .js-searchpro__filter-label--hidden', filter).forEach(function(el) {
			el.style.display = expanded ? 'none' : '';
		});
		link.textContent = expanded ? showCaption : hideCaption;
		filter.setAttribute('data-state', expanded ? '0' : '1');
	};

	SearchproFilters.prototype.toggleFiltersForm = function(btn) {
		var container = this.qs('.js-searchpro__filters-container');
		if (!container) {
			return;
		}
		var open = container.getAttribute('data-state') === '1';
		var hideCaption = btn.getAttribute('data-hide-caption') || '';
		var showCaption = btn.getAttribute('data-show-caption') || '';
		container.classList.toggle('js-searchpro__filters--form-shown', !open);
		btn.textContent = open ? showCaption : hideCaption;
		container.setAttribute('data-state', open ? '0' : '1');
	};

	SearchproFilters.prototype.toggleFilterSection = function(btn) {
		var filter = btn.closest('.js-searchpro__filter');
		if (!filter) {
			return;
		}
		var hidden = filter.classList.toggle('js-searchpro__filter--hidden');
		filter.setAttribute('data-state', hidden ? '1' : '0');
	};

	SearchproFilters.prototype.clearForm = function() {
		var form = this.getForm();
		if (!form) {
			return;
		}
		Array.prototype.forEach.call(form.elements, function(el) {
			var type = (el.type || '').toLowerCase();
			if (type === 'text' || type === 'number') {
				el.value = '';
			}
			if (type === 'checkbox' || type === 'radio') {
				el.checked = false;
			}
		});
		this.ajaxForm();
	};

	SearchproFilters.prototype.getMobileWindow = function() {
		var existing = document.querySelector('.searchpro__page-mobile-filters_window');
		if (existing) {
			return existing;
		}
		var win = document.createElement('div');
		win.className = 'searchpro__page-mobile-filters_window';
		win.innerHTML = ''
			+ '<div class="searchpro__page-mobile-filters_window-header">'
			+ '<div class="searchpro__page-mobile-filters_window-header_title">Фильтры</div>'
			+ '<div class="searchpro__page-mobile-filters_window-header_close-button js-searchpro__filters-close-button" role="button"></div>'
			+ '</div>'
			+ '<div class="searchpro__page-mobile-filters_window-filters"></div>';
		document.body.appendChild(win);
		var self = this;
		win.querySelector('.js-searchpro__filters-close-button').addEventListener('click', function() {
			self.closeMobileFilters();
		});
		return win;
	};

	SearchproFilters.prototype.showMobileFilters = function() {
		var win = this.getMobileWindow();
		var slot = win.querySelector('.searchpro__page-mobile-filters_window-filters');
		if (slot && !slot.contains(this.wrapper)) {
			slot.appendChild(this.wrapper);
		}
		win.style.display = 'block';
	};

	SearchproFilters.prototype.closeMobileFilters = function() {
		var win = document.querySelector('.searchpro__page-mobile-filters_window');
		if (win) {
			win.style.display = 'none';
		}
	};

	SearchproFilters.prototype.prepareReplaceContent = function() {
		if (window.jQuery && window.jQuery.fn.lazyLoad) {
			window.jQuery(window).lazyLoad('sleep');
		}
		var content = this.pageWrapper.querySelector('.js-searchpro__page-content');
		if (content) {
			content.innerHTML = '';
			content.classList.add('js-searchpro__page-content--loading');
		}
	};

	SearchproFilters.prototype.replaceContent = function(doc, url) {
		var newWrapper = doc.getElementById(this.pageWrapper.id);
		if (!newWrapper) {
			return;
		}
		var map = [
			['.js-searchpro__page-content', '.js-searchpro__page-content'],
			['.js-searchpro__page-description', '.js-searchpro__page-description'],
			['.js-searchpro__page-categories', '.js-searchpro__page-categories'],
			['.js-searchpro__page-sort', '.js-searchpro__page-sort']
		];
		map.forEach(function(pair) {
			var target = this.pageWrapper.querySelector(pair[0]);
			var source = newWrapper.querySelector(pair[1]);
			if (target && source) {
				target.innerHTML = source.innerHTML;
				if (pair[0] === '.js-searchpro__page-content') {
					target.classList.remove('js-searchpro__page-content--loading');
				}
			}
		}, this);

		if (window.history && window.history.pushState) {
			window.history.pushState({}, '', url);
		}
		if (window.jQuery && window.jQuery.fn.lazyLoad) {
			window.jQuery(window).lazyLoad('reload');
		}
		if (window.shop_searchpro_page_v2) {
			new window.shop_searchpro_page_v2(this.pageWrapper.id);
		}
	};

	SearchproFilters.prototype.ajaxForm = function() {
		var self = this;
		var form = this.getForm();
		if (!form) {
			return;
		}

		var parts = serializeForm(form);
		var params = parseQuery();
		if (params.sort) {
			parts.push('sort=' + encodeURIComponent(params.sort));
		}
		if (params.order) {
			parts.push('order=' + encodeURIComponent(params.order));
		}

		var url = '?' + parts.join('&');
		var requestId = this.getPageRequestId();
		this.prepareReplaceContent();

		var body = new FormData();
		body.append('shop_searchpro_id', requestId);

		fetch(url + '&_=_', {
			method: 'POST',
			body: body,
			credentials: 'same-origin'
		}).then(function(response) {
			return response.text();
		}).then(function(html) {
			var parser = new DOMParser();
			var doc = parser.parseFromString(html, 'text/html');
			self.replaceContent(doc, url);
			self.closeMobileFilters();
		}).catch(function() {
			window.location.href = url;
		});
	};

	window.shop_searchpro_filters_v2 = SearchproFilters;
	window.shop_searchpro_filters = SearchproFilters;

})();
