jQuery(function ($) {
	$('.wait-plugin-top .close').on('click', function() {
		console.log('close');
		$.ajax({
			url: waitGlobalFrontend + "waitclosetop/", 
			type: "POST", 
			async: false,
			success: function(data1) {
				$('.wait-plugin-top').remove();
			}
		});		
	});
});
