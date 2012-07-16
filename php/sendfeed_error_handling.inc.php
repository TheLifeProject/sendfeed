<?php

	// error handler function
	function sendfeed_error_handler($errno, $errstr, $errfile, $errline)
	{
		
		
		// Check to ensure we don't enter a loop of errors.
		global $sendfeed_error_running;
		if($sendfeed_error_running > 2)
		{
	        $msg = "Error loop detected!<br/>\n";
	        $msg .= "ERROR: [$errno] $errstr<br/>\n";
	        $msg .= "  Fatal error on line $errline in file $errfile<br/>\n";
	        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br/>\n";
	        $msg .= "Aborting...<br/>\n";
	        echo $msg;
	        exit(1);
		}
		else
		{
			$sendfeed_error_running++;
		}
		
		// Initialize the msg variable to blank space.
		$msg = "";
		
	    switch ($errno) {
	    case E_STRICT:
	        $msg = "[$errno] $errstr\n";
	        $msg .= "on line $errline in file $errfile";
	       # sendfeed_alert($msg, false, 'STRICT NOTICE');
	        break;
	
	    case E_ERROR:
	    case E_USER_ERROR:
	        $msg = "[$errno] $errstr\n";
	        $msg .= "  Fatal error on line $errline in file $errfile";
	        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")";
	        $msg .= "Aborting...\n";
	        if(SENDFEED_EMAIL_ADMIN_ERRORS) sendfeed_warn_admin($msg);
	        sendfeed_alert($msg, true, 'FATAL ERROR');
	        exit(1);
	        break;
	
	    case E_USER_WARNING:
	    case E_WARNING:
	        $msg = "[$errno] $errstr\n";
	        $msg .= "  Warning on line $errline in file $errfile";
	        if(SENDFEED_EMAIL_ADMIN_WARNINGS) sendfeed_warn_admin($msg);
	        sendfeed_alert($msg, false, 'WARNING');
	        break;
	
	    case E_USER_NOTICE:
	        $msg = "[$errno] $errstr";
			sendfeed_alert($msg, false);
	        break;

	    case E_NOTICE:
	        $msg = "[$errno] $errstr\n";
	        $msg .= "  Notice on line $errline in file $errfile";
			#sendfeed_alert($msg, false);
	        break;
	
	    default:
	        $msg = "Unknown error type: [$errno] $errstr\n";
	        $msg .= "  Error on line $errline in file $errfile\n";
	        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")";
	        sendfeed_alert($msg, true);
	        break;
	    }
	    
	    $sendfeed_error_running--;
	    return true;
	}

	// Function to display notices or errors.
	function sendfeed_alert($msg, $display=false, $level='NOTICE', $feedID = 0)
	{
		global $sendfeed_current_feed_id;
		
		if($feedID) $sendfeed_current_feed_id = $feedID;
		
		$msg = trim($msg);
		
		if($display === true) sendfeed_admin_notice("$level: $msg");
		
		sendfeed_log($msg, $level, $sendfeed_current_feed_id);
	}
	
	// Function to log messages to the log table in the DB.
	function sendfeed_log($msg, $level='NOTICE', $feedID = 0)
	{
		global $wpdb;
		
		$datestamp = date("F j, Y, g:i a", time());
		$msg = addslashes($msg);

		$version = get_option('sendfeed_version');
		if(!$version)
		{
			return(false);
		}
		if($version < '1.4') 
			$sql = "INSERT INTO " . SENDFEED_TABLE . "_logs (datestamp, message) VALUES ('$datestamp', '$msg');";
		elseif($version < '1.9')
			$sql = "INSERT INTO " . SENDFEED_TABLE . "_logs (timestamp, datestamp, msgtype, message) 
					VALUES ('" . time() . "', '{$datestamp}', '{$level}', '{$msg}');";
		else
			$sql = "INSERT INTO " . SENDFEED_TABLE . "_logs (timestamp, datestamp, msgtype, message, feedID) 
					VALUES ('" . time() . "', '{$datestamp}', '{$level}', '{$msg}', '{$feedID}');";

		$result = $wpdb->query($sql);
		if(!$result)
			trigger_error("Unable to run query: >>>>>$sql<<<<<", E_USER_ERROR);
	}
	
	function sendfeed_admin_notice($msg)
	{
		sendfeed_showMessage(nl2br($msg));
	}
	
	/**
	 * Generic function to show a message to the user using WP's
	 * standard CSS classes to make use of the already-defined
	 * message colour scheme.
	 *
	 * @param $message The message you want to tell the user.
	 * @param $errormsg If true, the message is an error, so use
	 * the red message style. If false, the message is a status
	 * message, so use the yellow information message style.
	 */
	function sendfeed_showMessage($message, $errormsg = false)
	{
		if ($errormsg) {
			echo '<div id="message" class="error">';
		}
		else {
			echo '<div id="message" class="updated fade">';
		}
	
		echo "<p><strong>$message</strong></p></div>";
	}
	
	
	
	function sendfeed_warn_admin($msg)
	{
		global $wpdb;
		
		// Load recent log entries
		$sql = "SELECT * FROM " . SENDFEED_TABLE . "_logs ORDER BY id DESC LIMIT 0,5;";
		$log_items = $wpdb->get_results($sql, ARRAY_A);
		
		$msg = "SENDFEED MESSAGE:\n" .
				"There has been an error in the SendFeed system of your blog '" . get_option('blogname') . "'(" . get_option('siteurl') . ") which has resulted in the following message.\n" .
				"\n" .
				"	$msg\n" .
				"\n" .
				"Here are the log entries leading up to this problem...\n\n";
		
		foreach($log_items as $tmp)
		{
			$msg .= "Date: " . $tmp[datestamp] . "\n" .
					"Level: " . $tmp[msgtype] . "\n" .
					"Message: " . $tmp[message] . "\n\n";
		}
		
		$to = get_option('admin_email');
		$from = $to;
		$subject = "SENDFEED ERROR ON " . get_option('blogname');
		
		wp_mail($to, $subject, $msg, "From: $from\n");
	}

?>