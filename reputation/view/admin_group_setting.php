				<div class="content-head">
					<h3 class="hn"><span><?php echo App::$lang['Reputation permissions'] ?></span></h3>
				</div>
				<fieldset class="mf-set set<?php echo ++App::$forum_page['item_count'] ?>">
					<legend><span><?php echo App::$lang['Reputation enable legend'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++App::$forum_page['fld_count'] ?>" name="rep_enable" value="1"<?php if ($group['g_rep_enable'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo App::$forum_page['fld_count'] ?>"><?php echo App::$lang['Group enable'] ?></label>
						</div>
					</div>
				</fieldset>
				<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Min post for minus'] ?></span> <small><?php echo App::$lang['Min post minus help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="rep_minus_min" size="5" maxlength="4" value="<?php echo $group['g_rep_minus_min'] ?>" /></span>
					</div>
					<div class="sf-box text">
						<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Min post for plus'] ?></span> <small><?php echo App::$lang['Min post plus help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="rep_plus_min" size="5" maxlength="4" value="<?php echo $group['g_rep_plus_min'] ?>" /></span>
					</div>
				</div>	
				<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Timeout'] ?></span><small><?php echo App::$lang['Timeout help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="rep_timeout" size="6" maxlength="6" value="<?php echo $group['g_rep_timeout'] ?>" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Weight'] ?></span><small><?php echo App::$lang['Weight help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="rep_weight" size="6" maxlength="6" value="<?php echo $group['g_rep_weight'] ?>" /></span>
					</div>
				</div>				