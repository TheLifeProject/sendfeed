<?php

/*
 *
 * This object, when initialized will allow locking of the script using it, in a database or other
 * lock management system.  When the script using the object has been locked, the same script may
 * not be rerun until the lock expires.
 *
 */

global $sendfeed_script_lock;
if(!class_exists('sendfeed_scriptLock')) {
class sendfeed_scriptLock {

	var $db_table_name = 'sendfeed_scriptLock';

	var $db_link;
	var $error;
	var $debug_mode=true;

	var $name;
	var $lock_key;
	var $timestamp;
	var $timeout;
	var $locked = false;
	
	var $version = '1.0';


	// Constructor for PHP 5
	function __construct($name = __FILE__, $autolock = false)
	{
		global $wpdb, $table_prefix;
		
		// For older WP compatibility...
		if(!isset($wpdb->prefix) OR $wpdb->prefix == '' OR $wpdb->prefix == false) 
			$sendfeed_table_prefix = $table_prefix;
		else 
			$sendfeed_table_prefix = $wpdb->prefix;
	
		// Set table name
		$this->db_table_name = $sendfeed_table_prefix . 'sendfeed_scriptLock';

		// Set script name
		$this->name = $name;

		// Set script timestamp
		$this->timestamp = time();

		// Set script timeout
		$this->timeout = $this->timestamp + 360;
		
		// Check installation of appropriate tables
		if($this->check_install() === false)
		{
			$this->debug("Failed on installation of ScriptLock tables.", true);
			exit;
		}

		// Create a new lock key
		$this->lock_key = uniqid(rand(), true);

		// check AutoLock
		if($autolock)
		{
			if(!$this->lock()) {
				$this->debug("Attempted to engage autolock but failed.  Exiting.", true);
				exit;
			}
		}

		return true;
	}

	// Attempt to lock the script.  Return true on success, or false on failure.
	function lock($seconds=false)
	{
		global $wpdb;
		
		if($seconds)
		{
			$this->timestamp = time();
			$this->timeout = $this->timestamp + $seconds;
		}

		$this->debug("Attempting to lock script.");
		if(!$this->canlock()) {
			$this->debug("We were unable to lock the script at this time.", true);
			return(false);
		}

		$sql = "INSERT INTO " . $this->db_table_name . " (script_name, lock_timestamp, lock_timeout, lock_key) " .
				"VALUES ('" . $this->name . "', '" . $this->timestamp . "', '" . $this->timeout . "', '" . $this->lock_key . "');";
		$this->debug("Running SQL: $sql");
		$qres = $wpdb->query($sql);


		if($qres !== false) {
			$this->debug("Script locked successfully.");
			$this->locked = true;
		}
		else
		{
			$this->debug("Script was NOT able to be locked.", true);
			$this->locked = false;
		}

		return($this->locked);
	}
	
	// Function to return the reverse of checklock... basically for clarity purposes.  Returns true, if we can lock
	// the script, or false if we should not.
	function canlock()
	{
		if($this->checklock() === false AND $this->locked === false) return(true);
		else return(false);
	}

	// Check whether or not a lock already exists for this script.  If it does, we should not proceed because it means
	// the script has been locked by another process.
	function checklock()
	{
		global $wpdb;
		
		$this->debug("Checking to see if script is already locked.");

		$sql = "SELECT * FROM " . $this->db_table_name . " WHERE script_name = '" . $this->name . "' LIMIT 0,1;";
		$this->debug("Running SQL: $sql");
		$lock = $wpdb->get_row($sql, ARRAY_A);

		if($lock AND time() > $lock[lock_timeout]) {
			$this->debug("Expired lock was found. Unlocking...");
			if($this->unlock($lock['lock_key'])) {
				$this->debug("Successfully unlocked expired entry.");
				return(false);
			}
			else {
				$this->debug("Unable to unlock expired entry.");
				return(true);
			}
		}
		elseif($lock AND time() < $lock[lock_timeout]) {
			$this->debug("Lock was found.");
			return(true);
		}
		else {
			$this->debug("Lock NOT found.");
			return(false);
		}
	}
	
	function verifylock()
	{
		global $wpdb;
		
		$this->debug("Attempting to check if we still have a valid lock.");

		$sql = "SELECT * FROM " . $this->db_table_name . " WHERE script_name = '" . $this->name . "' AND lock_key = '" . $this->lock_key . "' LIMIT 0,1;";
		$lock = $wpdb->get_row($sql, ARRAY_A);

		if($lock AND time() > $lock[lock_timeout]) {
			$this->debug("Expired lock was found.");
			return(false);
		}
		elseif($lock AND time() < $lock[lock_timeout]) {
			$this->debug("Valid lock was found.");
			return(true);
		}
		else {
			$this->debug("Lock NOT found.");
			return(false);
		}
		
	}

	// Unlock the current object.  Should at very least, be run on script termination.
	function unlock($key = '')
	{
		global $wpdb;
		
		if($key == '') $key = $this->lock_key;
		
		$this->debug("Unlocking script now.");

		$sql = "DELETE FROM " . $this->db_table_name . " WHERE script_name = '" . $this->name . "' AND lock_key = '" . $key . "' LIMIT 1;";
		$this->debug("Running SQL: $sql");

		if($wpdb->query($sql) !== false) {
			$this->locked = false;
			$this->debug("Script unlocked successfully.");
			return(true);
		}
		else
		{
			$this->debug("Unable to unlock script.");
			return(false);
		}
	}
	
	function check_install()
	{
		global $wpdb;
		
		$installed_version = get_option("sendfeed_scriptLock");
		if(!$installed_version)
		{
			$this->debug("ScriptLock has not yet been installed... running install scripts.");
			$sql = 'CREATE TABLE IF NOT EXISTS `' . $this->db_table_name . '` (
			  `script_name` varchar(255) NOT NULL,
			  `lock_timestamp` bigint(20) unsigned zerofill NOT NULL,
			  `lock_timeout` bigint(20) unsigned zerofill NOT NULL,
			  `lock_key` varchar(255) NOT NULL,
			  PRIMARY KEY  (`script_name`)
			)';
			$result = $wpdb->query($sql);
			update_option('sendfeed_scriptLock', $this->version);
			return($result);
		}
		
		return(true);
	}

	function debug($msg = "", $forced = false)
	{
		if($this->debug_mode == true OR $forced) {
			sendfeed_log("SCRIPTLOCK DEBUGGING [" . $this->lock_key . "]: " . $msg);
		}
		
		return(true);
	}

	// Constructor for PHP 4
    function sendfeed_scriptLock($name = __FILE__, $autolock = false)
    {
    	$this->debug("Hit the PHP4 constructor");

		// register __destruct method as shutdown function since we're running as PHP4
		if(function_exists('register_shutdown_function')) register_shutdown_function(array(&$this, "__destruct"));
		else trigger_error("Shutdown function does not exist.", E_USER_ERROR);
		
		return $this->__construct($name, $autolock); // forward php4 to __construct
    }

	// Standard PHP 5 Destructor
	function __destruct()
	{
		if($this->locked)
		{
			$this->debug("Running destructor.");
			$this->unlock();
		}
		return true;
	}

}
}

?>