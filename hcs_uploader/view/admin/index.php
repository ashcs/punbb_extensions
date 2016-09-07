<?php
?>
<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link(App::$forum_url['uploader_admin_setup_update']) ?>">
		<div class="content-head">
			<h2 class="hn"><span><?php echo App::$lang['Setup title'] ?></span></h2>
		</div>
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link(App::$forum_url['uploader_admin_setup_update'])) ?>" />
		</div>
		<fieldset class="frm-group group1">

			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Uploader basefolder'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_basefolder" size="75" maxlength="150" value="<?php echo forum_htmlencode(App::$forum_config['uploader_basefolder']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Extensions allow'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_extensions_allow" size="75" maxlength="150" value="<?php echo forum_htmlencode(App::$forum_config['uploader_extensions_allow']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Watermark image'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_watermark_image" size="75" maxlength="150" value="<?php echo forum_htmlencode(App::$forum_config['uploader_watermark_image']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Watermark position'] ?></span></label><br />
					<span class="fld-input"><input type="text" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_watermark_position" size="75" maxlength="150" value="<?php echo forum_htmlencode(App::$forum_config['uploader_watermark_position']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Thumbnail'] ?></span></label><br />
					<span class="fld-input">
						<?php echo App::$lang['Thumbnail width'] ?>
						<input type="number" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_thumbnail_width" size="4" maxlength="4" value="<?php echo forum_htmlencode(App::$forum_config['uploader_thumbnail_width']); ?>" />
						<?php echo App::$lang['Thumbnail height'] ?>
						<input type="number" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_thumbnail_height" size="4" maxlength="4" value="<?php echo forum_htmlencode(App::$forum_config['uploader_thumbnail_height']); ?>" />
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Max files upload once'] ?></span></label><br />
					<span class="fld-input"><input type="number" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_max_upload_once" size="5" maxlength="10" value="<?php echo forum_htmlencode(App::$forum_config['uploader_max_upload_once']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Max files upload total'] ?></span></label><br />
					<span class="fld-input"><input type="number" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_max_upload_total" size="5" maxlength="10" value="<?php echo forum_htmlencode(App::$forum_config['uploader_max_upload_total']); ?>" /></span>
				</div>
			</div>
			<div class="sf-set set<?php echo ++App::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++App::$forum_page['fld_count'] ?>"><span><?php echo App::$lang['Max file size Mb'] ?></span></label><br />
					<span class="fld-input"><input type="number" id="fld<?php echo App::$forum_page['fld_count'] ?>" name="uploader_max_file_size" size="5" maxlength="10" value="<?php echo forum_htmlencode(App::$forum_config['uploader_max_file_size']); ?>" /></span>
				</div>
			</div>

		</fieldset>


			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="update_settings" value="<?php echo App::$lang['Save changes'] ?>" /></span>
			</div>
	</form>
</div>

