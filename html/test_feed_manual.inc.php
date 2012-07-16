		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: Send Manually</h2>
			<?php if($msg) { ?><div class='updated fade'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
				<div class="sendfeed-option">
					<strong>Feed Name:</strong>
					<?=$feed[feed_name]; ?>
				</div>
				<div class="sendfeed-option">
					Your feed has been emailed successfully to the email address you indicated when setting up the feed.<br/>
					DO NOT REFRESH THIS PAGE.  It will result in the feed being sent again.<br/><br/>
					<a href="<?=SENDFEED_URL; ?>">Click here to return to the SendFeed Options page.</a>
				</div>

			<div style="clear: both;">&nbsp;</div>
		</div>
