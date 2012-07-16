		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: Test Feed</h2>
			<?php if($msg) { ?><div class='updated fade'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
			<form action="<?=SENDFEED_URL; ?>&sendfeed_page=test_feed" method="POST" class='sendfeed-form'>
				<div class="sendfeed-option">
					<strong>Feed Name:</strong>
					<?=$feed[feed_name]; ?>
				</div>
				<div class="sendfeed-option">
					<strong>Testing Email Recipiant:</strong>
					<input type="text" name="recipient" class="" value="<?=$_POST[recipient]; ?>" />
					<small>Enter the email address to which you wish to send this test.</small>
				</div>
				<div class="sendfeed-option">
					<strong>Include Additional Headers:</strong>
					<input type="checkbox" name="include_additional_headers" value="true" <?php if($_POST[include_additional_headers] == "true") echo "checked"; ?> /> Yes
				</div>
				<input type="hidden" name="feed_id" value="<?php echo $feed_id; ?>" />
				<input name="submit" type="submit" value="Send this Test!" />
				<a href="<?=SENDFEED_URL; ?>">Return to main SendFeed management page.</a>
			</form>
			<div class='sendfeed-instructions'>
				<strong>Instructions:</strong><br/>
				<br/>
				You can test the functionality of the feed before actually activating it.  To send the latest feed
				items to a test email address, simply enter that address in the space provided, and click "Send".
			</div>
			<div style="clear: both;">&nbsp;</div>
		</div>
