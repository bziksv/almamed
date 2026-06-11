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

	var renderSuggest = function(data) {
		var root = document.createElement('div');
		root.className = 'searchpro__dropdown';

		(data.groups || []).forEach(function(group) {
			var groupEl = document.createElement('div');
			groupEl.className = 'searchpro__dropdown-group searchpro__dropdown-group-' + group.id;

			var titleEl = document.createElement('div');
			titleEl.className = 'searchpro__dropdown-group-title';
			titleEl.textContent = group.title || '';
			groupEl.appendChild(titleEl);

			var entitiesEl = document.createElement('div');
			entitiesEl.className = 'searchpro__dropdown-group-entities js-searchpro__dropdown-entities';

			(group.entities || []).forEach(function(entity) {
				var tag = entity.action === 'value:data-value' ? 'div' : 'a';
				var item = document.createElement(tag);
				item.className = 'searchpro__dropdown-entity js-searchpro__dropdown-entity';
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

				if (entity.image) {
					var imgWrap = document.createElement('div');
					imgWrap.className = 'searchpro__dropdown-entity_image-container';
					var img = document.createElement('img');
					img.className = 'searchpro__dropdown-entity_image';
					img.src = entity.image;
					img.alt = '';
					imgWrap.appendChild(img);
					item.appendChild(imgWrap);
				}

				var nameEl = document.createElement('div');
				nameEl.className = 'searchpro__dropdown-entity_name';
				nameEl.innerHTML = highlightName(entity.name, data.query);
				if (entity.subname) {
					var sub = document.createElement('span');
					sub.className = 'searchpro__dropdown-entity_subname';
					sub.textContent = entity.subname;
					nameEl.appendChild(sub);
				}
				item.appendChild(nameEl);

				if (entity.type === 'products') {
					var priceWrap = document.createElement('div');
					priceWrap.className = 'searchpro__dropdown-entity_price-container';
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
						item.appendChild(priceWrap);
					}
				}

				entitiesEl.appendChild(item);
			});

			groupEl.appendChild(entitiesEl);
			root.appendChild(groupEl);
		});

		if (data.results_url) {
			var viewAll = document.createElement('div');
			viewAll.className = 'searchpro__dropdown-view-all';
			var viewAllLink = document.createElement('a');
			viewAllLink.className = 'searchpro__dropdown-view-all-link js-searchpro__dropdown-entity';
			viewAllLink.setAttribute('data-action', 'goto:href');
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
		box.style.width = this.getContainerWidth() + 'px';
		box.style.display = '';
		document.dispatchEvent(new CustomEvent('shop_searchpro.box_shown'));
	};

	SearchproField.prototype.hideBoxes = function() {
		[this._resultsBox, this._helperBox].forEach(function(box) {
			if (box) {
				box.style.display = 'none';
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
					box.innerHTML = '';
					box.style.display = 'none';
					return;
				}
				var html = renderSuggest(data);
				self.cache[key] = html;
				box.innerHTML = html;
				self.showBox(box);
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

	SearchproField.prototype.onEntityClick = function(e) {
		var deleteBtn = e.target.closest('.js-searchpro__dropdown-entity_delete-button');
		if (deleteBtn) {
			e.preventDefault();
			this.deleteHistoryItem(deleteBtn.closest('.js-searchpro__dropdown-entity'));
			return;
		}

		var entity = e.target.closest('.js-searchpro__dropdown-entity');
		if (!entity || !this.root.contains(entity)) {
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

		document.addEventListener('click', function(e) { self.onDocumentClick(e); });
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
