jQuery(document).ready(function() {
    jQuery('select.price_id:not(:first)').css('display', 'none');
    jQuery('select.price_id:first').on('change', function() {
    var selectedValue = jQuery(this).val();
		jQuery('select.price_id:not(:first)').val(selectedValue);
		timeoutID = window.setTimeout(function() {
			jQuery('select.price_id:last').trigger('change');
		}, 500);
    });
});
