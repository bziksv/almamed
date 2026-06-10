if (!window.shop_searchpro) {
	window.shop_searchpro = {
		plugin_url: '{$plugin_url}',
		dropdown_url: '{$dropdown_url}',
		results_url: '{$results_url}',
		version: '{$version}',
		loader: {
			assets: {},
			is_loading: {},
			loadJs: function(url, asset, callback) {
				var resolve = function() {
					if (typeof callback === 'function' && window.shop_searchpro.loader.assets[asset]) {
						callback.call(window.shop_searchpro.loader.assets[asset]);
					}
				};

				if (window.shop_searchpro.loader.is_loading[asset]) {
					$(document).on('shop_searchpro_asset_loaded_' + asset, resolve);
					return false;
				}

				if (
					Object.keys(this.assets).indexOf(asset) === -1 &&
					!window.shop_searchpro.loader.is_loading[asset]
				) {
					window.shop_searchpro.loader.is_loading[asset] = true;

					$.ajax({
						dataType: 'script',
						cache: true,
						url: url,
						complete: function() {
							window.shop_searchpro.loader.is_loading[asset] = false;
							window.shop_searchpro.loader.assets[asset] = true;
							$(document).trigger('shop_searchpro_asset_loaded_' + asset);
							resolve();
						}
					});
				} else {
					resolve();
				}
			},
			loadCss: function(url) {
				$('<link>')
					.appendTo('head')
					.attr({
						type: 'text/css',
						rel: 'stylesheet',
						href: url
					});
			}
		}
	};
}
