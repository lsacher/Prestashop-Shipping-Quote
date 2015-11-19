/*
*  NOTICE OF LICENSE
*
*  This source file is subject to the Academic Free License (AFL 3.0)
*  that is bundled with this package in the file LICENSE.txt.
*  It is also available through the world-wide-web at this URL:
*  http://opensource.org/licenses/afl-3.0.php
*
*  This program is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of 
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.   
*/

$(document).ready(function(){
  $('#carrier_form p:last').after('<p style="display:none;"><label for="shipping_quote_price">'+shipping_price+'</label><input class="ac_input" type="text" name="shipping_quote_price" id="shipping_quote_price"><br /><a href="#" class="btn btn-default button" id="shipping_quote_price_set"><i class="icon-play"></i> <span>'+update_shipping_price+'</span> </a></p><p style="display:none;" class="shipping_quote_admin"><span id="shipping_quote_msg"></span><br /><input type="checkbox" id="override_cart" name="override_cart" value="yes">&nbsp;&nbsp;<span id="override_cart_msg"></span></p>');

	var display_shipping_price_field = function(e) {
		if ($('#delivery_option').val() == shippingquote_carrier_id + ',') {
			$('#shipping_price').parent('p').hide();
			$('#shipping_quote_price').val($('#shipping_price').text().replace("$",""));
			$('#shipping_quote_price').parent('p').show();
			$('#free_shipping').parent('p').hide();
		} else {
			$('#shipping_price').parent('p').show();
			$('#shipping_quote_price').parent('p').hide();
			$('#free_shipping').parent('p').show();
		}
	};

	$('#delivery_option').bind('change', display_shipping_price_field);

	setTimeout(function() {
		if ($('#carriers_part:visible').length)
			return display_shipping_price_field();
		setTimeout(arguments.callee, 300)
	}, 300);
  
	$('#shipping_quote_price_set').bind('click', function(e) {
		e.preventDefault();
		$.ajax({
			type: 'POST',
			url: shippingquote_ajax_url,
			dataType: 'json',
			data: {
				'ajax': true,
				'token': shippingquote_token,
				'id_cart': id_cart,
				'id_customer': id_customer,
				'value': $('#shipping_quote_price').val(),
        'override_cart': $('#override_cart:checked').val(),
			},
			success: function(data) {
        if (data.sq_cart_changed == 1) {
          $('#shipping_quote_msg').text(data.message1);
          $('#override_cart_msg').text(data.message2);
          $('#shipping_quote_msg').parent('p').slideDown();
        } else  $('#shipping_quote_msg').parent('p').slideUp();
				updateDeliveryOption();
			},
		});
		return false;
	});
});
