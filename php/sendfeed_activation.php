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

	/**
	 * Run on plugin activation.
	 */
	function sendfeed_activation_hook()
	{
		// Check to ensure we don't need to run an upgrade.
		$version = get_option('sendfeed_version');
		if($version != SENDFEED_VERSION) {
			$version = sendfeed_activation($version);
		}
	}
	register_activation_hook(__FILE__, 'sendfeed_activation_hook');
	
	
	/**
	 * Run on plugin deactivation.
	 */
	function sendfeed_deactivation_hook()
	{
		sendfeed_cancel_crontask();
	}
	register_deactivation_hook(__FILE__, 'sendfeed_deactivation_hook');
	
	
	/**
	 * Main SendFeed Activation function.
	 * @param string $version
	 * @return string
	 */
	function sendfeed_activation($version = false) {
		global $wpdb;

		// Load version info from DB.
		sendfeed_alert("SendFeed Version numbers do not match.  Running install/upgrade scripts.", true);

		// New Installation Only.  Should contain all necessary scripts needed for a new install.
		if(!$version) {
			$sql = "CREATE TABLE IF NOT EXISTS " . SENDFEED_TABLE . " (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feed_name` text NOT NULL,
  `feed_url` text NOT NULL,
  `recipient` text NOT NULL,
  `additional_headers` text NOT NULL,
  `frequency` text NOT NULL,
  `from_name` text NOT NULL,
  `from_email` text NOT NULL,
  `subject` text NOT NULL,
  `text_template` longtext NOT NULL,
  `html_template` longtext NOT NULL,
  `activated` tinyint(4) NOT NULL,
  `last_sent_time` bigint(20) unsigned zerofill NOT NULL,
  `last_sent_data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			$result = $wpdb->query($sql);
			if($result === false) trigger_error("Installation Problem: Unable to create table " . SENDFEED_TABLE . " [$sql]", E_USER_ERROR);
			else sendfeed_log("Successfully ran: $sql");

			$sql = "CREATE TABLE IF NOT EXISTS `" . SENDFEED_TABLE . "_logs` (
  `id` bigint(20) NOT NULL auto_increment,
  `timestamp` bigint(20) unsigned zerofill NOT NULL,
  `datestamp` varchar(50) NOT NULL,
  `msgtype` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `feedID` int NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `timestamp` (`timestamp`),
  KEY `msgtype` (`msgtype`),
  KEY `feedID` (`feedID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			$result = $wpdb->query($sql);
			if($result === false) trigger_error("Installation Problem: Unable to create table " . SENDFEED_TABLE . "_logs [$sql]", E_USER_ERROR);
			else sendfeed_log("Successfully ran: $sql");

			$sql = "CREATE TABLE IF NOT EXISTS `" . SENDFEED_TABLE . "_lock` (
`id` INT NOT NULL ,
`timestamp` BIGINT UNSIGNED ZEROFILL NOT NULL ,
`lock_key` TEXT NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `timestamp` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;";
			$result = $wpdb->query($sql);
			if($result === false) trigger_error("Installation Problem: Unable to create table " . SENDFEED_TABLE . "_lock [$sql]", E_USER_ERROR);
			else sendfeed_log("Successfully ran: $sql");

			update_option('sendfeed_clip_timestamp', time());
			update_option('sendfeed_version', SENDFEED_VERSION);
			$version = SENDFEED_VERSION;
			sendfeed_alert("SendFeed Installation Completed Successfully!");
			return($version);
		}



		/************** UPGRADES **************/
		// Upgrade to 1.3
		if($version < "1.3") {
			die("I'm sorry.  The version of SendFeed you are running is no longer able to be upgraded.");
		}

		// Upgrade to 1.4
		if($version < "1.4") {
			$sql  = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` ADD `timestamp` BIGINT UNSIGNED ZEROFILL NOT NULL AFTER `id` ; ';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` ADD INDEX ( `timestamp` ) ; ';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` DROP INDEX `timestamp_2` ; ';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` CHANGE `message` `message` LONGBLOB NOT NULL ';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql  = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` ADD `msgtype` VARCHAR( 255 ) NOT NULL AFTER `datestamp` ;';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = ' ALTER TABLE `' . SENDFEED_TABLE . '_logs` ADD INDEX ( `msgtype` ) ;';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = 'ALTER TABLE `' . SENDFEED_TABLE . '_logs` DROP INDEX `msgtype_2` ; ';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");
			
			$sql = ' ALTER TABLE `' . SENDFEED_TABLE . '` CHANGE `feed_name` `feed_name` BLOB NOT NULL ,
CHANGE `feed_url` `feed_url` BLOB NOT NULL ,
CHANGE `recipient` `recipient` BLOB NOT NULL ,
CHANGE `additional_headers` `additional_headers` BLOB NOT NULL ,
CHANGE `from_name` `from_name` BLOB NOT NULL ,
CHANGE `from_email` `from_email` BLOB NOT NULL ,
CHANGE `subject` `subject` BLOB NOT NULL ,
CHANGE `text_template` `text_template` LONGBLOB NOT NULL ,
CHANGE `html_template` `html_template` LONGBLOB NOT NULL ,
CHANGE `last_sent_data` `last_sent_data` LONGBLOB NOT NULL ;';
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");

			$sql = "CREATE TABLE IF NOT EXISTS `" . SENDFEED_TABLE . "_lock` (
`id` INT NOT NULL ,
`timestamp` BIGINT UNSIGNED ZEROFILL NOT NULL ,
`lock_key` TEXT NOT NULL ,
PRIMARY KEY ( `id` ) ,
INDEX ( `timestamp` )
) ;";
			$result = $wpdb->query($sql);
			if($result !== false) sendfeed_log("Successfully ran sql: $sql");
			else sendfeed_log("FAILED running sql: $sql");

			
			update_option('sendfeed_version', '1.4');
			sendfeed_alert("SendFeed Upgrade to 1.4 Completed Successfully!", true);
		}
		
		// Upgrade to 1.5
		if($version < "1.5") {
			update_option('sendfeed_version', '1.5');
			sendfeed_alert("SendFeed Upgrade to 1.5 Completed Successfully!", true);
		}

		// Upgrade to 1.6
		if($version < "1.6") {
			update_option('sendfeed_version', '1.6');
			sendfeed_alert("SendFeed Upgrade to 1.6 Completed Successfully!", true);
		}

		// Upgrade to 1.7
		if($version < "1.7") {
			update_option('sendfeed_version', '1.7');
			sendfeed_alert("SendFeed Upgrade to 1.7 Completed Successfully!", true);
		}

		// Upgrade to 1.8
		if($version < "1.8") {
			update_option('sendfeed_version', '1.8');
			sendfeed_alert("SendFeed Upgrade to 1.8 Completed Successfully!", true);
		}

		// Upgrade to 1.9
		if($version < "1.9") {
			
			
			$sql = "
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `frequency` `frequency` BLOB NOT NULL DEFAULT '';
			ALTER TABLE `" . SENDFEED_TABLE . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `feed_name` `feed_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `feed_url` `feed_url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `recipient` `recipient` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `additional_headers` `additional_headers` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `frequency` `frequency` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `from_name` `from_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `from_email` `from_email` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `subject` `subject` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `text_template` `text_template` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `html_template` `html_template` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "` CHANGE `last_sent_data` `last_sent_data` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "_logs` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
			ALTER TABLE `" . SENDFEED_TABLE . "_logs` CHANGE `message` `message` LONGTEXT NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "_logs` ADD `feedID` INT NOT NULL;
			ALTER TABLE `" . SENDFEED_TABLE . "_logs` ADD INDEX ( `feedID` ) ;
			";
			
			$errors = false;
			$sql = explode(";", trim($sql));
			foreach($sql as $query)
			{
				$query = trim($query);
				if(!$query) continue;
				$query .= ';';
				$result = $wpdb->query($query);
				if(!$result)
				{
					sendfeed_alert("SendFeed Upgraded FAILED!!!  You may need to modify your table structure by hand.  Unable to run sql: $query", true, 'ERROR');
	#				$errors = true;
				}
			}
			
			if(!$errors)
			{
				update_option('sendfeed_version', '1.9');
				sendfeed_alert("SendFeed Upgrade to 1.9 Completed Successfully!", true);
			}
		}
		
		
		// Upgrade to 2.0
		if($version < "2.0") {
			update_option('sendfeed_version', '2.0');
			sendfeed_alert("SendFeed Upgrade to 2.0 Completed Successfully!", true);
		}
		
		sendfeed_setup_crontask();

		return($version);
	}
	
	function sendfeed_debug($var)
	{
		echo "<pre>", htmlentities(print_r($var, true)), "</pre>";
	}

?>