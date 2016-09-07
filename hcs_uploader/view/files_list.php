<div class="jFiler  jFiler-items jFiler-row">
<ul class="jFiler-items-list jFiler-items-default">
<?php
        App::$forum_loader->add_js(App::$base_url.'/extensions/hcs_uploader/js/vendor/lightbox2/js/lightbox.min.js', array('type' => 'url', 'weight'=> 170));

	$files_image = $files_other = "";
	foreach($list as $k => $rec) {
		$mime = explode('/', $rec['mime']);
		$class_mime = preg_replace("/(\/|\.)/i", "_", $rec['mime']); // icon class name for mime type
		$ext = pathinfo($rec['file_path'].$rec['name'], PATHINFO_EXTENSION);
		switch ($mime[0]){
		    case "image": 
		        $icon_class = "icon-jfi-file-image f-file-ext-".$ext;
		        break;
		    case "video":
		        $icon_class = "icon-jfi-file-video f-file-ext-".$ext;
		        break;
		    case "audio":
		        $icon_class = "icon-jfi-file-audio f-file-ext-".$ext;
		        break;
		    default:
		        $icon_class = "icon-jfi-file-o jfi-file-type-application f-file-ext-".$ext;
		        
		}
		
?>
    <li class="jFiler-item">
        <div class="jFiler-item-container">
            <div class="jFiler-item-inner">
                <div class="jFiler-item-icon pull-left">
                <?php if ($mime[0] == 'image') : ?>
                    <img src="<?= forum_link($rec['file_path'].'thumbnail/'.$rec['name']) ?>" height="32px">
                <?php else : ?>                
                    <i class="<?= $icon_class ?>"></i>
                <?php endif ?>
                </div>
                <div class="jFiler-item-info pull-left">
                    <div class="jFiler-item-title" title="<?= $rec['orig_name']?>">
                        <?= $rec['orig_name']?> 
                    </div>
                    <div class="jFiler-item-others">
                        <span>size: <?= $rec['brief_size'] ?></span>
                        <span>type: <?= $ext ?></span>
                    </div>
                    <div class="jFiler-item-assets">
                        <ul class="list-inline">
                            <li>
                                <a class="icon-jfi-download-o jFiler-item-download" href="<?= forum_link(App::$forum_url['uploader_file_link'], $rec['id']) ?>"></a>
                            </li>                    
                       </ul>
                   </div>
                </div>
            </div>
        </div>            
   </li>
<?php
	}
//		if($ar[0] == 'image') {
//			$files_image .= '<div class="upl-files-image-item"><a href="'.$rec['file_path'].$rec['name'].'" data-lightbox="post-'.$id.'"><img src="'.$rec['file_path'].'thumbnail/'.$rec['name'].'" height="100"></a></div>';
//		} else {
			//$files_other .= '<div class="upl-files-other-item icon-'.$class_mime.'"><a href="'.forum_link(App::$forum_url['uploader_file_link'], $rec['id']).'">'.$rec['orig_name'].'</a> ('.$rec['brief_size'].')</div>';
//		}
//	}
	//echo '<div class="upl-files-image-list">'.$files_image.'</div>';
	//echo '<div style="clear:both;"></div>';
//	echo '<div class="upl-files-other-list">'.$files_other.'</div>';
?>
</ul>
</div>
