		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: Preview</h2>
			
			<p>The following is how your message will most likely appear when it is received by email.<br/>
			<a href="<?=SENDFEED_URL; ?>">Return to main SendFeed management page.</a></p>
			
			<?php if(isset($msg)) { ?><div class='updated fade'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
			
			<div id="sendfeed-previews" class='sendfeed-form'>
				<h3>Text Version: </h3>
				<textarea><?php echo $feed_data[text_template]; ?></textarea>
				
				<h3>HTML Version: </h3>
				<div id="sendfeed-preview-html"><?php 
					if(sendfeed_regi("<body[^>]*>(.*)</body", $feed_data[html_template], $regs))
					{
						echo $regs[1];
					} 
					else
						echo "Unable to find body tags in html version.";
				?></div>
			</div>
			
			<div class='sendfeed-instructions'>
				<strong>Instructions:</strong><br/>
				<br/>
				You can test the functionality of the feed before actually activating it.  To send the latest feed
				items to a test email address, simply enter that address in the space provided, and click "Send".
			</div>
			<div style="clear: both;">&nbsp;</div>

		</div>
