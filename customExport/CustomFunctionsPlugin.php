<?php
/**
 * Plugin Name: Custom Functions Plugin
 * Plugin URI: http://eventespresso.com
 * Description: A blank plugin where you can put custom functions
 * Version: 1.0
 * Author: Sidney Harrell
 * Author URI: http://sidneyharrell.com
 * License: GPL2
 */
 
 function espresso_export_stuff() {
	global $wpdb, $ticketing_installed;
	$today = date("Y-m-d-Hi", time());
	$export_all_events = isset($_REQUEST['all_events']) && $_REQUEST['all_events'] == "true" ? TRUE : FALSE;

	//Export data to Excel file
	if (isset($_REQUEST['export'])) {
		switch ($_REQUEST['export']) {

			case "report":
				

				$event_id = isset($_REQUEST['event_id']) ? $_REQUEST['event_id'] : FALSE;

				// export for one event only ?
				if ($event_id) {

					$SQL = "SELECT event_name, event_desc, event_identifier, question_groups, event_meta FROM " . EVENTS_DETAIL_TABLE;
					$SQL .= " WHERE id = %d";

					if ($results = $wpdb->get_row($wpdb->prepare($SQL, $event_id), ARRAY_N)) {

						list( $event_name, $event_description, $event_identifier, $question_groups, $event_meta) = $results;

						$question_groups = maybe_unserialize($question_groups);
						$event_meta = maybe_unserialize($event_meta);

						if (!empty($event_meta['add_attendee_question_groups'])) {
							$question_groups = array_unique(array_merge((array) $question_groups, (array) $event_meta['add_attendee_question_groups']));
						}
					}
				} else {

					// export for ALL EVENTS

					$question_groups = array();
					$event_meta = array();
					$SQL = "SELECT event_name, event_desc, event_identifier, question_groups, event_meta FROM " . EVENTS_DETAIL_TABLE;
					if ($results = $wpdb->get_results($SQL, ARRAY_N)) {

						foreach ($results as $result) {

							list( $event_name, $event_description, $event_identifier, $q_groups, $e_meta) = $result;
							$question_groups = array_unique(array_merge($question_groups, (array) maybe_unserialize($q_groups)));
							$e_meta = (array) maybe_unserialize($e_meta);
							$event_meta = array_unique(array_merge($event_meta, (array) $e_meta['add_attendee_question_groups']));
						}
					}
				}

				$basic_header = array(
					100=>__('Group', 'event_espresso'), 		// column # A
					101=>__('ID', 'event_espresso'), 									// B
					102=>__('Reg ID', 'event_espresso'),  							// C
					103=>__('Payment Method', 'event_espresso'),  		// D
					104=>__('Reg Date', 'event_espresso'),  						// E
					105=>__('Pay Status', 'event_espresso'),  						// F
					106=>__('Type of Payment', 'event_espresso'),  			// G
					107=>__('Transaction ID', 'event_espresso'),  				// H
					108=>__('Price', 'event_espresso'),  								// I
					109=>__('Coupon Code', 'event_espresso'),  				// J
					110=>__('# Attendees', 'event_espresso'),  					// K
					111=>__('Amount Paid', 'event_espresso'),  				// L
					112=>__('Date Paid', 'event_espresso'),  						// M
					113=>__('Event Name', 'event_espresso'),  					// N
					114=>__('Price Option', 'event_espresso'),  					// O
					115=>__('Event Date', 'event_espresso'),  					// P
					116=>__('Event Time', 'event_espresso'),  					// Q
				);
				
				if ( $ticketing_installed ) {
					$basic_header[117] = __('Website Check-in', 'event_espresso'); 		// R
					$basic_header[118] = __('Tickets Scanned', 'event_espresso'); 		// S
					$basic_header[119] = __('Check-in Date', 'event_espresso'); 			// T
				}

				$basic_header[120] = __('Seat Tag', 'event_espresso'); 			// R  	or		U
				$basic_header[121] = __('First Name', 'event_espresso'); 		// S  	or		V
				$basic_header[122] = __('Last Name', 'event_espresso'); 			// T  	or		W
				$basic_header[123] = __('Email', 'event_espresso'); 					// U  	or		X

				$question_groups = maybe_unserialize($question_groups);
				$event_meta = maybe_unserialize($event_meta);

				if (isset($event_meta['add_attendee_question_groups'])) {

//					if ( is_serialized(  $event_meta['add_attendee_question_groups'] ) ){
//						$add_attendee_question_groups = unserialize($event_meta['add_attendee_question_groups']);
//					} else {
//						$add_attendee_question_groups = $event_meta['add_attendee_question_groups'];
//					}					

					if (!empty($add_attendee_question_groups)) {
						$question_groups = array_unique(array_merge((array) $question_groups, (array) $event_meta['add_attendee_question_groups']));
					}
				}


				switch ($_REQUEST['action']) {

					case "event":
						espresso_event_export($event_name);
						break;

					case "payment":

						$question_list = array(); //will be used to associate questions with correct answers
						$question_filter = array(); //will be used to keep track of newly added and deleted questions

						if (count($question_groups) > 0) {
							$question_sequence = array();

							$questions_in = '';
							foreach ($question_groups as $g_id) {
								$questions_in .= $g_id . ',';
							}
							$questions_in = substr($questions_in, 0, -1);

							$group_name = '';
							$counter = 0;

							$quest_sql = "SELECT q.id, q.question FROM " . EVENTS_QUESTION_TABLE . " q ";
							$quest_sql .= " JOIN " . EVENTS_QST_GROUP_REL_TABLE . " qgr on q.id = qgr.question_id ";
							$quest_sql .= " JOIN " . EVENTS_QST_GROUP_TABLE . " qg on qg.id = qgr.group_id ";
							$quest_sql .= " WHERE qgr.group_id in ( $questions_in ) ";
							if (function_exists('espresso_member_data') && ( espresso_member_data('role') == 'espresso_event_manager')) {
								$quest_sql .= " AND qg.wp_user = '" . espresso_member_data('id') . "' ";
							}
							//Fix from Jesse in the forums (http://eventespresso.com/forums/2010/10/form-questions-appearing-in-wrong-columns-in-excel-export/)
							//$quest_sql .= " AND q.system_name is null ORDER BY qg.id, q.id ASC ";
							//$quest_sql .= " AND q.system_name is null ";
							$quest_sql .= " ORDER BY q.sequence, q.id ASC ";

							$questions = $wpdb->get_results($quest_sql);
							$ignore = array('1'=>1, '2'=>2, '3'=>3);

							$num_rows = $wpdb->num_rows;
							if ($num_rows > 0) {
								foreach ($questions as $question) {
									if (!isset($ignore[$question->id])) {
										$question_list[$question->id] = $question->question;
										$question_filter[$question->id] = $question->id;
										$question_text = escape_csv_val( stripslashes( $question->question ));
										if ( ! in_array( $question_text, $basic_header )) {
											$basic_header[1000+$question->id] = $question_text;
										}																	
									}
								}
							}
						}
						
						$new_header[1] = $basic_header[1083];   //rearrange question 83 to column 1
						unset($basic_header[1083]);
						$new_header[2] = $basic_header[1004];   //rearrange question 4 to column 2
						unset($basic_header[1004]);
						$new_header[3] = $basic_header[1005];   //rearrange question 4 to column 3
						unset($basic_header[1005]);
						$new_header[4] = $basic_header[1006];   //rearrange question 6 to column 4
						unset($basic_header[1006]);
						$new_header[5] = $basic_header[1007];   //rearrange question 7 to column 5
						unset($basic_header[1007]);
						$new_header[6] = $basic_header[1009];   //rearrange question 9 to column 6
						unset($basic_header[1009]);
						$new_header[7] = $basic_header[1008];   //rearrange question 9 to column 7
						unset($basic_header[1008]);
						$new_header[8] = $basic_header[121];   //rearrange question 1 to column 8
						unset($basic_header[121]);
						$new_header[9] = $basic_header[122];   //rearrange question 2 to column 9
						unset($basic_header[122]);
						$new_header[10] = $basic_header[1082];   //rearrange question 82 to column 10
						unset($basic_header[1082]);
						$new_header[11] = $basic_header[1010];   //rearrange question 10 to column 11
						unset($basic_header[1010]);
						$new_header[12] = $basic_header[123];   //rearrange question 3 to column 12
						unset($basic_header[123]);
						foreach($basic_header as $header) {
							$new_header[] = $header;
						}

						if (count($question_filter) > 0) {
							$question_filter = implode(",", $question_filter);
						}
						//$question_filter = str_replace( array( '1,','2,','3,' ), '', $question_filter );

						$sql = '';

						$espresso_member = function_exists('espresso_member_data') && espresso_member_data('role') == 'espresso_group_admin' ? TRUE : FALSE;

						if ($espresso_member) {

							$group = get_user_meta(espresso_member_data('id'), "espresso_group", true);
							$group = maybe_unserialize($group);
							$group = implode(",", $group);
							$sql .= "(SELECT ed.event_name, ed.start_date, a.id AS att_id, a.registration_id, a.payment, a.date, a.payment_status, a.txn_type, a.txn_id";
							$sql .= ", a.amount_pd, a.quantity, a.coupon_code";
							$sql .= $ticketing_installed == true ? ", a.checked_in, a.checked_in_quantity, ac.date_scanned" : '';
							$sql .= ", a.payment_date, a.event_time, a.price_option, a.final_price a_final_price, a.quantity a_quantity, a.fname, a.lname, a.email";
							$sql .= " FROM " . EVENTS_ATTENDEE_TABLE . " a ";
							$sql .= " JOIN " . EVENTS_DETAIL_TABLE . " ed ON ed.id=a.event_id ";
							$sql .= $ticketing_installed == true ? " LEFT JOIN " . $wpdb->prefix . "events_attendee_checkin ac ON a.id=ac.attendee_id " : '';
							if ($group != '') {
								$sql .= " JOIN " . EVENTS_VENUE_REL_TABLE . " r ON r.event_id = ed.id ";
								$sql .= " JOIN " . EVENTS_LOCALE_REL_TABLE . " l ON  l.venue_id = r.venue_id ";
							}
							$sql .= $event_id ? " WHERE ed.id = '" . $event_id . "' " : '';
							$sql .= $group != '' ? " AND  l.locale_id IN (" . $group . ") " : '';
							$sql .= ") UNION (";
						}
						$sql .= "SELECT ed.event_name, ed.start_date, a.id AS att_id, a.registration_id, a.payment, a.date, a.payment_status, a.txn_type, a.txn_id";
						$sql .= ", a.quantity, a.coupon_code, a.final_price a_final_price, a.amount_pd, a.quantity a_quantity";
						$sql .= $ticketing_installed == true ? ", a.checked_in, a.checked_in_quantity, ac.date_scanned" : '';
							
						$sql .= ", a.payment_date, a.event_time, a.price_option, a.fname, a.lname, a.email";
						$sql .= " FROM " . EVENTS_ATTENDEE_TABLE . " a ";
						$sql .= " JOIN " . EVENTS_DETAIL_TABLE . " ed ON ed.id=a.event_id ";
						$sql .= $ticketing_installed == true ? " LEFT JOIN " . $wpdb->prefix . "events_attendee_checkin ac ON a.id=ac.attendee_id " : '';
						//$sql .= " JOIN " . EVENTS_ATTENDEE_COST_TABLE . " ac ON a.id=ac.attendee_id ";
						$sql .= $event_id ? " WHERE ed.id = '" . $event_id . "' " : '';

						$sql .= apply_filters('filter_hook_espresso_export_payments_query_where', '');

						if (function_exists('espresso_member_data') && ( espresso_member_data('role') == 'espresso_event_manager' || espresso_member_data('role') == 'espresso_group_admin')) {
							$sql .= " AND ed.wp_user = '" . espresso_member_data('id') . "' ";
						}

						$sql .= $espresso_member ? ") ORDER BY att_id " : " ORDER BY a.id ";

						$participants = $wpdb->get_results($sql);

						$filename = ( isset($_REQUEST['all_events']) && $_REQUEST['all_events'] == "true" ) ? __('all-events', 'event_espresso') : $event_name;

						$filename = sanitize_title_with_dashes($filename) . "-" . $today;
						switch ($_REQUEST['type']) {
							case "csv" :
								$st = "";
								$et = ",";
								$s = $et . $st;
								header("Content-type: application/x-msdownload");
								header("Content-Disposition: attachment; filename=" . $filename . ".csv");
								//header("Content-Disposition: attachment; filename='" .$filename .".csv'");
								header("Pragma: no-cache");
								header("Expires: 0");
								//echo header
								echo implode($s, $new_header) . "\r\n";
								break;

							default :
								$st = "";
								$et = "\t";
								$s = $et . $st;
								header("Content-Disposition: attachment; filename=" . $filename . ".xls");
								//header("Content-Disposition: attachment; filename='" .$filename .".xls'");
								header("Content-Type: application/vnd.ms-excel");
								header("Pragma: no-cache");
								header("Expires: 0");
								//echo header
								echo implode($s, $new_header) . $et . "\r\n";
								break;
						}


						if ($participants) {
							$temp_reg_id = ''; //will temporarily hold the registration id for checking with the next row
							$attendees_group = ''; //will hold the names of the group members
							$group_counter = 1;
							$amount_pd = 0;

							foreach ($participants as $participant) {

								if ($temp_reg_id == '') {
									$temp_reg_id = $participant->registration_id;
									$amount_pd = $participant->amount_pd;
								}


								if ($temp_reg_id == $participant->registration_id) {
									//Do nothing
								} else {
									$group_counter++;
									$temp_reg_id = $participant->registration_id;
								}
								$attendees_group = "Group $group_counter";

								//Build the seating assignment
								$seatingchart_tag = '';
								if (defined("ESPRESSO_SEATING_CHART")) {
									if (class_exists("seating_chart")) {
										if (seating_chart::check_event_has_seating_chart($event_id)) {
											$rs = $wpdb->get_row("select scs.* from " . EVENTS_SEATING_CHART_EVENT_SEAT_TABLE . " sces inner join " . EVENTS_SEATING_CHART_SEAT_TABLE . " scs on sces.seat_id = scs.id where sces.attendee_id = " . $participant->att_id);
											if ($rs !== NULL) {
												$participant->seatingchart_tag = $rs->custom_tag . " " . $rs->seat . " " . $rs->row;
											}
										}
									}
								} else {
									$participant->seatingchart_tag = '';
								}
								
								if(!empty($participant->date_scanned)) {
									$scanned_date_object = DateTime::createFromFormat('Y-m-d H:i:s', $participant->date_scanned);
									$scanned_date = $scanned_date_object->format(get_option('date_format') . ' ' . get_option('time_format'));
								} else {
									$scanned_date = "";
								}

								$row_info[100] = $attendees_group;																															// column # A
								$row_info[101] =  escape_csv_val($participant->att_id);																											// B
								$row_info[102] =  escape_csv_val($participant->registration_id)	;																							// C
								$row_info[103] =  escape_csv_val(stripslashes($participant->payment));																				// D
								$row_info[104] =  escape_csv_val(stripslashes(event_date_display($participant->date, get_option('date_format'))));	//E
								$row_info[105] =  escape_csv_val(stripslashes($participant->payment_status));																	// F
								$row_info[106] =  escape_csv_val(stripslashes($participant->txn_type));																				// G
								$row_info[107] =  escape_csv_val(stripslashes($participant->txn_id));																						// H
								$row_info[108] =  escape_csv_val($participant->a_final_price * $participant->a_quantity);													// I
								$row_info[109] =  escape_csv_val($participant->coupon_code);																								// J
								$row_info[110] =  escape_csv_val($participant->quantity);																										// K
								$row_info[111] =  escape_csv_val($participant->amount_pd)	;																								// L
								$row_info[112] =  escape_csv_val(event_date_display($participant->payment_date, get_option('date_format')));			// M
								$row_info[113] =  escape_csv_val($participant->event_name);																								// N
								$row_info[114] =  escape_csv_val($participant->price_option);																								// O
								$row_info[115] =  escape_csv_val(event_date_display($participant->start_date, get_option('date_format')));					// P
								$row_info[116] =  escape_csv_val(event_date_display($participant->event_time, get_option('time_format')));				// Q
								
								if ( $ticketing_installed == true ) {
									$row_info[117] =  escape_csv_val($participant->checked_in ? "Yes" : "No");																// R
									$row_info[118] =  escape_csv_val($participant->checked_in_quantity);																				// S
									$row_info[119] =  escape_csv_val($scanned_date);																												// T
								}
								$row_info[120] =  escape_csv_val($participant->seatingchart_tag);																					// R		or		U
								$row_info[121] =  escape_csv_val($participant->fname)	;																										// S		or		V
								$row_info[122] =  escape_csv_val($participant->lname);																											// T		or		W
								$row_info[123] =  escape_csv_val($participant->email);																											// U		or		X
								


								if ( ! empty( $question_filter )) {
									$SQL = "SELECT question_id, answer FROM " . EVENTS_ANSWER_TABLE . " ";
									$SQL .= "WHERE question_id IN ($question_filter) AND attendee_id = %d";
									$answers = $wpdb->get_results($wpdb->prepare($SQL, $participant->att_id), OBJECT_K);										
								} else {
									$answers = array();
								}

								foreach ($question_list as $k => $v) {

									// in case the event organizer removes a question from a question group,
									//  the orphaned answers will remian in the answers table.  This check will make sure they don't get exported.

									$search = array("\r", "\n", "\t");
									if (isset($answers[$k])) {
										$clean_answer = str_replace($search, " ", $answers[$k]->answer);
										$clean_answer = stripslashes(str_replace("&#039;", "'", trim($clean_answer)));
										$clean_answer = escape_csv_val($clean_answer);
										$row_info[1000+$answers[$k]->question_id] = $clean_answer;
									} else {
										$row_info[1000+$answers[$k]->question_id] = '';
									}
								}
								
								$new_row_info[1] = $row_info[1083];   //rearrange question 83 to column 1
								unset($row_info[1083]);
								$new_row_info[2] = $row_info[1004];   //rearrange question 4 to column 2
								unset($row_info[1004]);
								$new_row_info[3] = $row_info[1005];   //rearrange question 5 to column 3
								unset($row_info[1005]);
								$new_row_info[4] = $row_info[1006];   //rearrange question 6 to column 4
								unset($row_info[1006]);
								$new_row_info[5] = $row_info[1007];   //rearrange question 7 to column 5
								unset($row_info[1007]);
								$new_row_info[6] = $row_info[1009];   //rearrange question 9 to column 6
								unset($row_info[1009]);
								$new_row_info[7] = $row_info[1008];   //rearrange question 8 to column 7
								unset($row_info[1008]);
								$new_row_info[8] = $row_info[121];   //rearrange question 1 to column 8
								unset($row_info[121]);
								$new_row_info[9] = $row_info[122];   //rearrange question 2 to column 9
								unset($row_info[122]);
								$new_row_info[10] = $row_info[1082];   //rearrange question 82 to column 10
								unset($row_info[1082]);
								$new_row_info[11] = $row_info[1010];   //rearrange question 10 to column 11
								unset($row_info[1010]);
								$new_row_info[12] = $row_info[123];   //rearrange question 3 to column 12
								unset($row_info[123]);
								foreach($row_info as $info) {
									$new_row_info[] = $info;
								}

								switch ($_REQUEST['type']) {
									case "csv" :
										echo implode($s, $new_row_info) . "\r\n";
										break;
									default :
										echo implode($s, $new_row_info) . $et . "\r\n";
										break;
								}
								$new_row_info = array();
							}
						} else {
							echo __('No participant data has been collected.', 'event_espresso');
						}
						exit;
						break;

					default:
						echo '<p>' . __('This Is Not A Valid Selection!', 'event_espresso') . '</p>';
						break;
				}

			default:
				break;
		}
	}
}

