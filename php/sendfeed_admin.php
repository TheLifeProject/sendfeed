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

	// Menu processing for SendFeed system
	function sendfeed_menu()
	{
		// set to the user defined error handler
		$sendfeed_error_handler = set_error_handler("sendfeed_error_handler");

		$timezone = date_default_timezone_get();
		
		// set the default timezone to use. Available since PHP 5.1
		date_default_timezone_set('America/Vancouver');

		// Check to ensure we don't need to run an upgrade.
		$version = get_option('sendfeed_version');
		if($version != SENDFEED_VERSION) {
			$version = sendfeed_activation($version);
		}
		
		// Log data clipping.  Configure the log to only include information from the past 30 days.
		$result = sendfeed_clip_log();

		// Proceed.
		$feed_id = false;
		if(isset($_GET['feed_id']) AND sendfeed_regi("^[0-9]+$", $_GET['feed_id'])) $feed_id = $_GET['feed_id'];
		elseif(isset($_POST['feed_id']) AND sendfeed_regi("^[0-9]+$", $_POST['feed_id'])) $feed_id = $_POST['feed_id'];

		if(isset($_GET['sendfeed_page'])) switch($_GET['sendfeed_page'])
		{
			case "new_feed":
				sendfeed_new_feed();
			break;

			case "edit_feed":

				if($feed_id)
					sendfeed_edit_feed($feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "preview":
				if($feed_id)
					sendfeed_preview($feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "test_feed":
				if($feed_id)
					sendfeed_test_feed($feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "activate_feed":
				if($feed_id)
					sendfeed_options("This feed has been activated.", "activate_feed", $feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "deactivate_feed":
				if($feed_id)
					sendfeed_options("This feed has been deactivated.", "deactivate_feed", $feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "remove_feed":
				if($feed_id)
					sendfeed_options("This feed has been removed.", "remove_feed", $feed_id);
				else
					sendfeed_options("You have not specified a valid feed ID.");
			break;

			case "template_editor":
				sendfeed_template_editor();
			break;

			case "manualsend_feed":
				sendfeed_manualsend($feed_id);
			break;

			case "display_log":
				sendfeed_display_log();
			break;

			case "test_module":
				sendfeed_test_module();
			break;

			default:
				sendfeed_options();
			break;
		}
		else
			sendfeed_options();

		date_default_timezone_set($timezone);
				
		// Restore the previous error handler.
		restore_error_handler();
	}

	
	// Load the admin interface CSS data
	function sendfeed_css()
	{
		// set to the user defined error handler
		$sendfeed_error_handler = set_error_handler("sendfeed_error_handler");
		
		if(isset($_REQUEST['page']) AND substr(($_REQUEST['page']), 0, 8) == 'sendfeed')
		{
			include_once(SENDFEED_PLUGIN_PATH . "css/admin_styles.css");
		}
		
		// Restore the previous error handler.
		restore_error_handler();
	}
	
	
	// This function should be used to clip the log file to only entries within the last $days number of days
	// It should only be called once per day at most.
	function sendfeed_clip_log($days = 30)
	{
		if(get_option('sendfeed_version') < '1.2') {
			sendfeed_log("Unable to run clipping on database versions older than 1.2.", "msg");
			return(true);
		}
		
		$oneday = 3600*24;
		$clip_seconds = time() - $oneday * $days;
		
		$last_clip_timestamp = get_option('sendfeed_clip_timestamp');
		if( ((int)$last_clip_timestamp) > time() - ( $oneday * $days ) ) 
		{
			return(true);
		}
		else
		{
			sendfeed_alert("Clipping log files to remove entries older than $days days.");
		
			global $wpdb;
			$sql = "DELETE FROM " . SENDFEED_TABLE . "_logs WHERE timestamp < '" . $clip_seconds . "';";
			$result = $wpdb->query($sql);
			
			update_option('sendfeed_clip_timestamp', time());
			return(true);
		}
	}
	

?>