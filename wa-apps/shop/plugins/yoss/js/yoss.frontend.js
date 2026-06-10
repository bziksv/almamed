/**
 * yoss.frontend.js
 * Module yossFrontend
 */

/*global $, yossFrontend */

var yossFrontend = (function () { 'use strict';
	//---------------- BEGIN MODULE SCOPE VARIABLES ---------------
	var
		getProductBlock, keyupTimeout, ajaxSendQuery, onSearchInputKeyup, searchAjaxStatus, onResultBlockScroll, onNonResultBlockClick, initModule;
	//----------------- END MODULE SCOPE VARIABLES ----------------

	//--------------------- BEGIN DOM METHODS ---------------------
	getProductBlock = function (product) {
		var									
			wrapperBlock, wrapperLeft, wrapperRight, productImg, productName, productBrands, productCategory, productPrice, productLink;

		wrapperBlock    = $('<div/>').addClass('yoss-result-wrapper');
		wrapperLeft     = $('<div/>').addClass('yoss-result-left');
		wrapperRight    = $('<div/>').addClass('yoss-result-right');

		productImg      = $('<a/>').attr('href', product.url).addClass('product-image').html(product.image);
		productName     = $('<a/>').attr('href', product.url).addClass('product-name').html(product.name);
		productBrands   = $('<div/>').addClass('product-brand');
		productCategory = $('<div/>').addClass('product-category').html(product.category);


		productPrice    = $('<div/>').addClass('product-price').html(product.price);
		productLink     = $('<a/>').addClass('product-link').attr('href', product.url).html('{_wp("to product")} &rarr;');
		if(+product.price.replace(/\D+/g,"") == 0) {
			productPrice = $('<div/>').addClass('buy-box-product').css('margin-left', '0%').attr('data-tooltip', 'Цена зависит от комплектации прибора и/или наличия на складах в России.При заказе данного продукта Вам перезвонит менеджер и уточнит все детали.В случаи если Вас не устроит текущая цена или комплектация, Вы всегда сможете отказаться от товара до момента оплаты.').html('<span>Цена по запросу</span>&nbsp;<i>?</i>');
		}
		if (product.brands.length > 0) {
			for(var b in product.brands) {
				productBrands = productBrands.append(product.brands[b].brand);
			}
		}

		wrapperLeft.append(productImg, productName, productBrands, productCategory);
		wrapperRight.append(productPrice, productLink);
		wrapperBlock.append(wrapperLeft, wrapperRight);

		return wrapperBlock
	};
	//--------------------- END DOM METHODS -----------------------

	//------------------- BEGIN EVENT HANDLERS --------------------
	ajaxSendQuery = function (t, resultBlock) {
		$.ajax({
			type: 'POST',
			url: '{$search_url}',
			data: 'query='+t.val()+'&page=1',
			success: function (result) {

				resultBlock.removeClass('loading');

				if (result.status === 'ok' && result.data !== false) {

					if (result.data.products.length > 0) {

						if (result.data.next_page !== false) {
							var nextPage = $('<input/>').attr('type', 'hidden').attr('id', 'next_page').val(result.data.next_page);
						} else {
							var nextPage = $('<input/>').attr('type', 'hidden').attr('id', 'next_page').val('0');
						}
						resultBlock.append(nextPage);

						var wrapperBlock = $('<div/>').addClass('yoss-result-wrapper');
						var productCountSpan = $('<span/>').addClass('yoss-result-product-count').html('{_wp("Products found")}: ' + result.data.product_count);
						var searchAllUrlLink = $('<a/>').addClass('yoss-result-show-all').attr('href', result.data.searh_all_url).html('{_wp("to results")}');

						wrapperBlock.append(productCountSpan, searchAllUrlLink);
						resultBlock.append(wrapperBlock);

						for(var key in result.data.products) {
							var productBlock = getProductBlock(result.data.products[key]);
							resultBlock.append(productBlock);
						}


					} else {

						resultBlock.addClass('no-products').html('{_wp("Sorry, but nothing was found, try to change your query")}');

					}

				} else {

					resultBlock.addClass('yoss-error').html('{_wp("Sorry, error accured")}');

				}
			}
		}, 'json');
	};

	onSearchInputKeyup = function (event) {
		var t = $(this);
		searchAjaxStatus = false;

		if ( t.val().length >= {$yoss_settings.min_char_count} ) {

			var inputOffset = t.offset();
			var inputHeight = t.outerHeight();
			var inputParentWidth = t.parent().outerWidth();

			var resultBlock = $('<div/>').addClass('yoss-result loading').css({
				'left':       inputOffset.left + 'px',
				'max-height': '{$yoss_settings.result_max_height}',
				'top':        (inputOffset.top + inputHeight) + 'px',
				'width':      '{$yoss_settings.result_width}'
			});

			if ($('.yoss-result').length > 0) {
				$('.yoss-result').remove();
			}





			{if $yoss_settings.result_width === 'auto'}
				resultBlock.css({ 'width': inputParentWidth + 'px' });
			{/if}

			t.addClass('active');
			$('body').prepend(resultBlock);

			if (keyupTimeout) {
				clearTimeout(keyupTimeout);
				keyupTimeout = null;
			}

			keyupTimeout = setTimeout (function() { ajaxSendQuery(t, resultBlock); }, 700);			
			
			{if $yoss_settings.lazy_loading === 'on'}
				$('.yoss-result').scroll(onResultBlockScroll);
			{/if}

		} else {

			t.removeClass('active');
	        $('.yoss-result').remove();
			return false;

		}	
	};

	onResultBlockScroll = function (event) {
		var resultBlock = $(this);

		if(resultBlock.scrollTop() + resultBlock.innerHeight() >= this.scrollHeight) {
			if (!searchAjaxStatus) {
				searchAjaxStatus = true;

				var query = $('{$yoss_settings.id_in_html}').val();
				var nextPage = resultBlock.find('#next_page').val();
				var loadingBlock = $('<div/>').addClass('yoss-result-wrapper loading');
				var lastEl = resultBlock.find('.yoss-result-wrapper:last-child');

				if (query.length > 0 && nextPage > 0 ) {

					lastEl.after(loadingBlock);

					$.ajax({		            	
						type: 'POST',
						url: '{$search_url}',
						data: 'query='+query+'&page='+nextPage,
						success: function (result) {
							searchAjaxStatus = false;

							$('.yoss-result-wrapper.loading').remove();

							if (result.status === 'ok' && result.data.products.length > 0) {							

								if (result.data.next_page !== false) {
									resultBlock.find('#next_page').val(result.data.next_page);
								} else {
									resultBlock.find('#next_page').val('0');
								}

								for(var key in result.data.products) {
									var productBlock = getProductBlock(result.data.products[key]);

									lastEl.after(productBlock);
								}	

							}
						}
					}, 'json');
				}
			}
	    }
	};

	onNonResultBlockClick = function (event) {
		var div = $(event.data);

		if (!div.is(event.target) && div.has(event.target).length === 0) {
			div.remove();
		}
	};
	//------------------- END EVENT HANDLERS ----------------------

	//------------------- BEGIN PUBLIC METHODS --------------------
	initModule = function () {
		$(document).on('keyup', '{$yoss_settings.id_in_html}', onSearchInputKeyup);

		$(document).mouseup('.yoss-result', onNonResultBlockClick);

		window.onscroll = function() {
			var menu_top = document.documentElement.scrollTop;
			$('.yoss-result').css('top',menu_top + 56 + 'px');
		};

	};

	return {
		initModule: initModule
	};
	//------------------- END PUBLIC METHODS ----------------------
}());