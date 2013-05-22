jQuery(document).ready(function() {
    jQuery('select.price_id').css('display', 'none');
    jQuery('select.price_id:first').css('display', 'inline-block');
    jQuery('select.price_id:first').change(function() {
        var selectedValue = jQuery(this).find(":selected").val();
        jQuery("option").removeAttr('selected');
        jQuery("option[value='"+selectedValue+"']").attr('selected', 'selected');
    });
});
    
