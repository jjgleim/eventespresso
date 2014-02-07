<?php
// This is for the gateway display
add_action('action_hook_espresso_display_offsite_payment_header', 'espresso_display_offsite_payment_header');
add_action('action_hook_espresso_display_offsite_payment_footer', 'espresso_display_offsite_payment_footer');
event_espresso_require_gateway("authnet/authnet_vars.php");

//This is for the return from authnet's servers
if (!empty($_REQUEST['type']) && $_REQUEST['type'] == 'authnet') {
	event_espresso_require_gateway("authnet/authnet_ipn.php");
	add_filter('filter_hook_espresso_transactions_get_attendee_id', 'espresso_transactions_authnet_get_attendee_id');
	add_filter('filter_hook_espresso_thank_you_get_payment_data', 'espresso_process_authnet');
}

function espresso_add_get_hash_js()
{
    if ( file_exists( EVENT_ESPRESSO_GATEWAY_DIR . 'authnet/get_hash.js' )) {
        $location = EVENT_ESPRESSO_GATEWAY_URL . 'authnet/get_hash.js';
    } else {
        $location = EVENT_ESPRESSO_PLUGINFULLURL . 'gateways/authnet/get_hash.js';
    }
    wp_enqueue_script( 'my_get_hash_js', $location, 'jquery', true);
    wp_localize_script( 'my_get_hash_js', 'my_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

}
add_action('template_redirect', 'espresso_add_get_hash_js');


function get_my_hash()
{
	include_once ('Authorize.php');
	$authnet_settings = get_option('event_espresso_authnet_settings');
	$authnet_login_id = empty($authnet_settings['authnet_login_id']) ? '' : $authnet_settings['authnet_login_id'];
	$authnet_transaction_key = empty($authnet_settings['authnet_transaction_key']) ? '' : $authnet_settings['authnet_transaction_key'];
	$myAuthorize = new Espresso_Authorize();
	$myAuthorize->setUserInfo($authnet_login_id, $authnet_transaction_key);
	$fingerprint = $myAuthorize->generate_hash($_GET['x_Invoice_num'], $_GET['x_fp_timestamp'], $_GET['x_Amount']);
    echo $fingerprint;
    die();
}

add_action("wp_ajax_nopriv_get_my_hash", "get_my_hash");
add_action("wp_ajax_get_my_hash", "get_my_hash");