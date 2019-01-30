jQuery(function($) {
    
		$( document ).on( "click", ".seed-pay-button", function() {
		
			
			
			jQuery.post(seedpay_params.ajax_url, {'action':'ajax_seedpay_submit_request','phone':$("#seedpay_payment_phone").val()}, function(response) {
			console.log("Submitting Request to SeedPay");
			var obj = $.parseJSON(response);
			console.log(obj);
			
			
			});
			
			
			
			
			
			
			return false;
		});
});