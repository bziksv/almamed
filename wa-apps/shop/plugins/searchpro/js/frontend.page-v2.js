(function() {
	'use strict';

	if (window.shop_searchpro_page_v2) {
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

	function SearchproPage(pageId) {
		this.root = document.getElementById(pageId);
		if (!this.root) {
			return;
		}
		this.init();
	}

	SearchproPage.prototype.init = function() {
		this.initMobileSort();
		this.initCategoryCarousel();
	};

	SearchproPage.prototype.initMobileSort = function() {
		var select = this.root.querySelector('.js-searchpro__page-mobile-sort-select');
		if (!select) {
			return;
		}
		select.addEventListener('change', function() {
			var value = select.value;
			var params = parseQuery();
			delete params.sort;
			delete params.order;
			var tail = Object.keys(params).map(function(key) {
				return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
			}).join('&');
			var href = '?' + value + (tail ? '&' + tail : '');
			window.location.href = href;
		});
	};

	SearchproPage.prototype.initCategoryCarousel = function() {
		var swiperRoot = this.root.querySelector('.js-searchpro__swiper');
		if (!swiperRoot) {
			return;
		}
		var wrapper = this.root.querySelector('.js-searchpro__swiper-wrapper');
		var nextBtn = this.root.querySelector('.js-searchpro__swiper-next-button');
		var prevBtn = this.root.querySelector('.js-searchpro__swiper-prev-button');
		if (!wrapper || !nextBtn || !prevBtn) {
			return;
		}
		wrapper.style.overflowX = 'auto';
		wrapper.style.scrollBehavior = 'smooth';
		var step = function() {
			return Math.max(200, wrapper.clientWidth * 0.75);
		};
		nextBtn.addEventListener('click', function(e) {
			e.preventDefault();
			wrapper.scrollLeft += step();
		});
		prevBtn.addEventListener('click', function(e) {
			e.preventDefault();
			wrapper.scrollLeft -= step();
		});
	};

	window.shop_searchpro_page_v2 = SearchproPage;
	window.shop_searchpro_page = SearchproPage;

})();
