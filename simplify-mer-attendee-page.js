// There are two different approaches represented here. The the hiding of the copy-all button
// and the additional attendee forms is the same, but the copying is different. You can either leverage the existing
// code on the copy-all button and simply add this code:
jQuery(document).ready(function() {
	jQuery('#event_espresso_checkout_form div.multi-reg-page:not(:first)').css('display', 'none');
	jQuery('.copy-all-button-wrapper').parent().parent().css('display', 'none');

	jQuery('.event_questions input').change(function() {
		jQuery('.copy-all-button').click();
	});
});

// Or you can alter the copy-all button code. Add this code:
jQuery(document).ready(function() {
	jQuery('#event_espresso_checkout_form div.multi-reg-page:not(:first)').css('display', 'none');
	jQuery('.copy-all-button-wrapper').parent().parent().css('display', 'none');
});

// and then replace the section in espresso_cart_functions.js for the copy-all-button with this:

jQuery('.event_questions input').on('change', function() {

	var from_attendee_no = '1';
	//console.log(jQuery(this).attr('id') + ' > ' + jQuery(this).val());
	var id = jQuery(this).attr('name').split('[');
	var from_event_id = id[1].substring(0, id[1].length - 1);
	var from_price_id = id[2].substring(0, id[2].length - 1);
	jQuery('.multi_regis_wrapper_attendee-additional .event_form_field :input').each(function() {

		var val = jQuery(this).val();
		var name = jQuery(this).attr('name');
		var input_type = jQuery(this).attr('type');

		var copy_field_name = name.replace(/(\[\d+\])(\[\d+\])(\[\d+\])/, "[" + from_event_id + "][" + from_price_id + "][" + from_attendee_no + "]");
		//console.log(copy_field_name);

		var copy_from = jQuery(":input[name='" + copy_field_name + "']");
		//console.log(jQuery(this).attr('name') + ' > ' + copy_field_name + ' > ' + copy_from.val());

		switch (input_type) {

			case 'text':
			case 'textarea':
				jQuery(this).val(copy_from.val());
				break;

			case 'radio':
			case 'checkbox':
				if (copy_from.attr('checked') && val == copy_from.val())
					jQuery(this).attr("checked", "checked");
				break;

			default:
				jQuery(this).val(copy_from.val());

		}

	});

});
