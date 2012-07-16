		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: Edit Feed</h2>
			<?php if($msg) { ?><div class='updated'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
			<form action="<?=SENDFEED_URL; ?>&sendfeed_page=edit_feed" method="POST" class='sendfeed-form'>
				<div class="sendfeed-option">
					<strong>Feed Name:</strong>
					<input type="text" name="feed_name" class="" value="<?=$feed[feed_name]; ?>" />
					<small>Enter the name of the feed as you would like it to show up in your feed list.</small>
				</div>
				<div class="sendfeed-option">
					<strong>Feed URL:</strong>
					<input type="text" name="feed_url" class="" value="<?=$feed[feed_url]; ?>" />
					<small>Enter the URL of the feed to be used.</small>
				</div>
				<div class="sendfeed-option">
					<strong>Recipient Email:</strong>
					<input type="text" name="recipient" class="" value="<?=$feed[recipient]; ?>" />
					<small>Enter the email address that you want to send messages to when the feed is updated.</small>
				</div>
				<div class="sendfeed-option">
					<strong>Additional Headers:</strong>
					<br class="clear"/><textarea class="sendfeed-small-textarea" name="additional_headers"><?=$feed[additional_headers]; ?></textarea>
					<small>Add additional headers in the same way as they should appear in the email header as follows.<br/>
					BCC: email@domain.com<br/>
					X-Additional-Header: SomeValue<br/>
					</small>
					<br class="clear"/>
				</div>
				<div class="sendfeed-option">
					<strong>Email Sending Frequency:</strong>
					<select name="frequency">
						<option><?=$feed[frequency]; ?></option>
						<option>On Feed Update</option>
						<option>Daily at 4 AM</option>
						<option>Weekly on Sunday at 4 AM</option>
						<option>Weekly on Monday at 4 AM</option>
						<option>Weekly on Tuesday at 4 AM</option>
						<option>Weekly on Wednesday at 4 AM</option>
						<option>Weekly on Thursday at 4 AM</option>
						<option>Weekly on Friday at 4 AM</option>
						<option>Weekly on Saturday at 4 AM</option>
						<option>Monthly on the first day at 4 AM</option>
						<option>Monthly on the 15th day at 4 AM</option>
						<option>Send manually</option>
					</select>
					<small>Please select how frequently you would like updates to your feed emails to be sent.  Emails
					will only be sent when there are new posts available on the RSS feed.</small>
				</div>

				<div id="sendfeed-templates">
					<h3>Email Templates</h3>
					<div class="sendfeed-option">
						<strong>From Name:</strong>
						<input type="text" name="from_name" class="" value="<?=$feed[from_name]; ?>" />
						<small></small>
					</div>
					<div class="sendfeed-option">
						<strong>From Email:</strong>
						<input type="text" name="from_email" class="" value="<?=$feed[from_email]; ?>" />
						<small></small>
					</div>
					<div class="sendfeed-option">
						<strong>Subject Line:</strong>
						<input type="text" name="subject" class="" value="<?=$feed[subject]; ?>" />
						<small></small>
					</div>
					<div class="sendfeed-option">
						<strong>Text Version Template:</strong>
						<textarea name="text_template"><?=$feed[text_template]; ?></textarea>
						<small></small>
					</div>
					<div class="sendfeed-option">
						<strong>HTML Version Template:</strong>
						<textarea name="html_template"><?=$feed[html_template]; ?></textarea>
						<small></small>
					</div>
				</div>

				<small>If this feed has already been activated, changes to it will become active immediately.  Please be careful.</small><br/>
				<input type="hidden" name="feed_id" value="<?php echo $feed_id; ?>" />
				<input name="submit" type="submit" value="Save This Feed!" />
				<a href="<?=SENDFEED_URL; ?>">Cancel and Return to Main</a>
			</form>
			<?php sendfeed_display_instructions(); ?>
		</div>
