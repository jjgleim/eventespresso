function my_js_function() 
{
     jQuery.ajax({
        url: my_ajax_script.ajaxurl,
        data: ({
         action : 'get_my_hash',
         x_Login: jQuery( "[name='x_Login']" ).val(),
         x_Invoice_num: jQuery( "[name='x_Invoice_num']" ).val(),
         x_fp_timestamp: jQuery( "[name='x_fp_timestamp']" ).val(),
         x_Amount: jQuery( "[name='x_Amount']" ).val()
        }),
        success: function(data, textStatus, jqXHR) {
            jQuery( "[name='x_fp_hash']" ).val(data);
        }
     });
}
jQuery( document ).ready( function() {
    jQuery( "#amount_1" ).keyup(my_js_function);
});