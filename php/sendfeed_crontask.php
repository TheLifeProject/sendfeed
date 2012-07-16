<?php
/*

	Created by the TruthMedia
	(website: truthmedia.com       email : editor@truthmedia.com)

	Plugin Programming and Design by James Warkentin
	http://www.warkensoft.com/about-me/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

	add_filter( 'cron_schedules', 'sendfeed_filter_cron_schedules' );
	// add custom time to cron
	function sendfeed_filter_cron_schedules( $schedules ) 
	{
		// add a '5 minute' schedule to the existing set
		$schedules['five_minutes'] = array(
				'interval' => 300,
				'display' => __('Every 5 Minutes')
		);
		
		// add a '15 minute' schedule to the existing set
		$schedules['fifteen_minutes'] = array(
				'interval' => 900,
				'display' => __('Every 15 Minutes')
		);
		return $schedules;
	}
	
	
	/**
	 * Function to set up the wp crontask.
	 */
	function sendfeed_setup_crontask()
	{
		if ( ! wp_next_scheduled('sendfeed_crontick') ) {
			wp_schedule_event( current_time( 'timestamp' ), 'five_minutes', 'sendfeed_crontick' );
		}
	}
	
	
	/**
	 * Function to disconnect the wp crontask.
	 */
	function sendfeed_cancel_crontask()
	{
		wp_clear_scheduled_hook('sendfeed_crontick');
	}
	
	
	// Lockdown the currently running sendfeed instance to prevent multiple sends of the same content.
	// This function should be run on plugin start, as well as right before sending a mailing.
	function sendfeed_crontask()
	{
		sendfeed_alert("Running sendfeed_crontask now to check for items to be sent.", false);
		
		global $wpdb, $sendfeed_script_lock;
		global $sendfeed_current_feed_id;
	
		// Load the feed data
		$sql = "SELECT * FROM " . SENDFEED_TABLE . " WHERE activated='1';";
		$tmp_feeds = $wpdb->get_results($sql, ARRAY_A);
		if(!$tmp_feeds) {
			sendfeed_alert("Nothing to be sent.  Terminating sendfeed_crontask function.", false);
			return(false);
		}
		foreach($tmp_feeds as $tmp)
		{
			$feeds[$tmp[id]] = $tmp;
		}
		unset($tmp_feeds);
	
		$day = date("d");
		$hour = date("h");
		$timestamp = time();
		
		
	
		if (!function_exists('sendfeed_send_email')) trigger_error("Unable to continue due to missing function [sendfeed_send_email].", E_USER_ERROR);
	
		// Proceed to iterate through the feeds.
		foreach($feeds as $feed_id=>$feed)
		{
			if($feed_id) $sendfeed_current_feed_id = $feed_id;
			sendfeed_alert("Processing: " . $feed[feed_name]);
			
			switch($feed[frequency])
			{
				// This should fire every time.
				case "On Feed Update":
	
					$result = sendfeed_send_email($feed);
					if($result !== false)
					{
						sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
						// Otherwise, update the db with the latest sent timestamp.
						$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
						$result = $wpdb->query($sql);
						if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
					}
	
				break;
	
	
	
	
				// Feeds with this frequency selected should run once every morning at 4 am.
				case "Daily at 4 AM":
	
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
	
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds)) {
	
						// Ensure that it is the next day.
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
						else
						{
							$timepassed = ($timestamp - $feed[last_sent_time] + $margin_seconds) / (60);
							trigger_error("It hasn't been 24 hours since sending the last feed yet. [" . $feed[feed_name] . "]  It has only been " . $timepassed . " minutes.");
						}
					}
					else
						trigger_error("It isn't time to send this feed yet. [" . $feed[feed_name] . "]");
	
				break;
	
	
	
	
				// Only send these feeds on Sunday after 4 AM.
				case "Weekly on Sunday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Sunday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
	
				break;
	
	
	
	
				//
				case "Weekly on Monday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Monday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
	
				break;
	
	
	
	
				//
				case "Weekly on Tuesday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Tuesday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
	
	
	
	
				//
				case "Weekly on Wednesday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Wednesday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
	
	
	
	
				//
				case "Weekly on Thursday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Thursday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
	
	
	
	
				//
				case "Weekly on Friday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Friday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
	
	
	
	
				//
				case "Weekly on Saturday at 4 AM":
	
					$weekday = date("l");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $weekday == "Saturday") {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
	
	
	
	
				//
				case "Monthly on the first day at 4 AM":
	
					$monthday = date("j");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $monthday == 1) {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
	
				break;
	
	
	
	
				//
				case "Monthly on the 15th day at 4 AM":
	
					$monthday = date("j");
					$send_timestamp = mktime(4,0,0);	# Feed should be run only at 4 am.
					$margin_seconds = 5 * 60;			# 5 minute margin.
					$old_date_stamp = date("d", $feed[last_sent_time]);
					$new_date_stamp = date("d", time());
	
					// Ensure that it is after 4 am (with a 5 minute margin for error)
					if($timestamp > ($send_timestamp - $margin_seconds) AND $monthday == 15) {
	
						// Ensure that it has been 24 hours since the last mailing (with a 5 minute margin for error)
						if($new_date_stamp != $old_date_stamp) {
	
							// All tests passed therefore we should be ok to send the mailing.
							$result = sendfeed_send_email($feed);
							if($result)
							{
								sendfeed_alert("Sent [" . $feed[feed_name] . "] successfully!");
	
								// Otherwise, update the db with the latest sent timestamp.
								$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_time='" . $timestamp . "' WHERE id = '" . $feed_id . "';";
								$result = $wpdb->query($sql);
								if(!$result) trigger_error("Unable to update table with current newsletter timestamp.", E_USER_ERROR);
							}
	
						}
					}
				break;
			}
		}
	
		sendfeed_alert("Finished running sendfeed_crontask.", false);
	
	}
?>