<?php

define('FORUM_ROOT', '../../');
require FORUM_ROOT.'include/common.php';

header('Content-type: text/html; charset=utf-8');

($hook = get_hook('ape_start')) ? eval($hook) : null;

if ($forum_user['g_read_board'] == '0')
	message($lang_common['No view']);

require_once FORUM_ROOT.'include/parser.php';

// Load the topic.php and post.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

if (file_exists(FORUM_ROOT.'extensions/ajax_post_edit/lang/'.$forum_user['language'].'.php'))
	require FORUM_ROOT.'extensions/ajax_post_edit/lang/'.$forum_user['language'].'.php';
else
	require FORUM_ROOT.'extensions/ajax_post_edit/lang/English.php';

$action = isset($_POST['action']) ? $_POST['action'] : null;
$id = isset($_POST['id']) ? intval($_POST['id']) : null;

if (!isset($id))
	message($lang_common['Bad request']);

$query = array(
	'SELECT'	=> 'f.id AS fid, f.moderators, f.redirect_url, t.id AS tid, t.closed, t.first_post_id, p.poster, p.poster_id, p.hide_smilies, p.message, p.edited, p.edited_by, p.posted',
	'FROM'		=> 'posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'topics AS t',
			'ON'			=> 't.id=p.topic_id'
		),
		array(
			'INNER JOIN'	=> 'forums AS f',
			'ON'			=> 'f.id=t.forum_id'
		),
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id
);

($hook = get_hook('ape_qr_get_post_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
if (!$result)
	message($lang_common['Bad request']);

$cur_post = $forum_db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

// Do we have permission to edit this post?
if (($forum_user['g_edit_posts'] == '0' ||
	$cur_post['poster_id'] != $forum_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$forum_page['is_admmod'])
	message($lang_common['No permission']);

$can_edit_subject = $id == $cur_post['first_post_id'];

// it's a request for get post message
if ($action == 'get')
{
	($hook = get_hook('ape_pre_message_box')) ? eval($hook) : null;

?>
	<div class="main-content frm" id="ajax_post_edit">
		<form style="padding: 10px" id="post_edit_form" method="post" action="<?php echo forum_link($forum_url['edit'], $id) ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['edit'], $id)) ?>" />
				<input type="hidden" name="form_sent" value="1" />
				<input type="hidden" name="preview" value="1" />
			</div>

<?php //($hook = get_hook('ed_pre_message_box')) ? eval($hook) : null; // This hook is not changed, it allows displaying a bbcode bar ?>
			<div style="margin-top: 5px">
				<textarea id="postedit" name="req_message" style="width: 100%"><?php echo forum_htmlencode($cur_post['message']) ?></textarea>
			</div>

<?php if ($forum_page['is_admmod']) : ?>
			<div style="margin-top: 5px">
				<label for="fldsilent"><input type="checkbox" id="fldsilent" name="silent" value="1" checked="checked" /> <?php echo $lang_post['Silent edit'] ?></label>
			</div>
<?php endif; ?>

			<div style="margin-top: 5px">
				<div style="float:right; display:none" id="edit_info">
					<img src="<?php echo $base_url ?>/extensions/ajax_post_edit/loading.gif" /> <?php echo $lang_ape['Saving'] ?>
				</div>
				<input type="button" onclick="PUNBB.env.ape.update_post(<?php echo $id ?>)" value="<?php echo $lang_ape['Update'] ?>" id="btn_updatePost" />
				<input type="submit" value="<?php echo $lang_ape['Advanced Edit'] ?>" id="btn_fullEdit" />
				<input type="button" onclick="PUNBB.env.ape.cancel_edit(<?php echo $id ?>)" value="<?php echo $lang_ape['Cancel'] ?>" id="btn_cancelUpdate" />
			</div>
		</form>
	</div>

	<!-- END FORM -->
		<parsed_message><?php echo parse_message($cur_post['message'], $cur_post['hide_smilies']) ?></parsed_message>

<?php

}

// it's a request for update post in database
elseif ($action == 'update')
{
	// Clean up message from POST
	$message = forum_linebreaks(trim($_POST['req_message']));

	if ($message == '')
		$errors[] = $lang_post['No message'];
	if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
		$errors[] = sprintf($lang_post['Too long message'], forum_number_format(strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
	else if ($forum_config['p_message_all_caps'] == '0' && utf8_strtoupper($message) == $message && !$forum_page['is_admmod'])
		$errors[] = $lang_post['All caps message'];

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] == '1' || $forum_config['o_make_links'] == '1')
		$message = preparse_bbcode($message, $errors);

	($hook = get_hook('ape_end_validation')) ? eval($hook) : null;

	// If there were any errors, show them
	if (!empty($errors))
	{
		$errors_list = array();
		while (list(, $cur_error) = each($errors))
			$errors_list[] = '<li class="warn"><span>'.$cur_error.'</span></li>';
?>
		<error>
			<div class="frm-error" id="edit-error">
				<h3 class="warn"><?php echo $lang_post['Post errors'] ?></h3>
				<ul>
					<?php echo implode("\n\t\t\t\t\t", $errors_list)."\n" ?>
				</ul>
			</div>
		</error>
<?php
	}

	else
	{
		$edited_by = '';
		$edited = 0;

		if ($_POST['silent'] == 0)
		{
			$edited_by = $forum_user['username'];
			$edited = time();
		}
		elseif ($cur_post['edited'] != '')
		{
			$edited_by = $cur_post['edited_by'];
			$edited = $cur_post['edited'];
		}

		// save post
		$query = array(
			'UPDATE'	=> 'posts',
			'SET'		=> 'message=\''.$forum_db->escape($message).'\'',
			'WHERE'		=> 'id='.$id
		);

		if (!($_POST['silent'] == 1 && $forum_page['is_admmod']))
			$query['SET'] .= ', edited='.$edited.', edited_by=\''.$forum_db->escape($edited_by).'\'';

		($hook = get_hook('ape_qr_update_post')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

?>
		<message><?php echo parse_message($message, $cur_post['hide_smilies']) ?></message>

<?php if ($edited_by && $edited) : ?>
		<last_edit>
			<p class="lastedit"><em><?php echo sprintf($lang_topic['Last edited'], forum_htmlencode($edited_by), format_time($edited)) ?></em></p>
		</last_edit>
<?php endif;

	}

}

