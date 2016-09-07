	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo App::$forum_page['form_action'] ?>">
		<div class="hidden">
			<input type="hidden" name="form_sent" value="1" />
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(App::$forum_page['form_action']) ?>" />
		</div>

		<fieldset class="frm-group group<?php echo ++App::$forum_page['group_count'] ?>">
		<div class="ct-group">
	
		<table cellspacing="0">
			<thead>
				<tr>
				<th class="tc1"><?php echo App::$lang['From user'] ?></th>
				<th class="tc3" style="width:20%"><?php echo App::$lang['For topic'] ?></th>
				<th class="tc3"  style="width:28%"><?php echo App::$lang['Reason'] ?></th>
				<th class="tc1" style="width:4em;text-align:center;">+/-</th>
				<th class="tc3" style="width:4.5em;text-align:center;"><?php echo App::$lang['Date'] ?></th>
				<th class="tc3" style="width:4em;text-align:center;"><?php echo App::$lang['Delete'] ?></th>
				</tr>
			</thead>
			<tbody>
<?php foreach ($records as $cur_rep) : 
			$cur_rep['reason']= parse_message($cur_rep['reason'], 0);
?>
				<tr>					
					<td><?php echo $cur_rep['from_user_name'] ? '<a href="'.forum_link(App::$forum_url['reputation_view'], $cur_rep['from_user_id']).'">'. forum_htmlencode($cur_rep['from_user_name']).'</a>' :  App::$lang['Profile deleted'] ?></td>
					<td>
<?php 
	if ($cur_rep['read_forum'] == null ||  $cur_rep['read_forum'] == 1)
		echo $cur_rep['subject'] ? '<a href="'.forum_link(App::$forum_url['post'], $cur_rep['post_id']) . '">'.forum_htmlencode($cur_rep['subject']).'</a>' : App::$lang['Removed or deleted'];
	else 
		echo App::$lang['Topic not readable'];
?>
					</td>
					<td>
<?php 
	if ($cur_rep['read_forum'] == null ||  $cur_rep['read_forum'] == 1) {
		echo $cur_rep['reason'];
		if ($cur_rep['comment'] != '') 
			echo '<div class="ct-box info-box">'.parse_message($cur_rep['comment'], 0).'</div>';
		else {
			if ($cur_rep['user_id'] == App::$forum_user['id']){
				echo '<div class="ct-box info-box"><cite><a class="rep_info_link" href="'.forum_link(App::$forum_url['reputation_comment'], $cur_rep['id']) . '">'.App::$lang['Comment'].'</a></cite></div>';
			}			
		}
	}
	else 
		echo App::$lang['Message not readable'];	
?>
					</td>
					<td style="text-align:center;"><?php echo $cur_rep['rep_plus']>0 ? $cur_rep['rep_plus'].' <img src="'.forum_link('extensions/reputation').'/img/warn_add.gif" alt="+" border="0">' : $cur_rep['rep_minus'].' <img src="'.forum_link('extensions/reputation').'/img/warn_minus.gif" alt="-" border="0">'; ?></td>
					<td><?php echo format_time($cur_rep['time']) ?></td>
					<td style="text-align:center;"><input type="checkbox" name="delete_rep_id[]" value="<?php echo $cur_rep['id'] ?>"></td>						
				</tr>
<?php endforeach;?>
			</tbody>
		</table>
		</div>
		</fieldset>
		<div class="frm-buttons">
			<p class="postlink conr"><input type="submit" name="del_rep" value="<?php echo App::$lang_common['Delete'] ?>" onclick="return confirm('<?php echo App::$lang['Are you sure']; ?>')" /></p>
		</div>
		</form>

