<?php
/**
 * Ulogin implemenation
 *
 * @copyright (C) 2016 hcs hcs@mail.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * Extension for PunBB (C) 2008-2016 PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

?>
<div class="content-head">
    <h2 class="hn"><span><?php echo $ulogin_lang['Ulogin settings head'] ?></span></h2>
</div>
<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
	<legend class="group-legend"><span><?php echo $ulogin_lang['Setup ulogin legend'] ?></span></legend>
	<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
	    <div class="sf-box text">
	        <label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $ulogin_lang['Ulogin id label'] ?></span></label><br />
	        <span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[ulogin_id]" size="40" maxlength="50" value="<?php echo forum_htmlencode(App::$forum_config['o_ulogin_id']) ?>" /></span>
	    </div>		
	</div>

	<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
	    <div class="sf-box checkbox">
	        <span class="fld-input">
	            <input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="checkbox" <?php if (App::$forum_config['o_ulogin_force_reg'] == '1') echo ' checked="checked"' ?> value="1" name="form[ulogin_force_reg]">
	        </span>
	        <label for="fld<?php echo $forum_page['fld_count'] ?>">
	            <span><?php echo $ulogin_lang['Ulogin force register label'] ?></span>
	            <?php echo $ulogin_lang['Ulogin force register desc'] ?>
	        </label>	    
	    </div>		
	</div>
	
</fieldset>