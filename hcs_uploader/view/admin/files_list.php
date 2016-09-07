<div class="main-content main-frm">
<h1>Файлы</h1>

<div class="row">

<?php 
if(count($files)) {
?>
<div class="col-sm-12">

	<form id="save_url" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link(App::$forum_url['uploader_admin_alone_delete']); ?>" enctype="multipart/form-data">
    	        <input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link(App::$forum_url['uploader_admin_alone_delete'])); ?>" />
		<input type="hidden" name="send" value="1"> 
<table class="table table-striped table-bordered" id="pairs">
    <thead>
        <tr>
            <th class="col-sm-1">Удалить</th>
            <th class="col-sm-2">Дата</th>
            <th>Файл</th>
            <th class="col-sm-1">Размер</th>
            <th>Владелец</th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($files as $rec) : ?>
        <tr>
            <td>
               <input type="checkbox" name="del[]" value="<?php echo $rec['id'] ?>">
            </td>
            <td>
            <?php echo date("d.m.Y H:i:s", $rec['date']); ?>
            </td>
            <td>                                                                                           
            <?php echo '<a href="/misc.php?r=hcs_uploader/downloader/download/id/'.$rec['id'].'">'.$rec['orig_name']."</a>" ?>
            </td>
            <td>
            <?php echo $rec['size'] ?>
            </td>
            <td>
            <?php echo $rec['username'] ?>
            </td>
        </tr>
<?php endforeach; ?>
    </tbody>
</table>
		<div class="frm-buttons">
			<span class="submit primary"><button type="submit" class="btn btn-primary btn-ajax" data-target="#save_url" data-loading-text="Loading..." name="form_action" value="add_url" ><?php echo App::$lang['Uploader remove button'] ?></button> </span>
		</div>
	</form>

</div>
<?php
	// if count
} else {
?>
<div class="col-sm-12">
<h2>Битые файлы отсутствуют</h2>
</div>
<?php
}
?>
</div>

</div>