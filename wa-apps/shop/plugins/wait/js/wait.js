function isEmail(email) {
	var regex = /^(.)+\@((.)+\.)+(.{2,6})+$/;
	return regex.test(email);
}

/* Generate coupon code on click */
function autocouponClick(type) {
    if (typeof wait_event_coupon == 'function') {
        wait_event_coupon();
    }

	/* только для "Автогенерация купонов" */
	if (!$('.wait-plugin .autocoupon').hasClass('disable')) {
		var error = true;
		
		$.ajax({
			url: waitGlobalFrontend + "waitautocoupon/",
			type: "POST",
			data: {'type':type},
			async: false,
			success: function(data1) {
				if (data1.data.result.is == 'success') {
					var code = data1.data.result.code;
					if (code) {
						$('.wait-plugin .autocoupon').addClass('disable').attr('disable', 'disable');
						$('.wait-plugin .autocoupon-value').val(code);
						$('.wait-plugin .wait-success').html(data1.data.result_text);
						error = false;
					} 
				}				
			}
		});
		
		if (error) {
			$('.wait-plugin .wait-error').html('При генерации купона возникла ошибка.');
		}
	}
};

/* Send email on click */
function sendEmailClick() {
    if (typeof wait_event_mail == 'function') {
        wait_event_mail();
    }

	/* ввода имени/e-mail/телефона */
	if (!$('.wait-plugin input[type="button"]').hasClass('disable')) {
		var send = 1;
		
		var inputName = $('input.wait-name-value.wait-required');
		if ($(inputName).length && !$(inputName).val()) {
			send = 0;
			$(inputName).addClass('wait-input-error');
		} else {
			$(inputName).removeClass('wait-input-error');
		}
		
		var inputEmail = $('input.wait-email-value.wait-required');
		if ($(inputEmail).length && !isEmail($(inputEmail).val())) {
			send = 0;
			$(inputEmail).addClass('wait-input-error');
		} else {
			$(inputEmail).removeClass('wait-input-error');
		}
		
		var inputPhone = $('input.wait-phone-value.wait-required');
		if ($(inputPhone).length && !$(inputPhone).inputmask("isComplete")) {
			send = 0;
			$(inputPhone).addClass('wait-input-error');
		} else {
			$(inputPhone).removeClass('wait-input-error');
		}
		
		var inputPdn = $('input.wait-pdn.wait-required');
		if ($(inputPdn).length && !$(inputPdn).prop('checked')) {
			send = 0;
			$(inputPdn).next().addClass('wait-red');
		} else {
			$(inputPdn).next().removeClass('wait-red');
		}
		
		if (send == 1) {
		    if (typeof wait_event_mail_success == 'function') {
		        wait_event_mail_success();
		    }

			var name = $('input.wait-name-value').val();
			var email = $('input.wait-email-value').val();
			var phone = $('input.wait-phone-value').val();
			
			var carts_url = $('.wait-plugin input[name="carts_url"]').val();
			var mailer_form_id = $('.wait-plugin input[name="mailer_form_id"]').val();
			var subscribe_url = $('.wait-plugin input[name="subscribe_url"]').val();
			
			$.ajax({
				url: waitGlobalFrontend + "waitsendemail/",
				type: "POST",
				data: {'name':name, 'email':email, 'phone':phone, 'urlReferer':window.location.href},
				async: false,
				success: function(data1) {
					$('.wait-plugin input[type="button"]').addClass('disable').attr('disable', 'disable');
					$('.wait-plugin .wait-success').html(data1.data.result_text);
				}
			});	

			if (email && isEmail(email) && mailer_form_id > 0 && subscribe_url) {
				$.post(subscribe_url, {
					'subscriber[name]' : name,
					'subscriber[email]' : email,
					'form_id' : mailer_form_id
				});
			}

			if (carts_url) {
				$.post(carts_url, {
					'customer' : {
						'name' : name,
						'phone' : phone, 
						'email' : email
					}
				});
			}
		}
	}
}

/* After modal show */
function modalShowXV() {
    if (typeof wait_event_form_show == 'function') {
        wait_event_form_show();
    }

	/* для всех типов */
	$.ajax({
		url: waitGlobalFrontend + 'waitsetcookie/',
		type: 'POST', 
		data: {'urlReferer':window.location.href},
		async: false,
		success: function(data1) {
			if (data1.data.result.show == 1) {
				$('.wait-wrap').css('visibility', 'visible');
				alert_flag = false;
				showCoupon();
			}
		}
	});
}

function showCoupon() {
	/* только для "Свой код купона" */
	if ($('.wait-plugin .autocoupon-value').hasClass('coupon-value')) {
		var error = true;
		var code = $('.wait-plugin .coupon-value').val();
		
		$.ajax({
			url: waitGlobalFrontend + "waitcoupon/",
			type: "POST",
			data: {'code':code},
			async: false,
			success: function(data1) {
				if (data1.data.result.is == 'success') {
					$('.wait-plugin .wait-success').html(data1.data.result.result_text);
					error = false;
				}				
			}
		});
		
		if (error) {
			$('.wait-plugin .wait-error').html('При получении купона возникла ошибка.');
		}
	}
}

jQuery(function ($) {
	$('.wait-wrap').appendTo('body');

	if ($('.wait-modal').length > 1) {
		console.log('WAIT DEBUG: найдено более одной формы плагина ".wait-modal"');
	}

	// Close
    $('.wait-modal__close').click(function(){
        $('.wait-wrap').hide();
    });

	// Auto select
	$('.wait-plugin .autocoupon-value').on('click', function() {
		$(this).select();
	});
	
	// Mask
	$('.wait-plugin .wait-phone-value').inputmask("+7(9{3})9{3}-9{2}-9{2}");
	//$('.wait-plugin .email-value').inputmask('Regex', { regex: "[a-zA-Z0-9._%-]+@[a-zA-Z0-9-]+\.[a-zA-Z]{2,4}" });

	$('input.wait-name-value.wait-required').blur(function() {
		if (!$(this).val()) {
			$(this).addClass('wait-input-error');
		} else {
			$(this).removeClass('wait-input-error');
		}
	});

	$('input.wait-email-value.wait-required').blur(function() {
		if (!isEmail($(this).val())) {
			$(this).addClass('wait-input-error');
		} else {
			$(this).removeClass('wait-input-error');
		}
	});

	$('input.wait-phone-value.wait-required').blur(function() {
		if (!$(this).inputmask("isComplete")) {
			$(this).addClass('wait-input-error');
		} else {
			$(this).removeClass('wait-input-error');
		}
	});

	$('input.wait-pdn.wait-required').on('click', function() {
		if (!$(this).prop('checked')) {
			$(this).next().addClass('wait-red');
		} else {
			$(this).next().removeClass('wait-red');
		}
	});

	// Auto load dialog
	/*
	var _clientY = 0;
	var _date = 0;
	var _speed = 0;
	var alert_flag = true; 

	$('body').mousemove(function(e) {
		if (_clientY != 0 && e.clientY < _clientY) {
			_speed = Math.round((_clientY - e.clientY)/(new Date() - _date));
			if (e.clientY < 30 && _speed > 0.1) {
				if (alert_flag) {
					$('#wait-plugin').modal();
					alert_flag = false;
					var url = $('.wait-plugin .wa-url').val();
					$.ajax({url: url + "waitsetcookie", type: "POST", async: false});
				}
			}
		}
		_clientY = e.clientY;
		_date = new Date();
	});
	*/

});
