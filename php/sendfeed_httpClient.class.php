<?php

if(!class_exists('sendfeed_httpClient')) {
class sendfeed_httpClient {
	
	var $debug_mode = true;
	var $initialized = false;
	
	// Connection specific variables.
	var $path;
	var $protocol;
	var $hostname;
	var $request;
	var $query;
	
	var $fp;
	var $errno;
	var $errstr;
	
	var $redirect_counter = 0;
	var $max_redirects = 2;
	
	var $headers;
	var $data;
	
	// Send request to server
	function request($path, $out=false, $username=false, $password=false)
	{
		$this->debug("Requesting $path");
		
		// Initialize the variables from the path given
		if(!$this->parse_path($path, $username, $password))
			$this->debug("Unable to properly parse the url requested: $url");
		
		// Connect to the server
		if(!$this->connect())
			$this->debug("Unable to complete connection to server.");
		
		// Create a default request if none was given.
		if(!$out) {
		    $out = "GET " . $this->request . $this->query . " HTTP/1.1\r\n";
		    $out .= "Host: " . $this->hostname . "\r\n";
		    $out .= "Connection: Close\r\n\r\n";
		}
		
		// Make the request, and store the response.
		$this->data = $this->readwrite($out);
		
		// Disconnect when we're done.
		if(!$this->disconnect())
			$this->debug("Unable to disconnect from server.");
		
		// Return the data that was received.
		return($this->data);
	}

	// Function to parse out components of the path into useable pieces.
	function parse_path($path, $username=false, $password=false)
	{
		$this->reset();
		// Test URL and ensure that it is valid.
		if(false !== $username AND false !== $password)
			$match = "^([a-z]{2,10})\://" . $username . "\:" . $password . "([a-z0-9\.\-]+)/?([^\?]*)(.*)$";
		else
			$match = "^([a-z]{2,10})\://([^\:/\?]+)(/?[^\?]*)(.*)$";
		
		
		// Return false if the path does not look like a url.
		if(!sendfeed_regi($match, $path, $regs)) {
			$this->debug("Path given does not appear to be a valid URL.");
			return(false);
		}
		else {
			list($this->path, $this->protocol, $this->hostname, $this->request, $this->query) = $regs;
	
			// Determine port protocol.
			switch(strtoupper($this->protocol))
			{
				case "HTTPS":
					$this->port = 443;
					break;
	
				case "FTP":
					$this->port = 21;
					break;
	
				default:
					$this->port = 80;
					break;
			}
			
			$this->debug("Initialized with the following connection details:\n" .
					"Path: " . $this->path . "\n" .
					"Protocol: " . $this->protocol . "\n" .
					"Port: " . $this->port . "\n" .
					"HostName: " . $this->hostname . "\n" .
					"Request: " . $this->request . "\n" .
					"Query: " . $this->query . "\n");
			
			$this->initialized = true;
		}
		
		return(true);
	}

	// Make the connection to the host.
	function connect()
	{
		// Check to ensure we're properly initialized.
		if(!$this->initialized) {
			$this->debug("Unable to connect.  Connection details are not yet properly initialized.");
			return(false);
		}
		
		// Check to see if we already have a file pointer
		if($this->fp != false) {
			$this->debug("Connection already exists... disconnecting first.");
			$this->disconnect();
		}
		
		// Make connection to the server, if one doesn't already exist.
		$this->debug("Connecting to {$this->hostname} on port {$this->port}.");
		$this->fp = fsockopen($this->hostname, $this->port, $this->errno, $this->errstr, 10);
		
		//Error checking to ensure we have a valid connection
		if (!$this->fp) {
		    $this->debug("Connection failed!  Error Details: " . $this->errstr . " (" . $this->errno . ")");
		    return(false);
		} else {
			$this->debug("Host connected.");
			return(true);
		}
	}

	// Disconnect from the host.
	function disconnect()
	{
		// Check to see if we already have a file pointer
		if($this->fp == false) {
			$this->debug("Connection doesn't exist.  Nothing to disconnect.");
			return(true);
		}
		
		if(fclose($this->fp))
		{
			unset($this->fp);
			$this->debug("Host disconnected.");
			return(true);
		}

	}

	function readwrite($out)
	{
		// Check to see if we already have a file pointer
		if($this->fp == false) {
			$this->debug("Connection doesn't exist.");
			return(false);
		}

	    $this->debug("SENT: " . htmlentities($out));
		fwrite($this->fp, $out);
		
		// Get the headers
		while (!feof($this->fp)) {
		    $data .= fgets($this->fp);

			if(substr($data, -4)=="\r\n\r\n") {
				$this->debug("Received headers: " . htmlentities($data));
				$this->headers = $this->get_headers($data);
				
				// Check to see if we have a redirect.
				if( ($this->headers['STATUS'] >= 300 AND $this->headers['STATUS'] < 400) AND $this->headers['LOCATION'] != '') {
					
					if($this->redirect_counter++ < $this->max_redirects)
					{
						$this->debug("Redirecting to: " . $this->headers['LOCATION']);
						$this->request($this->headers['LOCATION']);
					}
					
				}
				
				break;
				unset($data);
					
			}
		}

		// If we don't yet have content data, get it now
		if(!$this->data)
		{
			unset($data);
			
			// Check whether we have to get the data as chunked or not...
			if($this->headers['TRANSFER-ENCODING'] != 'chunked')
			{
				while (!feof($this->fp)) 
				{
					$tmp = fgets($this->fp);
					if($tmp !== false)
				    	$data .= $tmp;
				    else
				    {
				    	$this->debug("Problem reading feed.");
				    	$this->debug(print_r($this, true));
				    	break;
				    }
				}
			}
			else
			{
				$this->debug('Reading chunked data...');
				
				do {
					// Determine total size of chunk.
					$chunksize = fgets($this->fp);
#					$this->debug("Hex ChunkSize: $chunksize");
					
					$chunksize = hexdec($chunksize);
#					$this->debug("Dec ChunkSize: $chunksize");
					
					$tmp = "";
					$remaining = $chunksize;
					
					// Read data until we have hit the chunk size.
					while($remaining > 0)
					{
						$tmp .= fread($this->fp, $remaining);
						$size_read = strlen($tmp);
						$remaining = $chunksize - $size_read;
					} 
					
					$discard = fgets($this->fp);
					
					// Add the temporary data to the main data.
					$data .= $tmp;
					
				} while($chunksize > 0);

			}
			
			return($data);
		}
		else
			return($this->data);
	}

	function get_headers($header)
	{
		$header = "\r\n" . trim($header);

		// Extract headers to individual array variables.
		$pattern = "#\r?\n([a-z0-9\-]+)\:(.*)\r?\n[a-z0-9\-]+\:#isU";
		$offset = 0;

		while(preg_match($pattern, $header, $regs, PREG_OFFSET_CAPTURE, $offset))
		{
			$headers[strtoupper($regs[1][0])] = trim($regs[2][0]);
			$offset = $regs[0][1]+5;
		}

		if(preg_match("#\r?\n([a-z0-9\-]+)\:(.*)$#isU", $header, $regs, PREG_OFFSET_CAPTURE, $offset))
		{
			$headers[strtoupper($regs[1][0])] = trim($regs[2][0]);
		}
		
		if(sendfeed_regi("HTTP/([^ ]+) +([0-9]+)", $header, $regs))
		{
			$headers['HTTPVER'] = trim($regs[1]);
			$headers['STATUS'] = trim($regs[2]);
		}
	
		return($headers);
		
	}

	function reset()
	{
		unset(
			$this->headers,
			$this->data
			);
	}



	// Constructor for PHP 5
	function __construct($url='', $username=false, $password=false)
	{
		if($url) {
			$this->debug("Initializing with url: $url");
			$this->request($url);
		}
	}

	// Constructor for PHP 4
    function sendfeed_httpClient($url='', $username=false, $password=false)
    {
    	$this->debug("Hit the PHP4 constructor");

		// register __destruct method as shutdown function since we're running as PHP4
		if(function_exists('register_shutdown_function')) register_shutdown_function(array(&$this, "__destruct"));
		else trigger_error("Shutdown function does not exist.", E_USER_ERROR);
		
		return $this->__construct($url, $username, $password); // forward php4 to __construct
    }

	// Standard PHP 5 Destructor
	function __destruct()
	{
		return true;
	}
	
	// Do something with class debugging messages.
	function debug($msg = "", $forced = false)
	{
		global $sendfeed_current_feed_id;
		if($this->debug_mode == true OR $forced) {
			#echo "<pre>HTTPCLIENT: $msg</pre><br/>\n";
			sendfeed_log("HTTPCLIENT DEBUGGING: " . $msg, 'NOTICE', $sendfeed_current_feed_id);
		}
		
		return(true);
	}
	
    
}
}
?>