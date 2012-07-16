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


function sendfeed_SendMail($From,$FromName,$To,$ToName,$Subject,$Text,$Html,$AttmFiles, $additional_headers=""){
 $OB="----=_OuterBoundary_000";
 $IB="----=_InnerBoundery_001";

 $Html=$Html?$Html:preg_replace("/\n/","<br>",$Text)
  or die("neither text nor html part present.");

  $From = preg_replace("/\r/", "", $From);
  $From = preg_replace("/\n/", "", $From);
  $FromName = preg_replace("/\r/", "", $FromName);
  $FromName = preg_replace("/\n/", "", $FromName);
  $To = preg_replace("/\r/", "", $To);
  $To = preg_replace("/\n/", "", $To);
  $ToName = preg_replace("/\r/", "", $ToName);
  $ToName = preg_replace("/\n/", "", $ToName);
  $Subject = preg_replace("/\r/", "", $Subject);
  $Subject = preg_replace("/\n/", "", $Subject);



 $Text=$Text?$Text:"Sorry, but you need an html mailer to read this mail.";
 $From or die("sender address missing");
 $To or die("recipient address missing");

 $headers ="From: ".$FromName." <".$From.">\n";
# $headers.="To: ".$ToName." <".$To.">\n";	// commented out to allow support for multiple recipients through the $To variable in the mail() command.
 $headers.="Reply-To: ".$FromName." <".$From.">\n";
 $headers.="MIME-Version: 1.0\n";
 $headers.="Content-Type: multipart/mixed;\n\tboundary=\"".$OB."\"\n";

 if($additional_headers) $headers .= trim($additional_headers) . "\n";

 //Messages start with text/html alternatives in OB
 $Msg ="This is a multi-part message in MIME format.\n";
 $Msg.="\n--".$OB."\n";
 $Msg.="Content-Type: multipart/alternative;\n\tboundary=\"".$IB."\"\n\n";

 //plaintext section
 $Msg.="\n--".$IB."\n";
 $Msg.="Content-Type: text/plain;\n\tcharset=\"UTF-8\"\n";
 $Msg.="Content-Transfer-Encoding: 7bit\n\n";
 // plaintext goes here
 $Msg.=$Text."\n\n";

 // html section - edited to remove base 64
 $Msg.="\n--".$IB."\n";
 $Msg.="Content-Type: text/html;\n\tcharset=\"UTF-8\"\n";
 $Msg.="Content-Transfer-Encoding: 7bit\n\n";
 // html goes here
 $Msg.=$Html."\n\n";

 // end of IB
 $Msg.="\n--".$IB."--\n";

 // attachments
 if($AttmFiles){
  foreach($AttmFiles as $AttmFile){
   $patharray = explode ("/", $AttmFile);
   $FileName=$patharray[count($patharray)-1];
   $Msg.= "\n--".$OB."\n";
   $Msg.="Content-Type: application/octetstream;\n\tname=\"".$FileName."\"\n";
   $Msg.="Content-Transfer-Encoding: base64\n";
   $Msg.="Content-Disposition: attachment;\n\tfilename=\"".$FileName."\"\n\n";

   //file goes here
   $fd=fopen ($AttmFile, "r");
   $FileContent=fread($fd,filesize($AttmFile));
   fclose ($fd);
   $FileContent=chunk_split(base64_encode($FileContent));
   $Msg.=$FileContent;
   $Msg.="\n\n";
  }
 }

 //message ends
 $Msg.="\n--".$OB."--\n";

	return(mail("$To",$Subject,$Msg,$headers));

}


	function sendfeed_browse_rss($path="/", $xml="<rss></rss>", $offset=0)
	{
		$apath = explode("/", $path);
		$aoffset = $offset;

		foreach($apath as $section)
		{
			$section = trim($section);
			if($section == "") $section = "rss";
#			echo "Processing: $section<br/>\n";

			if(sendfeed_regi("^([a-z]+)\[([0-9]+)\]$", $section, $regs)) {
#				print_r($regs);

				$section = $regs[1];
				$index = $regs[2];
			}
			else
			{
				$index = 1;
			}

			for($i=1; $i<=$index; $i++)
			{
#				echo "Run $i<br/>\n";
				$counter = 0;
				do {
					$begin = strpos("$xml", "<$section", $aoffset);
					$clip = substr($xml, $begin, strlen($section)+25);
#					echo "CHECKING AGAINST " . $clip . "<br/>";
					$aoffset = $begin+1;
				} while(sendfeed_regi("^<" . $section . "[ |>]", $clip) === false AND $counter++ < 10);

				if($begin === false OR sendfeed_regi("^<" . $section . "[ |>]", $clip) === false) return(false);
				else $begin = strpos($xml, ">", $begin) + 1;

#				echo "Found!<br/>\n";
				$aoffset = $begin;

				$end = strpos($xml, "</$section", $aoffset);
				$section_xml = substr($xml, $begin, $end-$begin);

			}
			$xml = $section_xml;
			$aoffset = $offset;
#			echo "RESULT XML: <pre>" . htmlentities($xml) . "</pre>";
		}

		if(substr($xml, 0, 9) == "<![CDATA[" AND substr($xml, strlen($xml)-3, 3) == "]]>")
		{
#			echo "RESULT XML: <pre>" . htmlentities($xml) . "</pre>";
			$xml = substr($xml, 9, strlen($xml) - 12);
#			exit;
		}

		return($xml);
	}
	
	/*
	 * Function to load xml and create html and text versions of the content.
	 */
	function sendfeed_create_content($feed_data)
	{
		global $sendfeed_current_feed_id;
		$sendfeed_current_feed_id = $feed_data['id'];
		
		// Load the feed XML data.
		$client = new sendfeed_httpClient();
		$xml = $client->request($feed_data[feed_url]);

		if(trim($xml) == "") {
			debug('XML was empty.');
			sendfeed_log("The content of the XML downloaded seems to be empty.", 'WARNING', $feed_data['id']);
			trigger_error("The content of the XML downloaded seems to be empty." .
					" We attempted to load data from the following url: " . $feed_data[feed_url] .
					" We'll have to abort.  The data received was as follows:" . $xml);
			return(false);
		}
		
		// Parse out the pieces needed.
		$pieces[SITE_LINK] = sendfeed_browse_rss("channel/link", $xml);
		$pieces[SITE_TITLE] = sendfeed_browse_rss("channel/title", $xml);
		$pieces[ARTICLE_AUTHOR] = sendfeed_browse_rss("channel/item[1]/dc:creator", $xml);
		$pieces[ARTICLE_LINK] = sendfeed_browse_rss("channel/item[1]/link", $xml);
		$pieces[ARTICLE_COMMENTS] = sendfeed_browse_rss("channel/item[1]/comments", $xml);
		$pieces[ARTICLE_TITLE] = sendfeed_browse_rss("channel/item[1]/title", $xml);
		$pieces[ARTICLE_DESCRIPTION] = sendfeed_browse_rss("channel/item[1]/description", $xml);
		$pieces[ARTICLE_CONTENT] = sendfeed_browse_rss("channel/item[1]/content:encoded", $xml);
		
		// check to ensure we actually got something.
		if(trim($pieces[ARTICLE_TITLE]) == "" 
		AND trim($pieces[ARTICLE_CONTENT]) == "") 
		{
			trigger_error("This doesn't seem to be valid XML data." .
					" We attempted to load data from the following url: " . $feed_data[feed_url] .
					" We'll have to abort.  The data received was as follows:" . $xml);
			return(false);
		}

		// Merge pieces to a variable, which we can compare against the database to be sure we haven't sent the
		// same thing out twice.
		$feed_data[duplicate_check] = "";
		foreach($pieces AS $key=>$value)
			$feed_data[duplicate_check] .= "$key: $value\n\n";

		if($feed_data[duplicate_check] == $feed_data[last_sent_data]) 
			$feed_data[duplicate_content] = true;
		else 
			$feed_data[duplicate_content] = false;

		// Additional pieces from the xml
		for($i=1; $i<=10; $i++)
		{
			$pieces["ARTICLE_AUTHOR_$i"] = sendfeed_browse_rss("channel/item[$i]/dc:creator", $xml);
			$pieces["ARTICLE_LINK_$i"] = sendfeed_browse_rss("channel/item[$i]/link", $xml);
			$pieces["ARTICLE_COMMENTS_$i"] = sendfeed_browse_rss("channel/item[$i]/comments", $xml);
			$pieces["ARTICLE_TITLE_$i"] = sendfeed_browse_rss("channel/item[$i]/title", $xml);
			$pieces["ARTICLE_DESCRIPTION_$i"] = sendfeed_browse_rss("channel/item[$i]/description", $xml);
			$pieces["ARTICLE_CONTENT_$i"] = sendfeed_browse_rss("channel/item[$i]/content:encoded", $xml);
		}

		// Additional pieces which could be used...
		$pieces[NICEDATE] = date("F jS, Y");
		$pieces[DATESTAMP] = date("m/d/Y");
		$pieces[TIMESTAMP] = date("g:i a");


		// Run string replacements
		foreach($pieces as $key=>$value)
		{
			$search = "<$key>";
			$replace = stripslashes($value);

			$feed_data[text_template] = str_replace($search, $replace, $feed_data[text_template]);
			$feed_data[html_template] = str_replace($search, $replace, $feed_data[html_template]);
			$feed_data[from_name] = str_replace($search, $replace, $feed_data[from_name]);
			$feed_data[from_email] = str_replace($search, $replace, $feed_data[from_email]);
			$feed_data[subject] = str_replace($search, $replace, $feed_data[subject]);
		}
		
		
		$feed_data[html_template] = sendfeed_fix_content($feed_data[html_template]);
		$feed_data[text_template] = sendfeed_fix_text_content($feed_data[text_template]);
		$feed_data[subject] = sendfeed_fix_text_content($feed_data[subject]);
		
		return($feed_data);
	}
	
	/*
	 * Function to send the feed email.
	 */
	function sendfeed_send_email($feed_data, $email_address="", $additional_headers="true")
	{
		global $wpdb;
		global $sendfeed_current_feed_id;

		// Die with error if there is no feed data array.
		if(!$feed_data['id'])	{
			trigger_error("No feed ID detected.", E_USER_WARNING);
			return(false);
		}
		
		$sendfeed_current_feed_id = $feed_data['id'];
		sendfeed_log("Preparing to send feed: [" . $feed_data['id'] . "]" . $feed_data['feed_name'], 'NOTICE', $feed_data['id']);

		// Return false if a deactivated feed tries to send to the default feed recipient.
		if($feed_data[activated] == 0 
		AND !$email_address 
		AND $feed_data[frequency] != 'Send manually') 
		{
			sendfeed_log("This feed is not activated.  I was unable to send it out.", 'WARNING', $feed_data['id']);
			trigger_error("This feed is not activated.  I was unable to send it out.", E_USER_WARNING);
			return(false);
		}

		// Set the active email address to the default email address, if it hasn't already been specified.
		// At this point, we would be doing a LIVE send, not a test.
		if(!$email_address) $email[to] = $feed_data[recipient];
		else $email[to] = $email_address;
		sendfeed_log("Setting recipiant email address to: " . $email[to], 'NOTICE', $feed_data['id']);

		// Load the content related pieces.
		$feed_data = sendfeed_create_content($feed_data);
		
		// Ensure we got valid data...
		if($feed_data === false)
		{
			sendfeed_log("We were not able to load valid data for the feed.", 'WARNING', $feed_data['id']);
			trigger_error("We were not able to load valid data for the feed.", E_USER_WARNING);
			return(false);
		}

		// Ensure that we don't send duplicates when in LIVE mode.
		if(!$email_address)
		{
			if($feed_data[duplicate_content] == true)
			{
				// In this case, we are sending out the exact same thing we sent out last time,
				// and we should terminate the script.
				sendfeed_log("We already sent this data out last time.  We probably shouldn't send it again.", 'NOTICE', $feed_data['id']);
				//trigger_error("We already sent this data out last time.  We probably shouldn't send it again.");
				return(false);
			}
			else
			{
				// Otherwise, update the db to contain today's devo.
				$sql = "UPDATE " . SENDFEED_TABLE . " SET last_sent_data='" . sendfeed_addslashes($feed_data[duplicate_check]) . "' WHERE id = '" . $feed_data[id] . "';";
				$result = $wpdb->query($sql);
				if($result === false) {
					sendfeed_log("Unable to update table with current newsletter data.", 'ERROR', $feed_data['id']);
					trigger_error("Unable to update table with current newsletter data.", E_USER_ERROR);
				}
			}
		}

		// Set up initial email text and html
		$email[text] = $feed_data[text_template];
		$email[html] = $feed_data[html_template];
		$email[from_name] = $feed_data[from_name];
		$email[from_email] = $feed_data[from_email];
		$email[subject] = $feed_data[subject];

		if($additional_headers == "true") $email[additional_headers] = $feed_data[additional_headers];
		
		// Log the message to be sent for recording purposes.
		sendfeed_log("Sending email:\n" .
				"\nFrom Name: " . $email[from_name] .
				"\nFrom Email: " . $email[from_email] .
				"\nTo: " . $email[to] .
				"\nSubject: " . $email[subject] .
				"\nAdditional Headers: " . $email[additional_headers], 'NOTICE', $feed_data['id']);
		
		// Check the lock one final time before sending, if we are in live mode....
		if(class_exists('sendfeed_scriptLock') AND !$email_address) {	
			global $sendfeed_script_lock;
			if(!$sendfeed_script_lock->verifylock())
			{
				sendfeed_alert('Unable to obtain valid lock.  Aborting...', false);
				return(false);
			}
		}
		
		// Code added by James Warkentin July 15, 2009 to ensure that neither text nor HTML contains
		// lines exceeding 500 characters in length.  This was done to avoid a Lyris bug.
		$email[text] = sendfeed_wordWrapIgnoreHTML($email[text], 400, "\n");
		$email[html] = sendfeed_wordWrapIgnoreHTML($email[html], 400, "\n");
		

		// Send the Email
		$result = sendfeed_SendMail(
			$email[from_email],
			$email[from_name],
			$email[to],
			"",
			$email[subject],
			$email[text],
			$email[html],
			"",
			$email[additional_headers]
		);
		
		if($result !== false)
			sendfeed_log("Successfully sent email.", 'NOTICE', $feed_data['id']);
		else
		{
			sendfeed_log("Failed to send email.", 'WARNING', $feed_data['id']);
			trigger_error("Failed to send email.", E_USER_WARNING);
		}
		
		return($result);
	}

