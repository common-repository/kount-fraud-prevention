(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */
	$( window ).load(function() {
	/** collapsible section */
	var coll = document.getElementsByClassName("collapsible");
	var i;

	for (i = 0; i < coll.length; i++) {
	coll[i].addEventListener("click", function() {
		this.classList.toggle("active");
		var content = this.nextElementSibling;
		if (content.style.display === "block") {
		content.style.display = "none";
		} else {
		content.style.display = "block";
		}
	});
	}

	/**Download log files */
	var page_url = 	$("#kount_page_url").val();
	var filename =	$("#logs_files").val();
	$("#download_btn").attr("href",page_url+"&download_file="+filename);

	/** logs file dropdown onchange */
	$("#logs_files").change(function(){
		page_url = 	$("#kount_page_url").val();
		filename =	$(this).val();
		$("#download_btn").attr("href",page_url+"&download_file="+filename);
	})
	
	/** Validation for admin configuration */
	var validation_error= false;
	$("#setting-notice").css("display","none");

	/**Calling onchange event on input field */
	$('input[type="text"]').change(function(e){
		var value=$(this).val();
		$(this).val(value);
	});

	/**Off all functionality if plugin is disabled */
	$('#is_plugin_enable').change(function(){
		disable_plugin();
	});

	/**Submit button click */
	$("#submit_btn").click(function(){
		disable_plugin();

		/**Getting the toggles value */
		var payment_website = $('#payment_website').val();
		var kount_merchant_id = $("#kount_merchant_id").val();
		var api_key = $("#api_key").val();
		var k360_api_key = $("#k360_api_key").val();
		var is_payment_enable_= $("#is_payment_enable").is(":checked");

		if(kount_merchant_id == ""){
			show_message("Merchant ID required.","block");
			validation_error= true;
		}
		else if(kount_merchant_id.length < 6){
			show_message("Invalid Merchant ID.","block");
			validation_error= true;
		}
		else if(api_key != "" && payment_website ==""){
			show_message("Website ID for payment is required when the Command Payment API Key is set.","block");
			validation_error= true;
		}
		else if(api_key == "" && k360_api_key == ""){
			show_message("Payment API key or Kount 360 API Key required.","block");
			validation_error= true;
		}
		else{
			validation_error= false;
		}

		if(is_payment_enable_ == true && validation_error== false){
			var order_cancellation_message = $("#order_cancellation_message").val();
			if(order_cancellation_message ==""){
				show_message("Order Cancellation Message required.","block");
				validation_error= true;
			}
			else{
				show_message("","none");
				validation_error= false;
			}
		}

		var days_number = Number($("#delete_logs_in").val());
		if(days_number != "" && validation_error == false){
			if (/^[0-9]{1,3}$/.test(days_number)) { 
				show_message("","none");
				validation_error= false;
			}
			else{
				show_message("Max 3 digits are allowed in logs deletion days.","block");
				validation_error= true;
			}
		}

		/**If validation error is false than save the form data */
		if(validation_error == false){
			$( "#submit" ).trigger( "click" );
		}
	});

	/**Regenerate keys */
	$("#regenerate_btn").click(function(){
		$("#regenerate_keys").val("true");
		show_message("Consumer key and consumer secret key are re-generated. Please click save changes for save keys.","block","regenerate");
	});

	/**Get the model */
	var kount_modal = document.getElementById("kountModal");
	// When the user clicks on the button, open the modal
	$(".kount_doc_btn").click(function() {
		kount_modal.style.display = "block";
	});

	// When the user clicks on <span> (x), close the modal
	$(document).on('click',".close_kount_modal",function() {
		kount_modal.style.display = "none";
	});

	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) {
		if (event.target == kount_modal) {
			kount_modal.style.display = "none";
		}
	}

	/**Notice close button action */
	$(document).on('click', '#setting-notice .notice-dismiss', function(){ 
		$("#setting-notice").remove();
	});
});

/**Disable plugin*/
function disable_plugin(){
	var is_plugin_enable1 = $('#is_plugin_enable').is(":checked");
	if(is_plugin_enable1 == false){
		 $('#is_payment_enable').prop("checked", false);
	}
}

/**show error message*/
function show_message(msg, display_error, regenerate){
	if(regenerate == "regenerate"){
		if($("#setting-notice").length == 0) {
				$("div#notice_block").append('<div id="setting-notice" class="notice is-dismissible notice-success" style="display: block;"><p>'+msg+'</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
		} else {
			$("#setting-notice").removeClass("notice-error");
			$("#setting-notice").addClass("notice-success");
		}
	} else if(display_error == "block") {
		if($("#setting-notice").length == 0) {
			$("div#notice_block").append('<div id="setting-notice" class="notice is-dismissible notice-error" style="display: block;"><p>'+msg+'</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
		} else {
			$("#setting-error-settings_updated").css("display",'none');
			$("#setting-notice").addClass("notice-error");
			$("#setting-notice").removeClass("notice-success");
		}
	} else {
		$("#setting-notice").removeClass("notice-error");
	}
	$("#setting-notice").find("p").text(msg);
	$("#setting-notice").css("display",display_error);
	/***IF error occur then focus on the error */
	$("html, body").animate({ scrollTop: 0 }, "slow");
}

})( jQuery );