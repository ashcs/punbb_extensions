<?php
/**
 * Make links redirectly
 *
 *	hcs_redirect_links
 * @copyright (C) 2012 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 *	Extension for PunBB (C) 2008-2012 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
defined('FORUM') OR die();

if (isset($_POST['redirect_form_sent']))
{
	
	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value=\''.$forum_db->escape($_POST['hcs_redirect_links']).'\'',
		'WHERE'		=> 'conf_name=\'o_hcs_redirect_links\''
	);

	($hook = get_hook('aop_qr_update_permission_conf')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	// Regenerate the config cache
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
	{
		require FORUM_ROOT.'include/cache.php';
	}
	
	generate_config_cache();
	redirect(forum_link($forum_url['hcs_admin_redirect']), $lang_admin_settings['Settings updated'].' '.$lang_admin_common['Redirect']);	
	
}

$query = array(
	'SELECT'	=> 'COUNT(id)',
	'FROM'		=> 'hcs_redirect_links',
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$num_links = $forum_db->result($result);

// Determine the post offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil(($num_links + 1) / $forum_user['disp_posts']);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), ($num_links + 1));

if (isset($forum_url['insertion_find']))
{
	$temp_insertion_find = $forum_url['insertion_find'];	
	unset($forum_url['insertion_find']);
}

$forum_url['page'] = '&amp;p=$1';
$forum_page['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['hcs_admin_redirect'], $lang_common['Paging separator']).'</p>';

if (isset($temp_insertion_find))
{
	$forum_url['insertion_find'] = $temp_insertion_find;
}

$query = array(
	'SELECT'	=> 'r.*, t.subject, u.signature, u.username',
	'FROM'		=> 'hcs_redirect_links AS r',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'posts AS p',
			'ON'			=> 'p.id=r.post_id'
		),
		array(
			'LEFT JOIN'	=> 'users AS u',
			'ON'			=> 'u.id=r.user_id'
		),
		array(
			'LEFT JOIN'	=> 'topics AS t',
			'ON'			=> 't.id=p.topic_id'
		),
	),
	'ORDER BY'	=> 'r.counter DESC',
	'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
);
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	
define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-redirect-links');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

$forum_page['item_count'] = $forum_page['group_count'] = $forum_page['fld_count'] = 0;

?>


	<div class="main-content main-frm">
		<div class="content-head">
			<h2 class="hn"><span><?php echo  $lang_redirect_links['Redirect exclude'] ?></span></h2>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link($forum_url['hcs_admin_redirect']) ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['hcs_admin_redirect'])) ?>" />
				<input type="hidden" name="redirect_form_sent" value="1" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_redirect_links['Redirect exclude'] ?></strong></legend>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_redirect_links['Redirect exclude list'] ?></span><small><?php echo $lang_redirect_links['Redirect exclude help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="hcs_redirect_links" rows="5" cols="55"><?php echo str_replace( ' ',"\n",forum_htmlencode($forum_config['o_hcs_redirect_links'])) ?></textarea></span></div>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" /></span>
			</div>
		</form>		
	</div>

<div id="brd-pagepost-top" class="main-pagepost gen-content">
	<?php echo $forum_page['paging'] ?>
</div>
	
<div class="main-content">
<div id="brd-userlist">
<table>
<tr>
<th class="tc2"><?php echo $lang_redirect_links['Number'] ?></th>
<th><?php echo $lang_redirect_links['Link'] ?></th>
<th><?php echo $lang_redirect_links['Source'] ?></th>
<th class="tc2"><?php echo $lang_redirect_links['Count'] ?></th>
</tr>
<?php 
$forum_page['item_count'] = 0;
while ($cur_link = $forum_db->fetch_assoc($result))
{
	
?>
	<tr>
<td class="tc2"><?php echo ++$forum_page['item_count'] ?></td>	
<td><a href="<?php echo $cur_link['link'] ?>"><?php echo $cur_link['link'] ?></a></td>
<?php if ($cur_link['post_id'] == 0) : ?>
<td><?php echo sprintf($lang_redirect_links['User profile'], '<a href="'.forum_link($forum_url['user'], $cur_link['user_id']).'">'.forum_htmlencode($cur_link['username'])).'</a>' ?></td>
<?php elseif ($cur_link['user_id'] == 0) :?>
<td><a href="<?php echo forum_link($forum_url['post'], $cur_link['post_id']) ?>"><?php echo $cur_link['subject'] ?></a></td>
<?php else : ?>
<td><?php echo sprintf($lang_redirect_links['Sig in post'], '<a href="'.forum_link($forum_url['post'], $cur_link['post_id']).'">'.forum_htmlencode($cur_link['subject'])).'</a>' ?></td>
<?php endif?>

<td class="tc2"><?php echo $cur_link['counter'] ?></td>	
	</tr>
<?php 
}


?>
</table>

</div>

</div>


