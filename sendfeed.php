<?php
/*
Plugin Name: SendFeed
Plugin URI: http://truthmedia.com/wordpress/sendfeed/
Description: Allow posts pulled from RSS feeds to be sent out via email when published.
Version: 2.0
Author: TruthMedia
Author URI: http://truthmedia.com/
Requires: WordPress Version 2.3
*/

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


	global $wpdb, $table_prefix;
	
	// For older WP compatibility... also used in the scriptLock
	if(!isset($wpdb->prefix) OR $wpdb->prefix == '' OR $wpdb->prefix == false) 
		$sendfeed_table_prefix = $table_prefix;
	else 
		$sendfeed_table_prefix = $wpdb->prefix;

	// Predefined variables used in the plugin.
	define('SENDFEED_VERSION', '2.0');
	define('SENDFEED_TABLE', $sendfeed_table_prefix . "sendfeed");
	define('SENDFEED_CRON_MINUTES', 4);

	define('SENDFEED_FILEPATH', basename(__FILE__));
	define('SENDFEED_PLUGIN_PATH', str_replace(SENDFEED_FILEPATH, "", __FILE__));
	define('SENDFEED_URL', substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], SENDFEED_FILEPATH) + strlen(SENDFEED_FILEPATH)));
	define('SENDFEED_PLUGIN_LOCATION', get_option('siteurl') . '/wp-content/plugins/sendfeed/');

	define('SENDFEED_EMAIL_ADMIN_WARNINGS', false);
	define('SENDFEED_EMAIL_ADMIN_ERRORS', true);
	
	define('SENDFEED_LOG_LIMIT', 50);			// Define the number of items per page on the log viewer.
	
	include("php/sendfeed_activation.php");
	include("php/sendfeed_admin.php");
	include("php/sendfeed_crontask.php");
	include('php/sendfeed_error_handling.inc.php');
	include("php/sendfeed_functions.php");
	include("php/sendfeed_httpClient.class.php");
	include("php/sendfeed_pages.php");
	include('php/sendfeed_scriptLock.class.php');

	
	add_action('admin_menu', 'sendfeed_admin');
	add_filter('admin_head', 'sendfeed_css');
	add_action('sendfeed_crontick', 'sendfeed_cron_tick');
	
	
	/**
	 * Sets up the admin menu functions
	 */
	function sendfeed_admin()
	{
		add_management_page('SendFeed', 'SendFeed', 'edit_pages', 'sendfeed/sendfeed.php', 'sendfeed_menu');
		
		
		// Check to ensure we don't need to run an upgrade.
		$version = get_option('sendfeed_version');
		if($version != SENDFEED_VERSION 
				&& $_GET['page'] != 'sendfeed/sendfeed.php') 
		{
			sendfeed_admin_notice("SendFeed needs to be upgraded.  Please <a href='/wp-admin/tools.php?page=sendfeed/sendfeed.php'>visit the SendFeed management page</a>.  (Tools &gt; SendFeed)");
		}
	}
	

	/**
	 * Runs each time a WordPress page loads.  Used to check whether or not we need to run the crontask.
	 */
	function sendfeed_cron_tick() {
		// set to the user defined error handler
		$sendfeed_error_handler = set_error_handler("sendfeed_error_handler");
		
		$version = get_option('sendfeed_version');
		if($version != SENDFEED_VERSION) {
			trigger_error("Terminating cron tick early due to plugin not being correct version.  " .
					"Database Version: " . $version . "  " .
					"Installed Files Version: " . SENDFEED_VERSION);
			// Restore the previous error handler.
			restore_error_handler();
			return(false);
		}
		
		
		$timezone = date_default_timezone_get();
		
		// set the default timezone to use. Available since PHP 5.1
		date_default_timezone_set('America/Vancouver');


		// Load the last cron check run time from memory.
		$last_run = get_option('sendfeed_last_cron_tick');
		
#		echo "Last Run: " .  date("F j, Y, g:i a T Y", $last_run) . "<br/>\n";
#		echo "DateStamp: " . date("F j, Y, g:i a T Y", time()) . "<br/>\n";
#		exit;

		// Determine the minimum time a cron item should have run last.
		$timelimit = time() - (SENDFEED_CRON_MINUTES * 60);

		// If the last run time is older than the timelimit, go ahead and load and run the cron checker.
		if(!$last_run OR $last_run < $timelimit) {

			sendfeed_log("------ START CronTick -------------------------------------------------");
			
			global $sendfeed_script_lock;
			$sendfeed_script_lock = new sendfeed_scriptLock('sendfeed_crontick');
			
			
			if($sendfeed_script_lock->lock() !== false)
			{
		
	
				update_option('sendfeed_last_cron_tick', time());
					
				sendfeed_crontask();
				
				$sendfeed_script_lock->unlock();
			
				sendfeed_log("------ FINISH CronTick -------------------------------------------------");

			}	
			else
			{

				sendfeed_log("------ ABORT CronTick -------------------------------------------------");

			}
		}
		
		
		
		date_default_timezone_set($timezone);
		
		// Restore the previous error handler.
		restore_error_handler();
	}
	

if(!function_exists('debug')) {
	function debug()
	{
		if(!strpos($_SERVER['DOCUMENT_ROOT'], 'wordpresstest')) return;
		$arg_list = func_get_args();
		if(count($arg_list) > 0)
		{
			echo "<pre>-------------------------------\n";
			foreach($arg_list as $arg)
			{
				ob_start();
				print_r($arg);
				$msg = ob_get_contents();
				ob_end_clean();
				$msg = str_replace('&', '&amp;', $msg);
				$msg = str_replace('<', '&lt;', $msg);
				$msg = str_replace('>', '&gt;', $msg);
				echo "$msg\n"; 
			}
			echo "</pre>";
		}
	}
}