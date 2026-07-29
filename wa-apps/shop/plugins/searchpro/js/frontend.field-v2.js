(function() {
	'use strict';

	if (window.shop_searchpro_field_v2) {
		return;
	}

	var DEBOUNCE_MS = 250;
	var HISTORY_SAVE_MS = 1500;

	var escapeHtml = function(text) {
		var div = document.createElement('div');
		div.textContent = text || '';
		return div.innerHTML;
	};

	var cookieGetJson = function(key) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + key.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
		if (!match) {
			return [];
		}
		try {
			return JSON.parse(decodeURIComponent(match[1]));
		} catch (e) {
			return [];
		}
	};

	var cookieSetJson = function(key, value) {
		document.cookie = key + '=' + encodeURIComponent(JSON.stringify(value)) + ';path=/;max-age=31536000';
	};

	var highlightName = function(name, query) {
		var safe = escapeHtml(name);
		if (!query || query.length < 2) {
			return safe;
		}
		var pattern = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'iu');
		return safe.replace(pattern, '<span class="searchpro-highlighted">$1</span>');
	};

	var renderEntityContent = function(entity, query) {
		var frag = document.createDocumentFragment();

		if (entity.image) {
			var imgWrap = document.createElement('div');
			imgWrap.className = 'searchpro__dropdown-entity_image-container';
			var img = document.createElement('img');
			img.className = 'searchpro__dropdown-entity_image';
			img.src = entity.image;
			img.alt = '';
			imgWrap.appendChild(img);
			frag.appendChild(imgWrap);
		}

		var nameEl = document.createElement('div');
		nameEl.className = 'searchpro__dropdown-entity_name';
		nameEl.innerHTML = highlightName(entity.name, query);
		if (entity.subname) {
			var sub = document.createElement('span');
			sub.className = 'searchpro__dropdown-entity_subname';
			sub.textContent = entity.subname;
			nameEl.appendChild(sub);
		}
		frag.appendChild(nameEl);

		if (entity.type === 'products') {
			var priceWrap = document.createElement('div');
			priceWrap.className = 'searchpro__dropdown-entity_meta searchpro__dropdown-entity_price-container';
			if (entity.price_on_request) {
				var priceEl = document.createElement('span');
				priceEl.className = 'searchpro__dropdown-entity_price';
				priceEl.textContent = 'Цена по запросу';
				priceWrap.appendChild(priceEl);
			} else if (entity.price_html) {
				var priceHtml = document.createElement('span');
				priceHtml.className = 'searchpro__dropdown-entity_price';
				priceHtml.innerHTML = entity.price_html;
				priceWrap.appendChild(priceHtml);
				if (entity.compare_price_html) {
					var compareEl = document.createElement('span');
					compareEl.className = 'searchpro__dropdown-entity_compare-price';
					compareEl.innerHTML = entity.compare_price_html;
					priceWrap.appendChild(compareEl);
				}
			}
			if (priceWrap.childNodes.length) {
				frag.appendChild(priceWrap);
			}
		} else if (entity.type === 'categories' && entity.count_label) {
			var countEl = document.createElement('div');
			countEl.className = 'searchpro__dropdown-entity_meta searchpro__dropdown-entity_count';
			countEl.textContent = entity.count_label;
			frag.appendChild(countEl);
		}

		return frag;
	};

	var renderCategoryRow = function(entity, query) {
		var row = document.createElement('div');
		row.className = 'searchpro__dropdown-section-row js-searchpro__dropdown-section';
		if (entity.id) {
			row.setAttribute('data-category-id', String(entity.id));
		}
		if (entity.full_url) {
			row.setAttribute('data-category-full-url', entity.full_url);
		}

		var filterBtn = document.createElement('button');
		filterBtn.type = 'button';
		filterBtn.className = 'searchpro__dropdown-section-filter js-searchpro__dropdown-section-filter';
		filterBtn.setAttribute('data-action', 'filter:category');
		if (entity.id) {
			filterBtn.setAttribute('data-category-id', String(entity.id));
		}
		if (entity.full_url) {
			filterBtn.setAttribute('data-category-full-url', entity.full_url);
		}
		filterBtn.setAttribute('data-category-name', entity.name || '');
		filterBtn.setAttribute('data-category-url', entity.href || entity.data_link || '');
		filterBtn.setAttribute('data-catalog-url', entity.catalog_url || entity.href || '');

		var check = document.createElement('span');
		check.className = 'searchpro__dropdown-section-check';
		check.setAttribute('aria-hidden', 'true');
		check.textContent = '✓';
		filterBtn.appendChild(check);
		filterBtn.appendChild(renderEntityContent(entity, query));
		row.appendChild(filterBtn);

		var goUrl = entity.catalog_url || entity.href || entity.data_link || '#';
		var goLink = document.createElement('a');
		goLink.className = 'searchpro__dropdown-section-go';
		goLink.href = goUrl;
		goLink.title = 'Перейти в категорию';
		goLink.setAttribute('aria-label', 'Перейти в категорию');
		goLink.innerHTML = '<i class="material-icons" aria-hidden="true">&#xE89E;</i>';
		row.appendChild(goLink);

		return row;
	};

	var renderProductRow = function(entity, query) {
		var row = document.createElement('div');
		row.className = 'searchpro__dropdown-product-row';

		var item = document.createElement('a');
		item.className = 'searchpro__dropdown-entity js-searchpro__dropdown-entity searchpro__dropdown-entity--products';
		item.setAttribute('data-action', 'goto:href');
		item.href = entity.href || '#';
		if (entity.id) {
			item.setAttribute('data-product-id', String(entity.id));
		}
		if (entity.category_id) {
			item.setAttribute('data-category-id', String(entity.category_id));
		}
		if (entity.category_full_url) {
			item.setAttribute('data-category-full-url', entity.category_full_url);
		}
		item.appendChild(renderEntityContent(entity, query));
		row.appendChild(item);

		if (entity.can_add_to_cart && entity.id) {
			var cartBtn = document.createElement('button');
			cartBtn.type = 'button';
			cartBtn.className = 'searchpro__dropdown-cart js-searchpro__dropdown-cart';
			cartBtn.title = 'В корзину';
			cartBtn.setAttribute('aria-label', 'Добавить в корзину');
			cartBtn.setAttribute('data-product-id', String(entity.id));
			if (entity.sku_id) {
				cartBtn.setAttribute('data-sku-id', String(entity.sku_id));
			}
			if (entity.cart_dialog_url) {
				cartBtn.setAttribute('data-cart-dialog-url', entity.cart_dialog_url);
			}
			cartBtn.innerHTML = '<i class="material-icons" aria-hidden="true">&#xE8CC;</i>';
			row.appendChild(cartBtn);
		}

		return row;
	};

	var renderSimpleEntity = function(entity, query) {
		var tag = entity.action === 'value:data-value' ? 'div' : 'a';
		var item = document.createElement(tag);
		item.className = 'searchpro__dropdown-entity js-searchpro__dropdown-entity searchpro__dropdown-entity--' + (entity.type || 'item');
		item.setAttribute('data-action', entity.action);

		if (entity.action === 'goto:href') {
			item.setAttribute('href', entity.href || '#');
		} else if (entity.action === 'goto:data-link') {
			item.setAttribute('href', entity.href || '#');
			item.setAttribute('data-link', entity.data_link || entity.href || '');
			item.setAttribute('data-value', entity.name || '');
		} else if (entity.action === 'value:data-value') {
			item.setAttribute('data-value', entity.data_value || entity.name || '');
		}

		item.appendChild(renderEntityContent(entity, query));
		return item;
	};

	// Popular / history: compact chips, query text only (no category subname).
	var renderQueryChip = function(entity, query) {
		var tag = entity.action === 'value:data-value' ? 'button' : 'a';
		var item = document.createElement(tag);
		item.className = 'searchpro__dropdown-chip js-searchpro__dropdown-entity searchpro__dropdown-entity--query';
		item.setAttribute('data-action', entity.action || 'value:data-value');
		if (tag === 'button') {
			item.type = 'button';
		}

		if (entity.action === 'goto:href' || entity.action === 'goto:data-link') {
			item.setAttribute('href', entity.href || entity.data_link || '#');
			if (entity.data_link) {
				item.setAttribute('data-link', entity.data_link);
			}
		}
		item.setAttribute('data-value', entity.data_value || entity.name || '');

		var nameEl = document.createElement('span');
		nameEl.className = 'searchpro__dropdown-chip-label';
		nameEl.innerHTML = highlightName(entity.name, query);
		item.appendChild(nameEl);
		return item;
	};

	var dedupeQueryEntities = function(entities) {
		var seen = {};
		var out = [];
		(entities || []).forEach(function(entity) {
			var key = String(entity.name || entity.data_value || '').toLowerCase().replace(/\s+/g, ' ').trim();
			if (!key || seen[key]) {
				return;
			}
			seen[key] = true;
			out.push(entity);
		});
		return out;
	};

	var renderQueryGroup = function(group, query) {
		var entities = dedupeQueryEntities(group.entities);
		if (!entities.length) {
			return null;
		}

		var groupEl = document.createElement('div');
		groupEl.className = 'searchpro__dropdown-group searchpro__dropdown-group-' + group.id + ' searchpro__dropdown-group--chips';

		var titleEl = document.createElement('div');
		titleEl.className = 'searchpro__dropdown-group-title';
		titleEl.textContent = group.title || '';
		groupEl.appendChild(titleEl);

		var entitiesEl = document.createElement('div');
		entitiesEl.className = 'searchpro__dropdown-group-entities searchpro__dropdown-chips js-searchpro__dropdown-entities';
		entities.forEach(function(entity) {
			entitiesEl.appendChild(renderQueryChip(entity, query));
		});
		groupEl.appendChild(entitiesEl);
		return groupEl;
	};

	var renderSuggest = function(data) {
		var root = document.createElement('div');
		root.className = 'searchpro__dropdown searchpro__dropdown--split';
		root.setAttribute('data-query', data.query || '');
		root.setAttribute('data-results-url', data.results_url || '');
		root.setAttribute('data-count', String(data.count || 0));

		var groupsById = {};
		(data.groups || []).forEach(function(group) {
			groupsById[group.id] = group;
		});

		var categories = groupsById.categories;
		var products = groupsById.products;
		var hasCategories = !!(categories && categories.entities && categories.entities.length);
		var hasProducts = !!(products && products.entities && products.entities.length);

		if (hasCategories || hasProducts) {
			var cols = document.createElement('div');
			cols.className = 'searchpro__dropdown-cols' + ((hasCategories && hasProducts) ? '' : ' searchpro__dropdown-cols--single');

			if (hasCategories) {
				var sections = document.createElement('div');
				sections.className = 'searchpro__dropdown-sections';

				var sectionsHead = document.createElement('div');
				sectionsHead.className = 'searchpro__dropdown-heading';
				sectionsHead.innerHTML = '<span class="searchpro__dropdown-heading-text">' + escapeHtml(categories.title || 'Категории') + '</span>'
					+ '<span class="searchpro__dropdown-hint">можно выбрать несколько</span>';
				sections.appendChild(sectionsHead);

				var sectionsList = document.createElement('div');
				sectionsList.className = 'searchpro__dropdown-group-entities searchpro__dropdown-list--sections js-searchpro__dropdown-entities';
				categories.entities.forEach(function(entity) {
					sectionsList.appendChild(renderCategoryRow(entity, data.query));
				});
				sections.appendChild(sectionsList);
				cols.appendChild(sections);
			}

			if (hasProducts) {
				var productsCol = document.createElement('div');
				productsCol.className = 'searchpro__dropdown-products';

				var productsHead = document.createElement('div');
				productsHead.className = 'searchpro__dropdown-products-head';
				productsHead.innerHTML = '<div class="searchpro__dropdown-heading searchpro__dropdown-heading--products">'
					+ '<span class="searchpro__dropdown-heading-text">' + escapeHtml(products.title || 'Товары') + '</span>'
					+ '<span class="searchpro__dropdown-products-count js-searchpro__dropdown-products-count">' + products.entities.length + '</span>'
					+ '</div>'
					+ '<div class="searchpro__dropdown-filter-bar js-searchpro__dropdown-filter-bar" hidden></div>';
				productsCol.appendChild(productsHead);

				var productsList = document.createElement('div');
				productsList.className = 'searchpro__dropdown-group-entities searchpro__dropdown-list--products js-searchpro__dropdown-entities';
				products.entities.forEach(function(entity) {
					productsList.appendChild(renderProductRow(entity, data.query));
				});
				productsCol.appendChild(productsList);

				var empty = document.createElement('div');
				empty.className = 'searchpro__dropdown-empty js-searchpro__dropdown-empty';
				empty.hidden = true;
				empty.textContent = 'В выбранных категориях нет товаров из этой выдачи';
				productsCol.appendChild(empty);

				cols.appendChild(productsCol);
			}

			root.appendChild(cols);
		}

		// Popular / history — compact full-width chip strip (not a tall left-only block).
		['popular', 'history'].forEach(function(id) {
			var group = groupsById[id];
			if (!group || !group.entities || !group.entities.length) {
				return;
			}
			var el = renderQueryGroup(group, data.query);
			if (el) {
				root.appendChild(el);
			}
		});

		(data.groups || []).forEach(function(group) {
			if (group.id === 'categories' || group.id === 'products' || group.id === 'popular' || group.id === 'history') {
				return;
			}
			if (!group.entities || !group.entities.length) {
				return;
			}

			var groupEl = document.createElement('div');
			groupEl.className = 'searchpro__dropdown-group searchpro__dropdown-group-' + group.id;

			var titleEl = document.createElement('div');
			titleEl.className = 'searchpro__dropdown-group-title';
			titleEl.textContent = group.title || '';
			groupEl.appendChild(titleEl);

			var entitiesEl = document.createElement('div');
			entitiesEl.className = 'searchpro__dropdown-group-entities js-searchpro__dropdown-entities';
			group.entities.forEach(function(entity) {
				entitiesEl.appendChild(renderSimpleEntity(entity, data.query));
			});
			groupEl.appendChild(entitiesEl);
			root.appendChild(groupEl);
		});

		if (data.results_url) {
			var viewAll = document.createElement('div');
			viewAll.className = 'searchpro__dropdown-view-all';
			var viewAllLink = document.createElement('a');
			viewAllLink.className = 'searchpro__dropdown-view-all-link js-searchpro__dropdown-entity js-searchpro__dropdown-view-all';
			viewAllLink.setAttribute('data-action', 'goto:href');
			viewAllLink.setAttribute('data-url-all', data.results_url);
			viewAllLink.setAttribute('data-label-all', 'Все результаты поиска');
			viewAllLink.href = data.results_url;
			viewAllLink.innerHTML = 'Все результаты поиска <span class="searchpro__dropdown-view-all-count">(' + (data.count || 0) + ')</span>';
			viewAll.appendChild(viewAllLink);
			root.appendChild(viewAll);
		}

		return root.outerHTML;
	};

	window.shop_searchpro_render_suggest = renderSuggest;

	function SearchproField(wrapperId, params) {
		this.wrapperId = wrapperId;
		this.params = params || {};
		this.root = document.getElementById(wrapperId);
		this.config = window.shop_searchpro || {};
		this.cache = {};
		this.findTimer = null;
		this.historyTimer = null;
		this.suggestAbort = null;

		if (!this.root) {
			return;
		}

		this.init();
	}

	SearchproField.prototype.qs = function(selector) {
		return this.root.querySelector(selector);
	};

	SearchproField.prototype.container = function() {
		return this.qs('.js-searchpro__field-container');
	};

	SearchproField.prototype.input = function() {
		return this.qs('.js-searchpro__field-input');
	};

	SearchproField.prototype.isEnabled = function(name) {
		return !!this.params[name + '_status'];
	};

	SearchproField.prototype.minLength = function() {
		return parseInt(this.params.dropdown_min_length, 10) || 1;
	};

	SearchproField.prototype.historyMax = function() {
		return parseInt(this.params.history_max_count, 10) || 5;
	};

	SearchproField.prototype.getContainerWidth = function() {
		var el = this.container();
		return el ? el.offsetWidth : 0;
	};

	SearchproField.prototype.getDropdownLayout = function() {
		var field = this.container() || this.root;
		if (!field) {
			return { mode: 'relative', width: 680 };
		}
		var fieldRect = field.getBoundingClientRect();
		var inMobile = !!(field.closest && (
			field.closest('.header-mobile-bar__search-panel')
			|| field.closest('.content-search-bar')
		));
		if (inMobile || window.matchMedia('(max-width: 1200px)').matches) {
			return {
				mode: 'relative',
				width: Math.max(Math.round(fieldRect.width), 280)
			};
		}

		var anchor = document.querySelector('.nav-wrapper .header-top-bar__inner')
			|| document.querySelector('.nav-wrapper > .container')
			|| document.querySelector('header.globalheader .container');
		if (!anchor) {
			return {
				mode: 'relative',
				width: Math.max(Math.round(fieldRect.width), 680)
			};
		}

		var anchorRect = anchor.getBoundingClientRect();
		var width = Math.round(anchorRect.width);
		var maxWidth = Math.max(window.innerWidth - 24, 320);
		if (width > maxWidth) {
			width = maxWidth;
		}

		return {
			mode: 'fixed',
			width: width,
			left: Math.round(anchorRect.left),
			top: Math.round(fieldRect.bottom + 4)
		};
	};

	SearchproField.prototype.applyDropdownLayout = function(box) {
		if (!box) {
			return;
		}
		var layout = this.getDropdownLayout();
		box.classList.toggle('searchpro__dropdown-box--full', layout.mode === 'fixed');
		if (layout.mode === 'fixed') {
			box.style.position = 'fixed';
			box.style.left = layout.left + 'px';
			box.style.top = layout.top + 'px';
			box.style.right = 'auto';
			box.style.width = layout.width + 'px';
			box.style.minWidth = layout.width + 'px';
			box.style.maxWidth = layout.width + 'px';
			box.style.zIndex = '1100';
		} else {
			box.style.position = '';
			box.style.left = '';
			box.style.top = '';
			box.style.right = '';
			box.style.width = layout.width + 'px';
			box.style.minWidth = layout.width + 'px';
			box.style.maxWidth = 'min(100vw - 16px, ' + layout.width + 'px)';
			box.style.zIndex = '';
		}
	};

	SearchproField.prototype.bindDropdownLayoutWatch = function() {
		var self = this;
		if (this._layoutWatchBound) {
			return;
		}
		this._layoutWatchBound = true;
		var reposition = function() {
			[self._resultsBox, self._helperBox].forEach(function(box) {
				if (box && box.style.display !== 'none') {
					self.applyDropdownLayout(box);
				}
			});
		};
		window.addEventListener('resize', reposition);
		window.addEventListener('scroll', reposition, true);
	};

	SearchproField.prototype.ensureBox = function(kind, html) {
		var prop = kind === 'results' ? '_resultsBox' : '_helperBox';
		var className = kind === 'results' ? 'js-searchpro__dropdown' : 'js-searchpro__helper';
		if (this[prop]) {
			return this[prop];
		}
		var box = document.createElement('div');
		box.className = className;
		box.style.display = 'none';
		if (html) {
			box.innerHTML = html;
		}
		var container = this.container();
		if (container && container.parentNode) {
			container.parentNode.insertBefore(box, container.nextSibling);
		}
		this[prop] = box;
		return box;
	};

	SearchproField.prototype.resultsBox = function(create) {
		if (!this._resultsBox && create) {
			this.ensureBox('results');
		}
		return this._resultsBox;
	};

	SearchproField.prototype.helperBox = function(create) {
		if (!this._helperBox && create) {
			this.ensureBox('helper', '');
		}
		return this._helperBox;
	};

	SearchproField.prototype.showBox = function(box) {
		if (!box) {
			return;
		}
		this.applyDropdownLayout(box);
		box.style.display = '';
		this.activeCategoryFilters = [];
		this.bindDropdownLayoutWatch();
		document.dispatchEvent(new CustomEvent('shop_searchpro.box_shown'));
	};

	SearchproField.prototype.hideBoxes = function() {
		[this._resultsBox, this._helperBox].forEach(function(box) {
			if (box) {
				box.style.display = 'none';
				box.classList.remove('searchpro__dropdown-box--full');
				box.style.position = '';
				box.style.left = '';
				box.style.top = '';
				box.style.right = '';
				box.style.width = '';
				box.style.minWidth = '';
				box.style.maxWidth = '';
				box.style.zIndex = '';
			}
		});
	};

	SearchproField.prototype.setLoading = function(on) {
		var container = this.container();
		if (!container) {
			return;
		}
		container.classList.toggle('js-searchpro__field-container--loading', on);
	};

	SearchproField.prototype.abortSuggest = function() {
		clearTimeout(this.findTimer);
		if (this.suggestAbort) {
			this.suggestAbort.abort();
			this.suggestAbort = null;
		}
	};

	SearchproField.prototype.dropdownKey = function(query) {
		var key = query;
		if (this.isEnabled('category')) {
			var catInput = this.qs('.js-searchpro__field-category-input');
			key += '+' + (catInput ? catInput.value : '0');
		}
		return key;
	};

	SearchproField.prototype.suggestUrl = function(query) {
		var base = this.config.suggest_url || this.config.dropdown_url || '';
		var url = base.indexOf('http') === 0
			? new URL(base)
			: new URL(base, window.location.origin);
		url.searchParams.set('q', query);
		url.searchParams.set('format', 'json');
		if (this.isEnabled('category')) {
			var catInput = this.qs('.js-searchpro__field-category-input');
			if (catInput && catInput.value && catInput.value !== '0') {
				url.searchParams.set('category_id', catInput.value);
			}
		}
		return url.toString();
	};

	SearchproField.prototype.findDropdown = function(query) {
		var self = this;
		this.abortSuggest();
		var key = this.dropdownKey(query);

		this.findTimer = setTimeout(function() {
			var box = self.resultsBox(true);
			if (self.cache[key]) {
				box.innerHTML = self.cache[key];
				self.showBox(box);
				self.applyCategoryFilters([]);
				self.setLoading(false);
				return;
			}

			self.setLoading(true);
			self.suggestAbort = new AbortController();

			fetch(self.suggestUrl(query), {
				signal: self.suggestAbort.signal,
				credentials: 'same-origin'
			}).then(function(response) {
				return response.json();
			}).then(function(data) {
				self.setLoading(false);
				if (!data || !data.groups || !data.groups.length) {
					var emptyHtml = ''
						+ '<div class="searchpro__dropdown searchpro__dropdown--empty-query">'
						+ '<div class="searchpro__dropdown-empty">'
						+ 'Ничего не найдено. Проверьте раскладку и опечатки — или попробуйте другое слово.'
						+ '</div></div>';
					self.cache[key] = emptyHtml;
					box.innerHTML = emptyHtml;
					self.showBox(box);
					return;
				}
				var html = renderSuggest(data);
				self.cache[key] = html;
				box.innerHTML = html;
				self.showBox(box);
				self.applyCategoryFilters([]);
				self.scheduleHistorySave(query);
			}).catch(function(err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				self.setLoading(false);
				box.innerHTML = '';
				box.style.display = 'none';
			});
		}, DEBOUNCE_MS);
	};

	SearchproField.prototype.scheduleHistorySave = function(query) {
		var self = this;
		clearTimeout(this.historyTimer);
		this.historyTimer = setTimeout(function() {
			self.saveHistory(query);
		}, HISTORY_SAVE_MS);
	};

	SearchproField.prototype.saveHistory = function(query) {
		if (!this.isEnabled('history') || !this.params.history_cookie_key) {
			return;
		}
		var key = this.params.history_cookie_key;
		var items = cookieGetJson(key);
		if (items.indexOf(query) === -1) {
			items.push(query);
			cookieSetJson(key, items);
			this.updateHistoryBox(items);
		}
	};

	SearchproField.prototype.updateHistoryBox = function(items) {
		var helper = this.helperBox(false);
		var templateWrap = this.params.helper_dropdown && this.params.helper_dropdown.template;
		if (!helper || !templateWrap) {
			return;
		}
		var temp = document.createElement('div');
		temp.innerHTML = templateWrap;
		var historyBlock = temp.querySelector('.js-searchpro__dropdown-history');
		if (!historyBlock) {
			return;
		}
		var entities = historyBlock.querySelector('.js-searchpro__dropdown-entities');
		var sample = historyBlock.querySelector('.js-searchpro__dropdown-entity');
		if (!entities || !sample) {
			return;
		}
		entities.innerHTML = '';
		items.slice().reverse().slice(0, this.historyMax()).forEach(function(item) {
			var clone = sample.cloneNode(true);
			clone.setAttribute('data-value', item);
			var queryEl = clone.querySelector('.js-searchpro__dropdown-entity_query');
			if (queryEl) {
				queryEl.textContent = item;
			} else {
				clone.textContent = item;
			}
			if (clone.getAttribute('href')) {
				clone.setAttribute('href', clone.getAttribute('href').replace(/%QUERY%/, encodeURIComponent(item)));
			}
			entities.appendChild(clone);
		});
		var target = helper.querySelector('.js-searchpro__dropdown-history');
		if (target) {
			target.innerHTML = historyBlock.innerHTML;
		}
	};

	SearchproField.prototype.deleteHistoryItem = function(entityEl) {
		if (!this.isEnabled('history')) {
			return;
		}
		var value = entityEl.getAttribute('data-value');
		if (!value) {
			var queryEl = entityEl.querySelector('.js-searchpro__dropdown-entity_query');
			value = queryEl ? queryEl.textContent.trim() : '';
		}
		if (!value) {
			return;
		}
		var key = this.params.history_cookie_key;
		var items = cookieGetJson(key).filter(function(item) {
			return item !== value;
		});
		cookieSetJson(key, items);
		this.updateHistoryBox(items);
		entityEl.parentNode.removeChild(entityEl);
	};

	SearchproField.prototype.showHelper = function() {
		if (!this.isEnabled('history') && !this.isEnabled('popular')) {
			return;
		}
		var box = this.helperBox(true);
		if (box && box.innerHTML.trim()) {
			this.showBox(box);
		}
	};

	SearchproField.prototype.handleInputValue = function(value) {
		var self = this;
		clearTimeout(this.historyTimer);
		var clearBtn = this.qs('.js-searchpro__field-clear-button');
		if (clearBtn) {
			clearBtn.style.display = value.length ? '' : 'none';
		}
		if (!value.length) {
			this.abortSuggest();
			this.hideBoxes();
			if (this._resultsBox) {
				this._resultsBox.innerHTML = '';
			}
			return;
		}
		if (this.isEnabled('dropdown') && value.length >= this.minLength()) {
			if (this._helperBox) {
				this._helperBox.style.display = 'none';
			}
			this.closeCategoriesList();
			this.findDropdown(value);
		} else {
			if (this._resultsBox) {
				this._resultsBox.innerHTML = '';
				this._resultsBox.style.display = 'none';
			}
			this.showHelper();
		}
	};

	SearchproField.prototype.search = function() {
		var input = this.input();
		if (!input) {
			return;
		}
		var query = input.value.trim();
		if (!query) {
			return;
		}
		this.abortSuggest();
		this.setLoading(true);
		var url = this.config.results_url || '';
		if (this.isEnabled('category')) {
			var catInput = this.qs('.js-searchpro__field-category-input');
			if (catInput && catInput.value && catInput.value !== '0') {
				url += '/' + catInput.value;
			}
		}
		url += '/' + encodeURI(query.replace(/\//g, '%SLASH%')) + '/';
		window.location.href = url;
	};

	SearchproField.prototype.closeCategoriesList = function() {
		var list = this.qs('.js-searchpro__field-categories-list');
		var selector = this.qs('.js-searchpro__field-category-selector');
		if (list) {
			list.style.display = 'none';
		}
		if (selector) {
			selector.classList.remove('js-searchpro__field-category-selector-active');
		}
	};

	SearchproField.prototype.toggleCategoriesList = function(anchor) {
		var list = this.qs('.js-searchpro__field-categories-list');
		var selector = this.qs('.js-searchpro__field-category-selector');
		if (!list) {
			return;
		}
		var open = list.style.display !== 'block';
		list.style.display = open ? 'block' : 'none';
		if (selector) {
			selector.classList.toggle('js-searchpro__field-category-selector-active', open);
		}
		if (open) {
			var self = this;
			setTimeout(function() {
				document.addEventListener('click', function onDocClick(e) {
					if (!list.contains(e.target) && (!anchor || !anchor.contains(e.target))) {
						self.closeCategoriesList();
						document.removeEventListener('click', onDocClick);
					}
				});
			}, 0);
		}
	};

	SearchproField.prototype.loadCategories = function() {
		var self = this;
		var categoriesRoot = this.qs('.js-searchpro__field-categories-root');
		if (!categoriesRoot || !this.params.categories_url || categoriesRoot.dataset.searchproCategoriesLoaded) {
			return Promise.resolve();
		}
		categoriesRoot.dataset.searchproCategoriesLoaded = '1';

		return fetch(this.params.categories_url, { credentials: 'same-origin' })
			.then(function(r) { return r.json(); })
			.then(function(response) {
				var list = categoriesRoot.querySelector('.js-searchpro__field-categories-list');
				if (!list) {
					return;
				}
				var maxDeep = parseInt(self.params.category_filter_deep, 10) || 1;

				var appendItems = function(parent, categories, deep) {
					(categories || []).forEach(function(category) {
						var item = document.createElement('li');
						item.className = 'js-searchpro__field-category searchpro__field-category';
						item.setAttribute('data-id', category.id);
						item.title = category.name;
						var span = document.createElement('span');
						span.textContent = category.name;
						item.appendChild(span);
						parent.appendChild(item);

						if (category.childs && category.childs.length && deep < maxDeep) {
							var sub = document.createElement('ul');
							sub.className = 'searchpro__field-subcategories-list';
							appendItems(sub, category.childs, deep + 1);
							item.appendChild(sub);
							item.classList.remove('searchpro__field-category');
							item.classList.add('searchpro__field-subcategory');
						}
					});
				};

				appendItems(list, response.categories || [], 1);

				var currentId = parseInt(self.params.current_category_id, 10) || 0;
				if (currentId > 0) {
					var current = list.querySelector('.js-searchpro__field-category[data-id="' + currentId + '"]');
					if (current) {
						list.querySelectorAll('.js-searchpro__field-category').forEach(function(el) {
							el.classList.remove('selected');
						});
						current.classList.add('selected');
						var catInput = self.qs('.js-searchpro__field-category-input');
						var label = self.qs('.js-searchpro__field-category-label');
						if (catInput) {
							catInput.value = currentId;
						}
						if (label) {
							label.textContent = current.getAttribute('title') || current.textContent.trim();
						}
					}
				}
			})
			.catch(function() {});
	};

	SearchproField.prototype.loadHelper = function() {
		var self = this;
		if (this._helperLoaded || !this.params.helper_url) {
			return Promise.resolve();
		}
		if (!this.isEnabled('history') && !this.isEnabled('popular')) {
			return Promise.resolve();
		}
		this._helperLoaded = true;

		return fetch(this.params.helper_url, { credentials: 'same-origin' })
			.then(function(r) { return r.text(); })
			.then(function(html) {
				if (!html) {
					return;
				}
				var box = self.helperBox(true);
				box.innerHTML = html;
				if (self.isEnabled('history')) {
					self.updateHistoryBox(cookieGetJson(self.params.history_cookie_key));
				}
				var input = self.input();
				if (input && !input.value.trim() && document.activeElement === input) {
					self.showHelper();
				}
			})
			.catch(function() {});
	};

	SearchproField.prototype.productMatchesFilters = function(productEl, filters) {
		if (!filters || !filters.length) {
			return true;
		}
		var productId = productEl.getAttribute('data-category-id') || '';
		var productUrl = (productEl.getAttribute('data-category-full-url') || '').replace(/^\/+|\/+$/g, '');
		for (var i = 0; i < filters.length; i++) {
			var filter = filters[i];
			if (filter.id && productId && String(filter.id) === String(productId)) {
				return true;
			}
			var filterUrl = (filter.fullUrl || '').replace(/^\/+|\/+$/g, '');
			if (filterUrl && productUrl && (productUrl === filterUrl || productUrl.indexOf(filterUrl + '/') === 0)) {
				return true;
			}
		}
		return false;
	};

	SearchproField.prototype.renderFilterChips = function(dropdown, filters) {
		var filterBar = dropdown.querySelector('.js-searchpro__dropdown-filter-bar');
		if (!filterBar) {
			return;
		}
		if (!filters.length) {
			filterBar.hidden = true;
			filterBar.innerHTML = '';
			return;
		}
		var html = '<div class="searchpro__dropdown-filter-chips">';
		filters.forEach(function(filter) {
			html += '<button type="button" class="searchpro__dropdown-filter-chip js-searchpro__dropdown-filter-chip" data-category-id="'
				+ escapeHtml(String(filter.id || '')) + '">'
				+ '<span class="searchpro__dropdown-filter-chip-label">' + escapeHtml(filter.name || '') + '</span>'
				+ '<span class="searchpro__dropdown-filter-clear" title="Убрать">&times;</span>'
				+ '</button>';
		});
		html += '<button type="button" class="searchpro__dropdown-filter-reset js-searchpro__dropdown-filter-reset">Сбросить все</button>';
		html += '</div>';
		filterBar.hidden = false;
		filterBar.innerHTML = html;
	};

	SearchproField.prototype.applyCategoryFilters = function(filters) {
		var box = this._resultsBox;
		if (!box) {
			return;
		}
		var dropdown = box.querySelector('.searchpro__dropdown');
		if (!dropdown) {
			return;
		}

		this.activeCategoryFilters = filters || [];
		var self = this;

		dropdown.querySelectorAll('.js-searchpro__dropdown-section-filter').forEach(function(btn) {
			var id = btn.getAttribute('data-category-id') || '';
			var active = self.activeCategoryFilters.some(function(f) { return String(f.id) === String(id); });
			btn.classList.toggle('is-active', active);
			var row = btn.closest('.js-searchpro__dropdown-section');
			if (row) {
				row.classList.toggle('is-filter-active', active);
			}
		});

		var products = dropdown.querySelectorAll('.searchpro__dropdown-entity--products');
		var visible = 0;
		products.forEach(function(product) {
			var match = self.productMatchesFilters(product, self.activeCategoryFilters);
			product.style.display = match ? '' : 'none';
			if (match) {
				visible++;
			}
		});

		this.renderFilterChips(dropdown, this.activeCategoryFilters);

		var empty = dropdown.querySelector('.js-searchpro__dropdown-empty');
		var list = dropdown.querySelector('.searchpro__dropdown-list--products');
		var countEl = dropdown.querySelector('.js-searchpro__dropdown-products-count');
		if (empty && list) {
			var showEmpty = self.activeCategoryFilters.length && visible === 0;
			empty.hidden = !showEmpty;
			list.style.display = showEmpty ? 'none' : '';
		}
		if (countEl) {
			countEl.textContent = String(self.activeCategoryFilters.length ? visible : products.length);
		}

		var viewAll = dropdown.querySelector('.js-searchpro__dropdown-view-all');
		if (viewAll) {
			var allUrl = viewAll.getAttribute('data-url-all') || viewAll.href;
			var allLabel = viewAll.getAttribute('data-label-all') || 'Все результаты поиска';
			var total = dropdown.getAttribute('data-count') || '0';
			if (self.activeCategoryFilters.length === 1) {
				var only = self.activeCategoryFilters[0];
				viewAll.href = only.url || only.catalogUrl || allUrl;
				viewAll.innerHTML = 'Все в «' + escapeHtml(only.name || '') + '»'
					+ ' <span class="searchpro__dropdown-view-all-count">(' + visible + ')</span>';
			} else if (self.activeCategoryFilters.length > 1) {
				viewAll.href = allUrl;
				viewAll.innerHTML = 'Показать все результаты'
					+ ' <span class="searchpro__dropdown-view-all-count">(' + visible + ')</span>';
			} else {
				viewAll.href = allUrl;
				viewAll.innerHTML = escapeHtml(allLabel)
					+ ' <span class="searchpro__dropdown-view-all-count">(' + total + ')</span>';
			}
		}
	};

	SearchproField.prototype.toggleCategoryFilter = function(btn) {
		var id = btn.getAttribute('data-category-id') || '';
		if (!id) {
			return;
		}
		var filters = (this.activeCategoryFilters || []).slice();
		var idx = filters.findIndex(function(f) { return String(f.id) === String(id); });
		if (idx >= 0) {
			filters.splice(idx, 1);
		} else {
			filters.push({
				id: id,
				name: btn.getAttribute('data-category-name') || '',
				fullUrl: btn.getAttribute('data-category-full-url') || '',
				url: btn.getAttribute('data-category-url') || '',
				catalogUrl: btn.getAttribute('data-catalog-url') || ''
			});
		}
		this.applyCategoryFilters(filters);
	};

	SearchproField.prototype.handleDropdownUiClick = function(e) {
		if (e._searchproFilterHandled) {
			return true;
		}

		var cartBtn = e.target.closest('.js-searchpro__dropdown-cart');
		if (cartBtn) {
			e.preventDefault();
			e.stopPropagation();
			e._searchproFilterHandled = true;
			this.addProductToCart(cartBtn);
			return true;
		}

		var filterBtn = e.target.closest('.js-searchpro__dropdown-section-filter');
		if (filterBtn) {
			e.preventDefault();
			e.stopPropagation();
			e._searchproFilterHandled = true;
			this.toggleCategoryFilter(filterBtn);
			return true;
		}

		var chip = e.target.closest('.js-searchpro__dropdown-filter-chip');
		if (chip) {
			e.preventDefault();
			e.stopPropagation();
			e._searchproFilterHandled = true;
			var chipId = chip.getAttribute('data-category-id') || '';
			var filters = (this.activeCategoryFilters || []).filter(function(f) {
				return String(f.id) !== String(chipId);
			});
			this.applyCategoryFilters(filters);
			return true;
		}

		var reset = e.target.closest('.js-searchpro__dropdown-filter-reset');
		if (reset) {
			e.preventDefault();
			e.stopPropagation();
			e._searchproFilterHandled = true;
			this.applyCategoryFilters([]);
			return true;
		}

		return false;
	};

	SearchproField.prototype.updateHeaderCart = function(data) {
		if (!data) {
			return;
		}
		var totals = document.querySelectorAll('.cart-total');
		var counts = document.querySelectorAll('.cart-count');
		var i;
		for (i = 0; i < totals.length; i++) {
			var cart = totals[i].closest('#cart') || totals[i].closest('.cart');
			if (cart) {
				cart.classList.remove('empty');
			}
			if (typeof data.total !== 'undefined') {
				totals[i].innerHTML = data.total;
			}
		}
		for (i = 0; i < counts.length; i++) {
			if (typeof data.count !== 'undefined') {
				counts[i].setAttribute('data-count', String(data.count));
				counts[i].style.display = '';
			}
		}
	};

	SearchproField.prototype.openCartDialog = function(url) {
		if (!url) {
			return;
		}
		if (window.jQuery) {
			var $ = window.jQuery;
			var d = $('#dialog');
			var c = d.find('.cart');
			if (!d.length || !c.length) {
				window.location.href = url.replace(/([?&])cart=1/, '').replace(/\?$/, '') || url;
				return;
			}
			c.html('<i class="icon32 loading"></i>');
			d.show();
			$('body, #footer-pane').addClass('dialog-margin');
			c.load(url, function() {
				c.prepend('<a href="#" class="dialog-close"><i class="material-icons mi-2x">&#xE5CD;</i></a>');
			});
			return;
		}
		window.location.href = url;
	};

	SearchproField.prototype.addProductToCart = function(btn) {
		if (!btn || btn.getAttribute('data-busy') === '1') {
			return;
		}

		var dialogUrl = btn.getAttribute('data-cart-dialog-url') || '';
		if (dialogUrl) {
			this.openCartDialog(dialogUrl);
			return;
		}

		var productId = btn.getAttribute('data-product-id') || '';
		if (!productId) {
			return;
		}

		var cartUrl = (this.config && this.config.cart_add_url) || '/cart/add/';
		var skuId = btn.getAttribute('data-sku-id') || '';
		var body = 'html=1&product_id=' + encodeURIComponent(productId);
		if (skuId) {
			body += '&sku_id=' + encodeURIComponent(skuId);
		}

		btn.setAttribute('data-busy', '1');
		btn.classList.add('is-loading');

		var self = this;
		var finish = function(ok) {
			btn.removeAttribute('data-busy');
			btn.classList.remove('is-loading');
			if (ok) {
				btn.classList.add('is-added');
				setTimeout(function() {
					btn.classList.remove('is-added');
				}, 1200);
			}
		};

		if (window.jQuery) {
			var payload = { html: 1, product_id: productId };
			if (skuId) {
				payload.sku_id = skuId;
			}
			window.jQuery.post(cartUrl, payload, function(response) {
				if (response && response.status === 'ok') {
					self.updateHeaderCart(response.data || {});
					finish(true);
				} else {
					finish(false);
				}
			}, 'json').fail(function() {
				finish(false);
			});
			return;
		}

		fetch(cartUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: body,
			credentials: 'same-origin'
		}).then(function(r) {
			return r.json();
		}).then(function(response) {
			if (response && response.status === 'ok') {
				self.updateHeaderCart(response.data || {});
				finish(true);
			} else {
				finish(false);
			}
		}).catch(function() {
			finish(false);
		});
	};

	SearchproField.prototype.onEntityClick = function(e) {
		if (this.handleDropdownUiClick(e)) {
			return;
		}

		var deleteBtn = e.target.closest('.js-searchpro__dropdown-entity_delete-button');
		if (deleteBtn) {
			e.preventDefault();
			this.deleteHistoryItem(deleteBtn.closest('.js-searchpro__dropdown-entity'));
			return;
		}

		var entity = e.target.closest('.js-searchpro__dropdown-entity');
		if (!entity) {
			return;
		}
		var inDropdown = (this._resultsBox && this._resultsBox.contains(entity))
			|| (this._helperBox && this._helperBox.contains(entity))
			|| this.root.contains(entity);
		if (!inDropdown) {
			return;
		}

		var action = entity.getAttribute('data-action') || '';
		var parts = action.split(':');
		if (parts[0] === 'goto' && parts[1] === 'data-link') {
			e.preventDefault();
			window.location.href = entity.getAttribute('data-link') || entity.getAttribute('href');
			return;
		}
		if (parts[0] === 'value') {
			e.preventDefault();
			var value = entity.getAttribute('data-value') || entity.textContent.trim();
			var input = this.input();
			if (input) {
				input.value = value;
				this.handleInputValue(value);
			}
		}
	};

	SearchproField.prototype.onDocumentClick = function(e) {
		var container = this.container();
		var boxes = [this._resultsBox, this._helperBox].filter(Boolean);
		var inside = this.root.contains(e.target);
		boxes.forEach(function(box) {
			if (box.contains(e.target)) {
				inside = true;
			}
		});
		if (!inside && container && !container.contains(e.target)) {
			this.hideBoxes();
			this.closeCategoriesList();
		}
	};

	SearchproField.prototype.onKeyDown = function(e) {
		var code = e.keyCode;
		if (code === 13) {
			e.preventDefault();
			this.search();
		} else if (code === 27) {
			this.hideBoxes();
		}
	};

	SearchproField.prototype.onKeyUp = function(e) {
		if ([38, 40, 27, 13, 9].indexOf(e.keyCode) !== -1) {
			return;
		}
		var input = this.input();
		if (!input) {
			return;
		}
		this.handleInputValue(input.value.trim());
	};

	SearchproField.prototype.bindEvents = function() {
		var self = this;
		var input = this.input();
		if (!input) {
			return;
		}

		input.addEventListener('focus', function() {
			var container = self.container();
			if (container) {
				container.classList.add('js-searchpro__field-container--focus');
			}
			self.loadHelper().then(function() {
				if (!input.value.trim()) {
					self.showHelper();
				}
			});
		});
		input.addEventListener('blur', function() {
			var container = self.container();
			if (container) {
				container.classList.remove('js-searchpro__field-container--focus');
			}
		});
		input.addEventListener('keydown', function(e) { self.onKeyDown(e); });
		input.addEventListener('keyup', function(e) { self.onKeyUp(e); });

		var button = this.qs('.js-searchpro__field-button');
		if (button) {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				self.search();
			});
		}

		var clearBtn = this.qs('.js-searchpro__field-clear-button');
		if (clearBtn) {
			clearBtn.addEventListener('click', function(e) {
				e.preventDefault();
				input.value = '';
				clearBtn.style.display = 'none';
				self.abortSuggest();
				self.hideBoxes();
				input.focus();
			});
		}

		var selector = this.qs('.js-searchpro__field-category-selector');
		if (selector) {
			selector.addEventListener('click', function(e) {
				e.preventDefault();
				self.hideBoxes();
				self.loadCategories().then(function() {
					self.toggleCategoriesList(selector);
				});
			});
		}

		this.root.addEventListener('click', function(e) {
			var item = e.target.closest('.js-searchpro__field-category');
			if (item && self.root.contains(item)) {
				e.preventDefault();
				var catInput = self.qs('.js-searchpro__field-category-input');
				var label = self.qs('.js-searchpro__field-category-label');
				if (catInput) {
					catInput.value = item.getAttribute('data-id') || '0';
				}
				if (label) {
					label.textContent = item.getAttribute('title') || item.textContent.trim();
				}
				self.root.querySelectorAll('.js-searchpro__field-category').forEach(function(el) {
					el.classList.remove('selected');
				});
				item.classList.add('selected');
				self.closeCategoriesList();
				self.handleInputValue(input.value.trim());
				return;
			}
			self.onEntityClick(e);
		});

		document.addEventListener('click', function(e) {
			if (self.handleDropdownUiClick(e)) {
				return;
			}
			self.onDocumentClick(e);
		});
	};

	SearchproField.prototype.init = function() {
		var field = this.qs('.js-searchpro__field');
		if (field) {
			field.style.display = '';
		}
		if (!this.isEnabled('clear_button')) {
			var clearBtn = this.qs('.js-searchpro__field-clear-button');
			if (clearBtn) {
				clearBtn.parentNode.removeChild(clearBtn);
			}
		}
		if (this.isEnabled('history') || this.isEnabled('popular')) {
			this.helperBox(true);
		}
		this.bindEvents();
	};

	window.shop_searchpro_field_v2 = SearchproField;
	window.shop_searchpro_field = SearchproField;

	window.shop_searchpro_field_init = function(wrapperId, params) {
		return new SearchproField(wrapperId, params);
	};

})();
