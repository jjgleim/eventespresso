<?php

if (!function_exists('espresso_ical')) {

	function espresso_ical() {
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		$name = $_REQUEST['event_summary'] . ".ics";
		$output = "BEGIN:VCALENDAR\n" .
						"VERSION:2.0\n" .
						"PRODID:-//" . $_REQUEST['organization'] . " - Event Espresso Version " . espresso_version() . "//NONSGML v1.0//EN\n" .
							"METHOD:PUBLISH\n" .
							//"X-WR-CALNAME:" . $_REQUEST['organization'] . "\n" . //Publishes a new calendar in some systems.
							"X-ORIGINAL-URL:" . $_REQUEST['eereg_url'] . "\n" .
							"X-WR-CALDESC:" . $_REQUEST['organization'] . "\n" .
							"X-WR-TIMEZONE:" . get_option('timezone_string') . "\n";
		if (!empty($_REQUEST['all_events'])) {
			global $wpdb, $org_options;
			$sql = "SELECT e.*, ese.start_time, ese.end_time ";
			if (isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y') {
				$sql .= ", v.name venue_name, v.address venue_address, v.address2 venue_address2, v.city venue_city, v.state venue_state, v.zip venue_zip, v.country venue_country, v.meta venue_meta ";
			}
			$sql .= " FROM " . EVENTS_DETAIL_TABLE . " e ";
			$sql .= " LEFT JOIN " . EVENTS_START_END_TABLE . " ese ON ese.event_id = e.id ";
			if (isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y') {
				$sql .= " LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = e.id LEFT JOIN " . EVENTS_VENUE_TABLE . " v ON v.id = r.venue_id ";
			}
			$events = $wpdb->get_results($sql, ARRAY_A);
			foreach ($events as $event) {
				if (isset($org_options['use_venue_manager']) && $org_options['use_venue_manager'] == 'Y') {
					$event_address = $event['venue_address'];
					$event_address2 = $event['venue_address2'];
					$event_city = $event['venue_city'];
					$event_state = $event['venue_state'];
					$event_zip = $event['venue_zip'];
					$event_country = $event['venue_country'];
				} else {
					$event_address = $event['address'];
					$event_address2 = $event['address2'];
					$event_city = $event['city'];
					$event_state = $event['state'];
					$event_zip = $event['zip'];
					$event_country = $event['country'];
				}
				$location = (!empty($event_address) ? $event_address : '') . (!empty($event_address2) ? '<br />' . $event_address2 : '') . (!empty($event_city) ? '<br />' . $event_city : '') . (!empty($event_state) ? ', ' . $event_state : '') . (!empty($event_zip) ? '<br />' . $event_zip : '') . (!empty($event_country) ? '<br />' . $event_country : '');
				$start_date = strtotime($event['start_date'] . ' ' . $event['start_time']);
				$end_date = strtotime($event['end_date'] . ' ' . $event['end_time']);
				$output .= "BEGIN:VEVENT\n" .
								"DTSTAMP:" . date('Y') . date('m') . date('d') . "T" . date('His') . "\n" .
								"UID:" . $_SESSION['espresso_session']['id'] . $event['id'] . "@" . site_url() . "\n" .
								"ORGANIZER:MAILTO:" . $org_options['contact_email'] . "\n" .
								"DTSTART:" . date('Y', $start_date) . date('m', $start_date) . date('d', $start_date) . "T" . date('His', $start_date) . "\n" .
								"DTEND:" . date('Y', $end_date) . date('m', $end_date) . date('d', $end_date) . "T" . date('His', $end_date) . "\n" .
								"STATUS:CONFIRMED\n" .
								"URL:" . site_url() . "\n" .
								"SUMMARY:" . stripslashes($event['event_name']) . "\n" .
								//"DESCRIPTION:" . $_REQUEST['event_description'] . "\n" .
								"LOCATION:" . $location . "\n" .
								"END:VEVENT\n";
			}
		} else {
			$output .= "BEGIN:VEVENT\n" .
							"DTSTAMP:" . $_REQUEST['currentyear'] . $_REQUEST['currentmonth'] . $_REQUEST['currentday'] . "T" . $_REQUEST['currenttime'] . "\n" .
							"UID:" . $_REQUEST['registration_id'] . "@" . $_REQUEST['eereg_url'] . "\n" .
							"ORGANIZER:MAILTO:" . $_REQUEST['contact_email'] . "\n" .
							"DTSTART:" . $_REQUEST['startyear'] . $_REQUEST['startmonth'] . $_REQUEST['startday'] . "T" . $_REQUEST['starttime'] . "\n" .
							"DTEND:" . $_REQUEST['endyear'] . $_REQUEST['endmonth'] . $_REQUEST['endday'] . "T" . $_REQUEST['endtime'] . "\n" .
							"STATUS:CONFIRMED\n" .
							"URL:" . $_REQUEST['eereg_url'] . "\n" .
							"SUMMARY:" . $_REQUEST['event_summary'] . "\n" .
							//"DESCRIPTION:" . $_REQUEST['event_description'] . "\n" .
							"LOCATION:" . $_REQUEST['location'] . "\n" .
							"END:VEVENT\n";
		}
		$output .= "END:VCALENDAR\n";
		if (ob_get_length() || headers_sent()) {
			echo('Some data has already been output, can\'t send iCal file');
		}
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Length: ' . strlen($output));
		header('Content-Disposition: inline; filename="' . $name . '"');
		echo $output;
		die();
	}

}