if (!function_exists("htmlspecialchars_decode")) {
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT) {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
}

function sendfeed_htmlspecialchars_decode_php4 ($str, $quote_style = ENT_COMPAT) {
    return strtr($str, array_flip(sendfeed_get_html_translation_table_CP1252(HTML_ENTITIES, $quote_style)));
}

function sendfeed_get_html_translation_table_CP1252($type=HTML_ENTITIES, $quote_style= ENT_COMPAT) {
    $trans = get_html_translation_table($type);
    $trans[chr(130)] = '&sbquo;';    // Single Low-9 Quotation Mark
    $trans[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
    $trans[chr(132)] = '&bdquo;';    // Double Low-9 Quotation Mark
    $trans[chr(133)] = '&hellip;';    // Horizontal Ellipsis
    $trans[chr(134)] = '&dagger;';    // Dagger
    $trans[chr(135)] = '&Dagger;';    // Double Dagger
    $trans[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
    $trans[chr(137)] = '&permil;';    // Per Mille Sign
    $trans[chr(138)] = '&Scaron;';    // Latin Capital Letter S With Caron
    $trans[chr(139)] = '&lsaquo;';    // Single Left-Pointing Angle Quotation Mark
    $trans[chr(140)] = '&OElig;    ';    // Latin Capital Ligature OE
    $trans[chr(145)] = '&lsquo;';    // Left Single Quotation Mark
    $trans[chr(146)] = '&rsquo;';    // Right Single Quotation Mark
    $trans[chr(147)] = '&ldquo;';    // Left Double Quotation Mark
    $trans[chr(148)] = '&rdquo;';    // Right Double Quotation Mark
    $trans[chr(149)] = '&bull;';    // Bullet
    $trans[chr(150)] = '&ndash;';    // En Dash
    $trans[chr(151)] = '&mdash;';    // Em Dash
    $trans[chr(152)] = '&tilde;';    // Small Tilde
    $trans[chr(153)] = '&trade;';    // Trade Mark Sign
    $trans[chr(154)] = '&scaron;';    // Latin Small Letter S With Caron
    $trans[chr(155)] = '&rsaquo;';    // Single Right-Pointing Angle Quotation Mark
    $trans[chr(156)] = '&oelig;';    // Latin Small Ligature OE
    $trans[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
    $trans[chr(187)] = '&#187;';    // Latin Capital Letter Y With Diaeresis
    ksort($trans);
    return $trans;
}


function sendfeed_fix_content($content)
{
	
		// Check to see if the data is already html-encoded.  If so, strip out the encoding.  added Apr. 24, 2009
		if(preg_match("!&#?[a-z0-9]+;!is", $content))
			$content = html_entity_decode($content, ENT_NOQUOTES, 'UTF-8');
		
		// Re-encode the template using UTF-8 and no quotes.  added Apr. 24, 2009
		$content = htmlentities($content, ENT_NOQUOTES, 'UTF-8');
		
		// Reconvert critical HTML entities to original form.  added Apr. 24, 2009
		$content = str_replace(
			array('&nbsp;', '&lt;', '&gt;', '&#8220;', '&#8221;', '&#8217;'), 
			array(' ', '<', '>', '"', '"', '...', '\''), 
			$content);
	
			
		$content = str_replace("–", "--", $content);
		$content = str_replace("’", "'", $content);
		$content = str_replace("…", "...", $content);
		$content = str_replace("“", "\"", $content);
		$content = str_replace("”", "\"", $content);
		
		// Removed due to chinese encoding problems.
		#$content = strtr($content, "       ï¿½�                             ", "                                                                                    ");

	return($content);
}

function sendfeed_fix_text_content($content)
{
	$content = sendfeed_fix_content($content);
	
	$content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

#	$content = htmlspecialchars_decode($content, ENT_QUOTES);

#	$content = sendfeed_htmlspecialchars_decode_php4($content, ENT_QUOTES);
					   
	// Run Text Transformations as Necessary.
	$content = sendfeed_regi_replace("<br[^>]*>", "\r\n", $content);
	$content = sendfeed_regi_replace("<p[^>]*>", "\r\n", $content);

	$content = sendfeed_regi_replace("<a href=[\"']*([^\"']+)[\"']*[^>]*>([^<]*)", "\\2 (\\1)", $content);

	$content = strip_tags($content);
	
	return($content);
}



/**********************************************************
** Better Wordwrap coded iwrap
**
** Author..: leapinglangoor [ leapinglangoor@yahoo.co.in ]
** Date....: 12th Aug 2004
** Version.: v2
** URL Downloaded: http://www.weberdev.com/get_example-4157.html
**
** Desc....: Ok, what this wordwrap is more or less the
**        PHP wordwrap; only it  does not mess up any
**        HTML tags when open. Made some bug fixes and
**        finally ready to go public.
**        $str - string that needs processing.
**        $cols - How long the word/string should be to be cut
**        $cut - what you want to insert after every segment.
**
**********************************************************/

function sendfeed_iwrap( $str, $cols, $cut )
{
    $tag_open = '<';
    $tag_close = '>';
    //$count = ! 0;
    $in_tag = 0;
    $str_len = strlen( $str );
    $segment_width = 0;

    for( $i=0; $i<=$str_len; $i++  )
    {
        if( $str[$i] == $tag_open )
        {
            $in_tag++;

        }
        elseif( $str[$i] == $tag_close )
        {
            if( $in_tag > 0 )
            {
                $in_tag--;
            }
        }
        else
        {
            if( $in_tag == 0 )
            {
                $segment_width++;
                if( ( $segment_width > $cols ) /*&& ( $str[$i] != " " )*/ )
                {
                    $str = substr( $str,0,$i ).$cut.substr( $str,$i+1,$str_len-1 );
                    $i += strlen( $cut );
                    $str_len = strlen( ! $str );
                    $segment_width = 0;
                }
            }
        }
    }

    return $str;

}

function sendfeed_wordWrapIgnoreHTML($string, $length = 45, $wrapString = "\n")
   {
   	 $count = 0;
     $wrapped = '';
     $word = '';
     $html = false;
     $string = (string) $string;
     for($i=0;$i<strlen($string);$i+=1)
     {
       $char = $string[$i];
      
       /** HTML Begins */
       if($char === '<')
       {
         if(!empty($word))
         {
           $wrapped .= $word;
           $word = '';
         }
        
         $html = true;
         $wrapped .= $char;
         $count++;
       }
      
       /** HTML ends */
       elseif($char === '>')
       {
         $html = false;
         $wrapped .= $char;
         $count++;
       }
      
       /** If this is inside HTML -> append to the wrapped string */
       elseif($html)
       {
         $wrapped .= $char;
         $count++;
       }
      
       /** Whitespace characted / new line */
       elseif($char === ' ')
       {
         $wrapped .= $word.$char;
         $word = '';
         $count++;
         if($count > $length)
         {
         	$wrapped .= $wrapString;
            $count = 0;
         }
       }
       
       elseif($char === "\n")
       {
         $wrapped .= $word.$char;
         $word = '';
       	 $count = 0;       
       }
      
       /** Check chars */
       else
       {
         $word .= $char;
         $count++;
         //if(strlen($word) > $length)
         //{
         //  $wrapped .= $word.$wrapString;
         //  $word = '';
         //  $count = 0;
         //}
       }
     }

    if($word !== ''){
        $wrapped .= $word;
    }
    
     return $wrapped;
   }
	
   
   /*
    * Replacement functions for ereg based searches
    */
	function sendfeed_reg($pattern, $string, &$regs = array(), $modifiers = 's')
	{
		#debug('Search and Replace: ', $pattern, $string, $regs, $modifiers);
		$pattern = '#' . str_replace('#', '\\#', $pattern) . '#' . $modifiers;
		$result = preg_match($pattern, $string, $regs);
		return($result);
	}
	
	function sendfeed_regi($pattern, $string, &$regs = array())
	{
		return(sendfeed_reg($pattern, $string, $regs, 'is'));
	}
	
	function sendfeed_reg_replace($pattern, $replacement, $string, $modifiers = 's')
	{
		return preg_replace('#' . str_replace('#', '\\#', $pattern) . '#' . $modifiers, $replacement, $string);
	}
	
	function sendfeed_regi_replace($pattern, $replacement, $string)
	{
		return sendfeed_reg_replace($pattern, $replacement, $string, 'is');
	}
	
	function sendfeed_addslashes($text)
	{
		$text = stripslashes($text);
		$text = addslashes($text);
		return($text);
	}
	
	function sendfeed_stripslashes($text)
	{
		$text = stripslashes($text);
		return($text);
	}
	
	

	/**
	 * Get a paginated navigation bar
	 * 
	 * This function will create and return the HTML for a paginated navigation bar
	 * based on the total number of results passed in $num_results, and the value 
	 * found in $_GET['pageNumber'].  The programmer simply needs to call this function
	 * with the appropriate value in $num_results, and use the value in $_GET['pageNumber']
	 * to determine which results should be shown.
	 * Creates a list of pages in the form of:
	 * 1 .. 5 6 7 .. 50 51 .. 100
	 * (in this case, you would be viewing page 6)
	 * 
	 * @global   int     $_GET['pageNumber'] is the current page of results being displayed.
	 * @param    int     $num_results is the total number of results to be paged through.
	 * @param    int     $num_per_page is the number of results to be shown per page.
	 * @param    bool    $show set to true to write output to browser.
	 * 
	 * @return   string  Returns the HTML code to display the nav bar. 
	 * 
	 */
	function sendfeed_paged_nav($num_results, $num_per_page=10, $show=false)
	{
	    // Set this value to true if you want all pages to be shown,
	    // otherwise the page list will be shortened.
	    $full_page_list = false; 
	        
	    // Get the original URL from the server.
	    $url = $_SERVER['REQUEST_URI'];
	    
	    // Initialize the output string.
	    $output = '';
	    
	    // Remove query vars from the original URL.
	    if(preg_match('#^([^\?]+)(.*)$#isu', $url, $regs))
	        $url = $regs[1];
	    
	    // Shorten the get variable.
	    $q = $_GET;
	    
	    // Determine which page we're on, or set to the first page.
	    if(isset($q['pageNumber']) AND is_numeric($q['pageNumber'])) $page = $q['pageNumber'];
	    else $page = 1;
	    
	    // Determine the total number of pages to be shown.
	    $total_pages = ceil($num_results / $num_per_page);
	    
	    // Begin to loop through the pages creating the HTML code.
	    for($i=1; $i<=$total_pages; $i++)
	    {
	        // Assign a new page number value to the pageNumber query variable.
	        $q['pageNumber'] = $i;
	        
	        // Initialize a new array for storage of the query variables.
	        $tmp = array();
	        foreach($q as $key=>$value)
	            $tmp[] = "$key=$value";
	        
	        // Create a new query string for the URL of the page to look at.
	        $qvars = implode("&amp;", $tmp);
	        
	        // Create the new URL for this page.
	        $new_url = $url . '?' . $qvars;
	        
	        // Determine whether or not we're looking at this page.
	        if($i != $page)
	        {
	            // Determine whether or not the page is worth showing a link for.
	            // Allows us to shorten the list of pages.
	            if($full_page_list == true
	                OR $i == $page-1
	                OR $i == $page+1
	                OR $i == 1
	                OR $i == $total_pages
	                OR $i == floor($total_pages/2)
	                OR $i == floor($total_pages/2)+1
	                )
	                {
	                    $output .= "<a href='$new_url'>$i</a> ";
	                }
	                else
	                    $output .= '. ';
	        }
	        else
	        {
	            // This is the page we're looking at.
	            $output .= "<strong>$i</strong> ";
	        }
	    }
	    
	    // Remove extra dots from the list of pages, allowing it to be shortened.
	    $output = preg_replace('#(\. ){2,}#is', ' .. ', $output);
	    
	    // Determine whether to show the HTML, or just return it.
	    if($show) echo $output;
	    
	    return($output);
	}
	
	
	
	/**
	 * Build a url based on permitted query vars passed to the function.
	 * 
	 * @param $add array containing query vars to add to the query request.
	 * @param $qvars array containing query vars to keep from the old query request.
	 * 
	 * @return string containing the new URL
	 */
	function sendfeed_build_url($add = array(), $qvars = array())
	{
		// Get the original URL from the server.
		$url = $_SERVER['REQUEST_URI'];
		
		// Remove query vars from the original URL.
		if(preg_match('#^([^\?]+)(.*)$#isu', $url, $regs))
		$url = $regs[1];
		
		// Shorten the get variable.
		$q = $_GET;
		
		// Initialize a new array for storage of the query variables.
		$tmp = array();
		foreach($qvars as $key)
			@$tmp[] = "$key=" . urlencode($q[$key]);
		
		foreach($add as $key=>$value)
			@$tmp[] = "$key=" . urlencode($value);
		
		// Create a new query string for the URL of the page to look at.
		$qvars = implode("&", $tmp);
		
		// Create the new URL for this page.
		$new_url = $url . '?' . $qvars;
		
		return($new_url);
	}
	
?>