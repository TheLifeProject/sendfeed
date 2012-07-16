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

	function sendfeed_options($msg = "", $action="", $feed_id="")
	{
		global $wpdb;

		$sql = "SELECT * FROM " . SENDFEED_TABLE . " ORDER BY feed_name ASC;";
		$tmp_feeds = $wpdb->get_results($sql, ARRAY_A);
		if($tmp_feeds) foreach($tmp_feeds as $tmp)
		{
			$feeds[$tmp[id]] = $tmp;
		}

		if($action == "remove_feed" AND $feeds[$feed_id])
		{
			$sql = "DELETE FROM " . SENDFEED_TABLE . " WHERE id = '$feed_id';";
			if(!$wpdb->query($sql)) {
				sendfeed_alert("WARNING: Unable to remove feed!", true);
				trigger_error("Failed to run the following sql: $sql", E_USER_WARNING);
			}
			else {
				unset($feeds[$feed_id]);
				sendfeed_alert("Removed feed ID: $feed_id");
			}
		}
		if($action == "activate_feed" AND $feeds[$feed_id])
		{
			// Load the content related pieces.
			$feed_data = sendfeed_create_content($feeds[$feed_id]);
			$timestamp = mktime(1,0,0);
			
			// Ensure we got valid data...
			if($feed_data === false)
			{
				sendfeed_alert("We were not able to load valid data for the feed.  Failed to activate!", true);
			}
			else
			{
				$sql = "UPDATE " . SENDFEED_TABLE . " SET " .
						"activated='1', " .
						"last_sent_time='$timestamp', " .
						"last_sent_data='" . sendfeed_addslashes($feed_data[duplicate_check]) . "' " .
						"WHERE id = '$feed_id';";
				if(!$wpdb->query($sql)) 
				{
					sendfeed_alert("WARNING: Unable to activate feed!", true);
					trigger_error("Failed to run the following sql: $sql", E_USER_WARNING);
				}
				else 
				{
					$feeds[$feed_id][activated] = 1;
					sendfeed_alert("Activated feed ID: $feed_id");
				}
			}
		}
		if($action == "deactivate_feed" AND $feeds[$feed_id])
		{
			$sql = "UPDATE " . SENDFEED_TABLE . " SET activated='0' WHERE id = '$feed_id';";
			if(!$wpdb->query($sql)) {
					sendfeed_alert("WARNING: Unable to deactivate feed!", true);
					trigger_error("Failed to run the following sql: $sql", E_USER_WARNING);
			}
			else {
				$feeds[$feed_id][activated] = 0;
				sendfeed_alert("DeActivated feed ID: $feed_id");
			}
		}

		include(SENDFEED_PLUGIN_PATH . 'html/main_options.inc.php');
	}
	
	function sendfeed_display_instructions()
	{
		include(SENDFEED_PLUGIN_PATH . 'html/sidebar_instructions.inc.php');
	}

	function sendfeed_new_feed($msg = "")
	{
		global $wpdb;
		
		if(file_exists(SENDFEED_PLUGIN_PATH . 'email_templates/default.text')) 
			$feed[text_template] = htmlentities(implode("", file(SENDFEED_PLUGIN_PATH . 'email_templates/default.text')));
		if(file_exists(SENDFEED_PLUGIN_PATH . 'email_templates/default.html')) 
			$feed[html_template] = htmlentities(implode("", file(SENDFEED_PLUGIN_PATH . 'email_templates/default.html')));

		if($_POST)
		{
			if(isset($subquery) OR isset($subkeys)) unset($subquery, $subkeys);
			
			$postValues = array();
			foreach($_POST as $key=>$value) $postValues[$key] = sendfeed_addslashes($value);

			// Doublecheck the values entered, and only save those that are valid.

			/*
			 * REQUIRED FIELDS
			 */
			if(sendfeed_regi("^.{1,255}$", $postValues[feed_name])) 	{
				$subquery[] = "'" . $postValues[feed_name] . "'";
				$subkeys[] = "feed_name";
			}
				else $msg .= "  You have not entered a name for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[feed_url])) 	{
				$subquery[] = "'" . $postValues[feed_url] . "'";
				$subkeys[] = "feed_url";
			}
				else $msg .= "  You have not entered a URL for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[recipient])) 	{
				$subquery[] = "'" . $postValues[recipient] . "'";
				$subkeys[] = "recipient";
			}
				else $msg .= "  You have not entered a recipient for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[frequency])) 	{
				$subquery[] = "'" . $postValues[frequency] . "'";
				$subkeys[] = "frequency";
			}
				else $msg .= "  You have not entered a frequency for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[from_name])) 	{
				$subquery[] = "'" . $postValues[from_name] . "'";
				$subkeys[] = "from_name";
			}
				else $msg .= "  You have not entered a from name for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[from_email])) 	{
				$subquery[] = "'" . $postValues[from_email] . "'";
				$subkeys[] = "from_email";
			}
				else $msg .= "  You have not entered a from email for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[subject])) 	{
				$subquery[] = "'" . $postValues[subject] . "'";
				$subkeys[] = "subject";
			}
				else $msg .= "  You have not entered a subject for the feed email.";
			if(sendfeed_regi("^.+$", $postValues[text_template])) 	{
				$subquery[] = "'" . $postValues[text_template] . "'";
				$subkeys[] = "text_template";
			}
				else $msg .= "  You have not entered a text template for the feed email.";



			/*
			 * NON Required fields
			 */
			if(sendfeed_regi("^.+$", $postValues['additional_headers'])) 	{
				$subquery[] = "'" . trim($postValues['additional_headers']) . "'";
				$subkeys[] = "additional_headers";
			}
			if(sendfeed_regi("^.+$", $postValues['html_template'])) 	{
				$subquery[] = "'" . $postValues['html_template'] . "'";
				$subkeys[] = "html_template";
			}


			if(!$msg)
			{
				$subquery = implode(", ", $subquery);
				$subkeys = implode(", ", $subkeys);

				$sql = "INSERT INTO " . SENDFEED_TABLE . " ($subkeys) VALUES ($subquery)";

			#	echo("Running SQL: $sql");
				$result = $wpdb->query($sql);

				if($result !== false)
				{
					sendfeed_options("Your feed has been successfully saved.");
				}
				else
				{
					trigger_error("WARNING: The feed was not saved successfully. [$sql]", E_USER_WARNING);
				}

				return($result);
				exit;
			}
			$feed = $postValues;
			foreach($feed as $key=>$value)
			{
				$feed[$key] = htmlentities(stripslashes($value), ENT_QUOTES, get_option('blog_charset'));
			}
		}
		include(SENDFEED_PLUGIN_PATH . 'html/new_feed.inc.php');
	}

	function sendfeed_edit_feed($feed_id, $msg = "")
	{
		global $wpdb;

		$sql = "SELECT * FROM " . SENDFEED_TABLE . " WHERE id = '$feed_id';";
		$feed = $wpdb->get_row($sql, ARRAY_A);
		if(!$feed)
		{
			sendfeed_options("WARNING: We were not able to load the feed ID you specified.");
			return(false);
		}

		if($_POST)
		{
			$postValues = array();
			
			foreach($_POST as $key=>$value) $postValues[$key] = sendfeed_addslashes($value);
			
			if(isset($subquery)) unset($subquery);

			// Doublecheck the values entered, and only save those that are valid.
			/*
			 * REQUIRED Fields
			 */
			if(sendfeed_regi("^.{1,255}$", $postValues[feed_name])) 	$subquery[] = "feed_name = '" . $postValues[feed_name] . "'";
				else $msg .= "  You have not entered a name for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[feed_url])) 	$subquery[] = "feed_url = '" . $postValues[feed_url] . "'";
				else $msg .= "  You have not entered a URL for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[recipient])) 	$subquery[] = "recipient = '" . $postValues[recipient] . "'";
				else $msg .= "  You have not entered a recipient for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[frequency])) 	$subquery[] = "frequency = '" . $postValues[frequency] . "'";
				else $msg .= "  You have not entered a frequency for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[from_name])) 	$subquery[] = "from_name = '" . $postValues[from_name] . "'";
				else $msg .= "  You have not entered a from name for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[from_email])) 	$subquery[] = "from_email = '" . $postValues[from_email] . "'";
				else $msg .= "  You have not entered a from email for the feed email.";
			if(sendfeed_regi("^.{1,255}$", $postValues[subject])) 	$subquery[] = "subject = '" . $postValues[subject] . "'";
				else $msg .= "  You have not entered a subject for the feed email.";
			if(sendfeed_regi("^.+$", $postValues[text_template])) 	$subquery[] = "text_template = '" . $postValues[text_template] . "'";
				else $msg .= "  You have not entered a text template for the feed email.";
			if(sendfeed_regi("^.+$", $postValues[html_template])) 	$subquery[] = "html_template = '" . $postValues[html_template] . "'";
				else $msg .= "  You have not entered an html template for the feed email.";

			/*
			 * NON Required Fields
			 */
			if(sendfeed_regi("^.*$", $postValues[additional_headers])) 	$subquery[] = "additional_headers = '" . trim($postValues[additional_headers]) . "'";


			if(!$msg)
			{
				$subquery = implode(", ", $subquery);
				$sql = "UPDATE " . SENDFEED_TABLE . " SET $subquery WHERE id = '$feed_id';";
				
		#		echo("Running SQL: $sql");
				$result = $wpdb->query($sql);

				if($result !== false)
					sendfeed_options("Your feed has been successfully saved.");
				else
					sendfeed_options("WARNING: The feed was not saved successfully.");

				return($result);
				exit;



			}
			$feed = $postValues;
			foreach($feed as $key=>$value)
			{
				$feed[$key] = stripslashes($value);
			}
		}
		foreach($feed as $key=>$value)
		{
			$feed[$key] = htmlentities($value, ENT_QUOTES, get_option('blog_charset'));
		}


		include(SENDFEED_PLUGIN_PATH . 'html/edit_feed.inc.php');
	}

	function sendfeed_test_feed($feed_id, $msg = "")
	{
		global $wpdb;

		$sql = "SELECT * FROM " . SENDFEED_TABLE . " WHERE id = '$feed_id';";
		$feed = $wpdb->get_row($sql, ARRAY_A);
		if(!$feed)
		{
			sendfeed_options("WARNING: We were not able to load the feed ID you specified.");
			return(false);
		}


		if($_POST['recipient'])
		{
			$postValues = array();
			foreach($_POST as $key=>$value) $postValues[$key] = sendfeed_addslashes($value);

			$result = sendfeed_send_email($feed, $postValues['recipient'], $postValues['include_additional_headers']);
			if($result)
				$msg = "Message sent successfully.";
			else
				$msg = "WARNING: Message failed to send!";
		}


		foreach($feed as $key=>$value)
		{
			$feed[$key] = htmlentities($value, ENT_QUOTES, get_option('blog_charset'));
		}

		include(SENDFEED_PLUGIN_PATH . 'html/test_feed.inc.php');
	}

	function sendfeed_manualsend($feed_id)
	{
		global $wpdb;

		$sql = "SELECT * FROM " . SENDFEED_TABLE . " WHERE id = '$feed_id';";
		$feed = $wpdb->get_row($sql, ARRAY_A);
		if(!$feed)
		{
			sendfeed_options("WARNING: We were not able to load the feed ID you specified.");
			return(false);
		}

		$result = sendfeed_send_email($feed, $feed[recipient]);
		if($result)
		{
			$msg = "Message sent successfully.";
		}
		else
			$msg = "WARNING: Message failed to send!";

		include(SENDFEED_PLUGIN_PATH . 'html/test_feed_manual.inc.php');
	}
	
	function sendfeed_preview($feed_id)
	{
		global $wpdb;

		$sql = "SELECT * FROM " . SENDFEED_TABLE . " WHERE id = '$feed_id';";
		$feed_data = $wpdb->get_row($sql, ARRAY_A);
		
		if(!$feed_data)
		{
			sendfeed_options("WARNING: We were not able to load the feed ID you specified.");
			return(false);
		}

		$feed_data = sendfeed_create_content($feed_data);
		
		include(SENDFEED_PLUGIN_PATH . 'html/preview_email.inc.php');
	}
	
	function sendfeed_display_log()
	{
		global $wpdb;
		
		if(isset($_GET['pageNumber']) && $_GET['pageNumber'] > 0)
			$pageNumber = $_GET['pageNumber'] - 1;
		else
			$pageNumber = 0;
			
		$start_pos = $pageNumber * SENDFEED_LOG_LIMIT;
		
		$where = array();
		
		// Allow filtering of just a feed ID.
		if(is_numeric($_GET['feedID']) && $_GET['feedID'] > 0)
		{
			$where[] = "feedID='" . addslashes(stripslashes($_GET['feedID'])) . "'";
		}
		
		if(isset($_GET['feedSearch']))
		{
			$where[] = "(message LIKE '%" . addslashes(stripslashes($_GET['feedSearch'])) . "%' OR datestamp LIKE '%" . addslashes(stripslashes($_GET['feedSearch'])) . "%')";
		}
		
		if(count($where) > 0)
			$where = 'WHERE ' . implode(' AND ', $where);
		else
			$where = '';
		
		// Count totals:
		$sql = "SELECT COUNT(id) AS num FROM " . SENDFEED_TABLE . "_logs {$where};";
		$results = $wpdb->get_results($sql);
		$totalRows = $results[0]->num;
		
		// Create navigation bar for results.
		$nav = sendfeed_paged_nav($totalRows, SENDFEED_LOG_LIMIT, false);
		
		// Create the SQL statement.
		$sql = "SELECT * FROM " . SENDFEED_TABLE . "_logs {$where} ORDER BY id DESC LIMIT $start_pos," . (SENDFEED_LOG_LIMIT + 1) . ";";
	#	echo($sql);
		$log_items = $wpdb->get_results($sql, ARRAY_A);
		
		
		// Get a list of all feed items for reference.
		$sql = "SELECT * FROM " . SENDFEED_TABLE . " ORDER BY id ASC;";
		$feedsTmp = $wpdb->get_results($sql, ARRAY_A);
		$feeds = array();
		foreach($feedsTmp as $feed)
		{
			$feeds[$feed['id']] = $feed;
		}
		
		include(SENDFEED_PLUGIN_PATH . 'html/display_log.inc.php');
		
	}
	
	function sendfeed_test_module()
	{
		$client = new sendfeed_httpClient();
		$data = $client->request('http://feeds.feedburner.com/TruthmediaMinistryUpdate');
		
		echo "<pre>DATADATADATADATA\n";
		echo htmlentities($data);
		echo "\nDATADATADATADATADATA</pre>";
	}

?>
