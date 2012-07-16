<?php

/*
 	function sendfeed_lockdown()
	{
		global $sendfeed_lock_key, $wpdb;
		
		// Create a new key if we don't already have one.
		if(!isset($sendfeed_lock_key)) {
			
#			sendfeed_alert("Creating new locking key.");
			
			$sendfeed_lock_key = sendfeed_create_lock();
			// If the creation of the lock failed, we need to find out whether or not there is 
			// already a key in place and if it is expired. 
			if(sendfeed_save_lock($sendfeed_lock_key) === false)
			{
				sendfeed_alert("Failed to create new lock... diagnosing.");
				
				$rlock = sendfeed_load_lock();
				
				// Check to see if the existing lock is expired.
				if($rlock['timestamp'] < time() - SENDFEED_LOCK_EXPIRY)
				{
					sendfeed_alert("Found expired lock in database.  Removing lock dated from: " . date("F j, Y, g:i a", $rlock['timestamp']));
					if(!sendfeed_unlock($rlock['lock_key']))
					{
						trigger_error("Unable to unlock sendfeed system for automated send.", E_USER_WARNING);
						return(false);
					}
					else
					{
						// We have unlocked the expired entry.  Now let's relock the database.
						if(sendfeed_save_lock($sendfeed_lock_key) === false)
						{
							trigger_error("Unable to save new lock to the sendfeed database..", E_USER_WARNING);
							return(false);
						}
						else
							return($sendfeed_lock_key);
					}
				}
				else
				{
					// We should terminate now.  The table is still locked, meaning that another process is currently running.
					sendfeed_alert('Another process already seems to be running.  Unable to run scheduled cron tick.');
					return(false);
				}
				
			}
			else
			{
				// Creation of the lock key was successful, we can return true.
				return($sendfeed_lock_key);
			}
			
			
		}
		else
		{
#			sendfeed_alert("Checking existing locking key.");
			
			// We appear to already have a lock in place, we should check to ensure that it is
			// still valid.
			$rlock = sendfeed_load_lock();
			
			// Check to ensure the stored lock is ours...
			if($rlock['lock_key'] == $sendfeed_lock_key 
			AND $rlock['timestamp'] >= time() - SENDFEED_LOCK_EXPIRY)
			{
				// All is well, we can proceed.
				return($sendfeed_lock_key);
			}
			elseif($rlock['lock_key'] == $sendfeed_lock_key 
			AND $rlock['timestamp'] < time() - SENDFEED_LOCK_EXPIRY)
			{
				// It's our lock but it's expired.  We should renew.
				if(sendfeed_unlock($sendfeed_lock_key) 
				AND sendfeed_save_lock($sendfeed_lock_key))
					return($sendfeed_lock_key);
				else
					return(false);
			}
			else
			{
				// The lock in the db is someone elses, but it is expired.  That's not good.  Get out now.
				return(false);
			}
		}
		
		
	}
	
	// Create a new sendfeed lock key and put it in the lock table.
	function sendfeed_create_lock()
	{
		global $sendfeed_lock_key;
		
#		sendfeed_alert("Creating new lock");
		
		// If we already have a lock key, don't allow for creation of a new one.
		if(isset($sendfeed_lock_key)) return($sendfeed_lock_key);
		
		// Create a new lock key
		$sendfeed_lock_key = uniqid(rand(), true);
		foreach($_SERVER as $tmp) $sendfeed_lock_key .= $tmp;
		
		return($sendfeed_lock_key);
	}
	
	// Check to ensure the lock in the table is the same one on record.
	function sendfeed_load_lock()
	{
		global $wpdb;
		
#		sendfeed_alert("Loading lock");
		
		// Load the current lock from the lock table.
		$sql = "SELECT * FROM " . SENDFEED_TABLE . "_lock WHERE `id`='1';";
		$recorded_lock = $wpdb->get_row($sql, ARRAY_A);
		
		return($recorded_lock);
	}	
	
	function sendfeed_save_lock($lock_key)
	{
		global $wpdb;
		
#		sendfeed_alert("Saving new lock.");
		
		// Attempt to insert the key into the lock table.
		$sql = "INSERT INTO " . SENDFEED_TABLE . "_lock (`id`, `timestamp`, `lock_key`) VALUES ('1', '" . time() . "', '$lock_key');";
		$result = $wpdb->query($sql, ARRAY_A);
		
		// Return false on failure, or the lock key on success.
		if($result === false) return(false);
		else return($lock_key);
	}
	
	function sendfeed_unlock($lock_key)
	{
		global $wpdb;
		
#		sendfeed_alert("Unlocking");
		
		$recorded_lock = sendfeed_load_lock();
		
		// IF the stored lock matches ours, remove it.
		if($recorded_lock['lock_key'] == $lock_key)
		{
			$sql = "DELETE FROM " . SENDFEED_TABLE . "_lock WHERE id = '" . $recorded_lock['id'] . "';";
			$result = $wpdb->query($sql, ARRAY_A);
			return($result);
		}
	}
	
 */
?>