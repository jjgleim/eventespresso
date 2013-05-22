jQuery(document).ready(function() {
	jQuery('#event_espresso_checkout_form div.multi-reg-page:not(:first)').css('display', 'none');
	jQuery('.copy-all-button-wrapper').parent().parent().css('display', 'none')
	jQuery('.event_questions input').change(function() {
		jQuery('.copy-all-button').click();
	});
});
