		<div class="wrap">
			<h2><a href="<?=SENDFEED_URL; ?>">SendFeed v. <?=get_option('sendfeed_version'); ?></a>: 
			<a href='<?php echo sendfeed_build_url(array(), array('page', 'sendfeed_page')); ?>'>View Log History</a></h2>
			
			<p>All important SendFeed actions are logged here for easy reading/searching.<br/>
			<a href="<?=SENDFEED_URL; ?>">Return to main SendFeed management page.</a></p>
			
			<form name="sendfeedSearch" method="GET" action="<?php echo sendfeed_build_url(array(), array()); ?>">
				<select name='feedID'>
					<option value='0'>All Feeds</option>
					<?php foreach($feeds as $feed) { ?>
						<option value='<?php echo $feed['id']?>' <?=($_GET['feedID']==$feed['id'])?'selected':''; ?>><?php echo $feed['feed_name']?></option>
					<?php } ?>
				</select>
				<input type='hidden' name='page' value='<?php echo stripslashes($_GET['page']); ?>' />
				<input type='hidden' name='sendfeed_page' value='<?php echo stripslashes($_GET['sendfeed_page']); ?>' />
				<input type='text' name='feedSearch' value="<?php echo htmlentities(stripslashes($_GET['feedSearch'])); ?>" />
				<input type='submit' name='submit' value='Search' />
			</form>
			
				
			<?php if(isset($msg)) { ?><div class='updated fade'><p><strong><?php echo $msg; ?></strong></p></div><?php } ?>
			
			<div id="sendfeed-previews" class='sendfeed-form'>
			
			<table width='100%'>
		
				<tr>
					<td align='right' valign='top' colspan="3">Showing: <?php echo $start_pos; ?>-<?php echo ($start_pos + SENDFEED_LOG_LIMIT); ?> of <?php echo $totalRows; ?> -- Page: <?php echo $nav; ?></td>
				</tr>

				<tr>
					<td align='left' valign='top' width='220'><strong>Date:</strong></td>
					<td align='left' valign='top'><strong>Message:</strong></td>
					<td align='right' valign='top' width='200'><strong>Feed:</strong></td>
				</tr>
			
				<?php if($log_items) foreach($log_items as $tmp) { 
				
					if(isset($_GET['feedSearch']))
					{
						$p1 = stripos($tmp['datestamp'], $_GET['feedSearch']);
						$p2 = stripos($tmp['message'], $_GET['feedSearch']);
						$l =  strlen($_GET['feedSearch']);
						
						if($p1 !== false)
						{
							$replace = substr($tmp['datestamp'], $p1, $l);
							$tmp['datestamp'] = str_replace($replace, '<span style="color: #00dd00; font-weight: bold;">' . $replace . '</span>', $tmp['datestamp']);
						}
						
						if($p2 !== false)
						{
							$replace = substr($tmp['message'], $p2, $l);
							$tmp['message'] = str_replace($replace, '<span style="color: #00dd00; font-weight: bold;">' . $replace . '</span>', $tmp['message']);
						}
					}
					
				?>
				
				<tr>
					<td align='left' valign='top'><?php echo $tmp['datestamp']; ?></td>
					<td align='left' valign='top'><?php echo $tmp['msgtype']; ?>: <?php echo nl2br($tmp['message']); ?></td>
					<td align='right' valign='top'><a href='<?php echo sendfeed_build_url(array('feedID'=>$tmp['feedID']), array('page', 'sendfeed_page', 'feedSearch')); ?>'><?php echo substr($feeds[$tmp['feedID']]['feed_name'], 0, 26); ?></a></td>
				</tr>
			
				<?php } ?>
		
				<tr>
					<td align='right' valign='top' colspan="3">Results: <?php echo $totalRows; ?> -- Page: <?php echo $nav; ?></td>
				</tr>

			</table>
		
			</div>
			<div style="clear: both;">&nbsp;</div>

		</div>