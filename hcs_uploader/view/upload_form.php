
<input type="hidden" name="secure" value="<?php echo $upload_token ?>">
<div class="form-group attach" style="display:none">
        <input id="filer_input" type="file" name="files[]"  multiple="multiple"  title='Click to add Images' 
		data-jfiler-extensions="<?php echo App::$forum_config['uploader_extensions_allow'] ?>"
		data-jfiler-limit="<?php echo App::$forum_config['uploader_max_upload_once'] ?>"
		data-jfiler-maxSize="<?php echo App::$forum_config['uploader_max_file_size'] ?>"
		data-jfiler-uploadUrl="<?php echo $upload_url ?>" 
		data-jfiler-uploadData='{"csrf_token": "<?php echo $upload_token ?>", "form_sent": 1, "resource_name": "<?= $resource_name ?>"}' >
</div>
