<?php 

/*
echo 'siteurl:' . get_option('siteurl') . "<br/>\n";
echo 'ABSPATH:' . ABSPATH . "<br/>\n";
echo 'SENDFEED_FILEPATH:' . SENDFEED_FILEPATH . "<br/>\n";
echo 'SENDFEED_PLUGIN_PATH:' . SENDFEED_PLUGIN_PATH . "<br/>\n";
echo 'SENDFEED_URL:' . SENDFEED_URL . "<br/>\n";
echo 'SENDFEED_PLUGIN_LOCATION:' . SENDFEED_PLUGIN_LOCATION . "<br/>\n";

echo 'dirname:' . dirname(__FILE__) . "<br/>\n";
echo 'SENDFEED_PLUGIN_LOCATION:' . SENDFEED_PLUGIN_LOCATION . "<br/>\n";
*/
?>

<style>
	td {
	}
	.feedActions
	{
		padding: 6px 8px 8px 14px;
		font-weight: bold;
		margin: 0;
		display: block;
		position: relative;
	}
	.feedActions ul
	{
		clear: both;
		position: relative;
		display: block;
		height: 14px;
	}
	.feedActions ul li
	{
		display: inline;
		float: left;
		padding: 0 4px 0 4px;
		margin: 0;
		height: 14px;
		border-right: 1px solid black;
	}
	.feedActions ul li.last
	{
		border-right: 0px solid black;
	}
	.sendfeedRow
	{
		padding: 4px;
	}
</style>

		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: General Options</h2>
			<?php if($msg) { ?><div class='updated'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
			<form action="<?=SENDFEED_URL; ?>" method="POST" class='sendfeed-form'>

				<div id="sendfeed-feedlist">
					<h3>Current Feed List</h3>
					<table width="100%">
						<?php if(isset($feeds)) foreach($feeds as $feed) { 
							$alt = $alt ? '' : 'sendfeed-alt';
							?>
						<tr class="<?php echo $alt; ?>" id='feed-<?php echo $feed['id']; ?>'>
							<td align="left" valign="top" class="sendfeedRow">
								<a href='<?=$feed[feed_url]; ?>'><img src='<?=SENDFEED_PLUGIN_LOCATION; ?>images/feed-icon-14x14.png' border='0' align='absmiddle' vspace='2' /></a>
								<a href='javascript:;' onClick="jQuery('#feed-<?php echo $feed['id']; ?>-controls').slideToggle();"><?=$feed[feed_name]; ?></a>
								
								<div id='feed-<?php echo $feed['id']; ?>-controls' style='display: none;' class='feedActions'>
									<ul>
										<li><a href="<?=sendfeed_build_url(array('feedID'=>$feed[id], 'sendfeed_page'=>'display_log'), array('page')); ?>">View Log</a></li>
										<li><a href="<?=SENDFEED_URL; ?>&sendfeed_page=edit_feed&feed_id=<?=$feed[id]; ?>">Edit</a></li>
										<li><a href="<?=SENDFEED_URL; ?>&sendfeed_page=preview&feed_id=<?=$feed[id]; ?>">Preview</a></li>
										<li><a href="<?=SENDFEED_URL; ?>&sendfeed_page=test_feed&feed_id=<?=$feed[id]; ?>">Test</a></li>
										<li><a href="<?=SENDFEED_URL; ?>&sendfeed_page=manualsend_feed&feed_id=<?=$feed[id]; ?>"
										onclick="return(confirm('Do you really want to send this feed now? [<?=preg_replace("#[^A-Za-z0-9 _\-]#isU", "", $feed[feed_name]); ?>] Last chance to change your mind...'));" >Send Now!</a></li>
										<?php if($feed['frequency'] != 'Send manually') { ?>
										<li>
											<?php if($feed[activated] == 1) { ?>
												<a href="<?=SENDFEED_URL; ?>&sendfeed_page=deactivate_feed&feed_id=<?=$feed[id]; ?>"
												onclick="return(confirm('Do you really want to deactivate this feed? [<?=preg_replace("#[^A-Za-z0-9 _\-]#isU", "", $feed[feed_name]); ?>]'));" >Deactivate</a>
											<?php } else { ?>
												<a href="<?=SENDFEED_URL; ?>&sendfeed_page=activate_feed&feed_id=<?=$feed[id]; ?>"
												onclick="return(confirm('Do you really want to activate this feed? [<?=preg_replace("#[^A-Za-z0-9 _\-]#isU", "", $feed[feed_name]); ?>]'));" >Activate</a>
											<?php } ?>
										</li>
										<?php } ?>
										<li class='last'><a href="<?=SENDFEED_URL; ?>&sendfeed_page=remove_feed&feed_id=<?=$feed[id]; ?>"
										onclick="return(confirm('Do you really want to remove this feed? [<?=preg_replace("#[^A-Za-z0-9 _\-]#isU", "", $feed[feed_name]); ?>]  There is NO undo feature.'));" >Remove</a></li>
									</ul>	
									
								</div>
							</td>
							<td width="120" valign="baseline" align="center">[<? echo $feed[frequency]; ?>]</td>
							<td width="80" valign="baseline" align="center"><?php echo ($feed[activated] == 1)? '[active]' : ''; ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td colspan="3" align="center" valign="top">&nbsp;<br/>
							<a href="<?=SENDFEED_URL; ?>&sendfeed_page=new_feed">Add New Feed</a></td>
						</tr>
					</table>
				</div>

			</form>
			<div class='sendfeed-instructions'>
				<strong>Description:</strong><br/>
				This WordPress plugin will allow for automatic emails to be sent out,
				pulling content from rss feeds and sending to selected email addresses.
				<br/>
				<br/>
				If you run into problems, you can always <a href='<?=SENDFEED_URL; ?>&sendfeed_page=display_log'>view the historical log</a>.
			</div>
			<div style="clear: both;">&nbsp;</div>
		</div>