/*
  Displays a link to download an .ics (iCal) file.

  Example usage in a template file:
  echo apply_filters('filter_hook_espresso_display_ical', $all_meta);
  (Note: the $all_meta variable (array) is populated in the event_list.php and registration_page.php files.)

  Advanced usage using the title and image parameter:
  echo apply_filters('filter_hook_espresso_display_ical', $all_meta, __('iCal Import', 'event_espresso'), '<img alt="'.__('iCal Import', 'event_espresso').'" src="'.EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/calendar_link.png">');

  Parameters:
  meta - the generated meta from an event template file
  title - the text to display in the title tag attribute of the link
  image - adds html to display an image (or text)
  link_only - ignores the image parameter and displays the title instead
 */
if (!function_exists('espresso_ical_prepare_by_meta')) {

	function espresso_ical_prepare_by_meta($meta, $title = '', $image = '', $link_only = FALSE) {
		global $org_options, $wpdb;
		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
		if (!empty($org_options['display_ical_download']) && $org_options['display_ical_download'] == 'N' || !isset($org_options['display_ical_download'])) {
			return;
		}

		$start_date = strtotime($meta['start_date'] . ' ' . $meta['start_time']);
		$end_date = strtotime($meta['end_date'] . ' ' . $meta['end_time']);
		$title = empty($text) ? __('iCal Import', 'event_espresso') : $title;
		$image = empty($image) ? '<img src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/calendar_link.png">' : $image;
		if ($link_only == TRUE) {
			$image = $title;
		}
		$array = array(
				'iCal' => 'true',
				'currentyear' => date('Y'),
				'currentmonth' => date('m'),
				'currentday' => date('d'),
				'currenttime' => date('His'),
				'event_id' => $meta['event_id'],
				'registration_id' => !empty($meta['registration_id']) ? $meta['registration_id'] : $_SESSION['espresso_session']['id'],
				'contact_email' => $meta['contact_email'],
				'startyear' => date('Y', $start_date),
				'startmonth' => date('m', $start_date),
				'startday' => date('d', $start_date),
				'starttime' => date('His', $start_date),
				'endyear' => date('Y', $end_date),
				'endmonth' => date('m', $end_date),
				'endday' => date('d', $end_date),
				'endtime' => date('His', $end_date),
				'event_summary' => urlencode(stripslashes($meta['event_name'])),
				//'event_description' => espresso_format_content(stripslashes($meta['event_desc'])),
				'eereg_url' => urlencode(espresso_reg_url($meta['event_id'])),
				'site_url' => urlencode(site_url()),
				'organization' => urlencode($org_options['organization']),
				'location' => str_replace(array('<br>', '<br />'), ' ', $meta['location']),
		);
		$url = add_query_arg($array, site_url());
		$html = '<a  href="' . wp_kses($url, '') . '" id="espresso_ical_' . $meta['event_id'] . '" class="espresso_ical_link" title="' . $title . '">' . $image . '</a>';
		return $html;
	}

}
add_filter('filter_hook_espresso_display_ical', 'espresso_ical_prepare_by_meta', 100, 4);

function espresso_ical_export_all($atts) {
	global $org_options;
	$array = array(
			'iCal' => 'true',
			'all_events' => 'true',
			'event_summary' => 'all_events',
			'organization' => urlencode($org_options['organization']),
			'eereg_url' => urlencode(site_url())
	);
	$url = add_query_arg($array, site_url());
	$html = '<a  href="' . wp_kses($url, '') . '" id="espresso_ical_all" class="espresso_ical_link" title="' . __('iCal Import', 'event_espresso') . '">' . '<img src="' . EVENT_ESPRESSO_PLUGINFULLURL . 'images/icons/calendar_link.png">' . '</a>';
	return $html;
}

add_shortcode('ALL_EVENTS_ICAL', 'espresso_ical_export_all');

