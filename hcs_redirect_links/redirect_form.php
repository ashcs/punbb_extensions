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

$forum_page['form_action'] = forum_link($forum_url['hcs_redirect_confirm']);
$forum_page['hidden_fields'] = array(
	'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
	'prev_url'		=> '<input type="hidden" name="prev_url" value="'.forum_htmlencode($forum_user['prev_url']).'" />',
	'pid'			=> '<input type="hidden" name="pid" value="'.$pid.'" />',
	'uid'			=> '<input type="hidden" name="uid" value="'.$uid.'" />',
	'profile_id'	=> '<input type="hidden" name="profile_id" value="'.$profile_id.'" />',
	'hash'			=> '<input type="hidden" name="hash" value="'.forum_htmlencode($_GET['hash']).'" />',
);
define('FORUM_ALLOW_INDEX', 0);
$forum_head['robots'] = '<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />';

define('FORUM_PAGE', 'redirect-links-form');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();	
?>

<div id="brd-main" class="main">
	<div class="main-head">
		<h2 class="hn"><span><?php echo $lang_common['Confirm action head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<p><?php echo $lang_redirect_links['Confirm redirect'] ?></p>
		</div>
		<form class="frm-form" method="post" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>		
			<div class="frm-buttons">
				<span class="submit"><input type="submit" value="<?php echo $lang_common['Confirm'] ?>" /></span>
				<span class="cancel"><input type="submit" name="confirm_cancel" value="<?php echo $lang_common['Cancel'] ?>" onClick="window.close();" /></span>
			</div>
		</form>
	</div>
</div>


<?php 
$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';