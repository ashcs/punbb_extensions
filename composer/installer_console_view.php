<?php 
defined('FORUM_ROOT') or exit('Derect access not allowed');
global $visit_elements,  $visit_links, $tpl_main, $gen_elements,  $main_elements, $forum_page;
define('FORUM_PAGE_SECTION', 'extensions');
define('FORUM_PAGE', 'admin-extensions-manage');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();
?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_composer[$action]. ' '. $id ?></span></h2>
	</div>
	<div class="main-content main-extensions">
	    <div class="content-head">
	       <h3 class="hn">
                <span><?php echo $lang_composer['Console'] ?></span>
            </h3>
        </div>
        <div class="ct-box" id="o-container" style="height:200px; overflow:auto">
            <pre id="output"></pre>
        </div>
        
        <form id="composer-installer-form" data-composer-action="<?php echo $action ?>" data-composer-url="<?php echo forum_link('extensions/composer/main.php') ?>" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $form_action ?>">
            <input type="hidden" name="composer_token" value="<?php echo generate_form_token(forum_link('extensions/composer/main.php')) ?>" />
            <input type="hidden" name="csrf_token" value="<?php echo $form_csrf_token ?>" />
            <input type="hidden" name="id" value="<?php echo $id ?>" />
            
            <div class="frm-buttons">
                <span class="submit primary caution">
                    <input type="submit" name="install_cancel" value="Cancel" style="display:none">
                </span>        
                <span class="submit primary">
                    <input type="button" disabled="disabled" value="Finish" name="finish">
                </span>
            </div>
            <input type="submit" style="display:none" value="<?php echo $button['value'] ?>" name="<?php echo $button['name'] ?>">
        </form>
	</div>

<?php 
$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';

exit;