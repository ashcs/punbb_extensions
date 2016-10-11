<?php

chdir(__DIR__.'/../../');
define('FORUM_ROOT', getcwd().DIRECTORY_SEPARATOR);

//define('FORUM_ROOT', __DIR__.'/../../');
// Tell common.php that we don't want output buffering
define('FORUM_DISABLE_BUFFERING', 1);

require FORUM_ROOT.'include/common.php';

if ($forum_user['g_id'] != FORUM_ADMIN) exit();

$forum_url['rebuild'] = 'extensions/hcs_uploader/migrate.php';

function add_attach($cur_post)
{
    global $forum_db, $forum_config;
    
    $meta = unserialize($cur_post['meta']);
    
    $attach_record = array(
        'user_id' => $cur_post['poster_id'],
        'resource_name' =>  '\'post\'',
        'resource_id' =>  '\'' . $cur_post['post_id']. '\'',
        'orig_name' => '\'' . $forum_db->escape($cur_post['orig_name']) . '\'',
        'size' => '\'' . $meta['size'] . '\'',
        'date' => '\'' . $cur_post['time'] . '\'',
        'mime' => '\'' . $forum_db->escape(mime_content_type(FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$cur_post['name'])) . '\'',
        'name' => '\'' . $forum_db->escape($cur_post['name']) . '\'',
        'file_path' => '\'' .$forum_db->escape($forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'). '\'',
        'secure' => '\'\''
    );
    
    $attach_query = array(
        'INSERT' => implode(',', array_keys($attach_record)),
        'INTO' => 'upload_files',
        'VALUES' => implode(',', array_values($attach_record))
    );
    
    $forum_db->query_build($attach_query) or error(__FILE__, __LINE__);
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;File copied<br>';
}


function update_message($cur_post)
{
    global $forum_db, $forum_config;
    $new_message = str_replace('extensions/image_uploader/storage/'.$cur_post['poster_id'].'/thumb/'.$cur_post['name'], $forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$forum_config['uploader_thumbnail_path'].$cur_post['name'], $cur_post['message']);
    
    $new_message_query = array(
        'UPDATE'	=> 'posts',
        'SET'		=> 'message = \''. $forum_db->escape($new_message) .'\'',
        'WHERE'		=> 'id='. $cur_post['post_id']
    );
    $forum_db->query_build($new_message_query) or error(__FILE__, __LINE__);
    $_SESSION['messages_updated']++;
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Message update<br>';
}


if (isset($_GET['i_per_page']) && isset($_GET['i_start_at']))
{
    $per_page = intval($_GET['i_per_page']);
    $start_at = intval($_GET['i_start_at']);
    if ($per_page < 1 || $start_at < 1)
        message($lang_common['Bad request']);

    @set_time_limit(0);

    // Setup breadcrumbs
    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
    );

    ?>
<!DOCTYPE html>
<html lang="<?php $lang_common['lang_identifier'] ?>" dir="<?php echo $lang_common['lang_direction'] ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo generate_crumbs(true) ?></title>
<style type="text/css">
body {
	font: 68.75% Verdana, Arial, Helvetica, sans-serif;
	color: #333333;
	background-color: #FFFFFF
}
</style>
</head>
<body>
<p><?php echo 'Migrating' ?></p>

<?php

	$query = array(
		'SELECT'	=> 'iu.*, iuref.post_id, p.message, p.poster_id',
		'FROM'		=> 'iu_images AS iu',
	    'JOINS'		=> array(
	        array(
	            'INNER JOIN'	=> 'iu_references AS iuref',
	            'ON'			=> 'iuref.image_id=iu.id'
	        ),
	        array(
	            'INNER JOIN'	=> 'posts AS p',
	            'ON'			=> 'iuref.post_id=p.id'
	        ),
	    ),	    
		'WHERE'		=> 'iu.id >= '.$start_at,
		'ORDER BY'	=> 'iu.id',
		'LIMIT'		=> $per_page
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$post_id = 0;
	echo '<p>';

	while ($cur_post = $forum_db->fetch_assoc($result))
	{
	    echo 'Processing file id #<strong>'. $cur_post['id'].'</strong> file name='.$cur_post['orig_name']. ' in post id '.$cur_post['post_id'].'<br>'."\n";

	    $cur_post['message'] = str_replace('image_uploader//storage','image_uploader/storage',$cur_post['message']);
	    
	    preg_match_all('/('.str_replace('/','\/',preg_quote('extensions/image_uploader/storage/'.$cur_post['poster_id'].'/thumb/'.$cur_post['name'])).')\[\/img\]/', $cur_post['message'], $matches);
    
		if (isset($matches[1]) && isset($matches[1][0]))
		{
		    $_SESSION['migrated_items']++;
		    
		    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Matching in message<br>';
		    
		    if (isset($_SESSION['images'][$cur_post['id']])) {
		        update_message($cur_post);
		        continue;
		    }
		    
		    $_SESSION['images'][$cur_post['id']] = true;
		    
		    $new_path = FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$cur_post['name'];
		    $new_thumb_path =  FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$forum_config['uploader_thumbnail_path'].$cur_post['name'];

		    $old_path = str_replace('thumb/', '', $matches[1][0]);
		    if (file_exists(FORUM_ROOT.$old_path)) {
		        
		        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Matching in disk<br>';
		        
		        if (! is_dir(FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'])) {
		            mkdir(FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'], 750);
		        }
		        
		        if (rename(FORUM_ROOT.$old_path, $new_path)) {
		            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;File copied<br>';
		            add_attach($cur_post);
		        }
		        else {
		            echo 'rename error. breaking';
		            exit;
		        }
		        
		        if (file_exists(FORUM_ROOT.$matches[1][0])) {
		            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Preview matching<br>';
		            if (! is_dir(FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$forum_config['uploader_thumbnail_path'])) {
		                mkdir(FORUM_ROOT.$forum_config['uploader_basefolder'].$cur_post['poster_id'].'/'.$forum_config['uploader_thumbnail_path'], 750);
		            }
		        
		            if (rename(FORUM_ROOT.$matches[1][0], $new_thumb_path)) {
		                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Peview copied<br>';
		            }
		            
		            else {
		                $_SESSION['messages_ignored']++;
		                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:red"><strong>Preview not copied. Message not updated</strong></span><br>';
		            }
		        }
		        else {
		            /**
		             * предпросмотр не найден. что делаем?
		             */
		            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:red"><strong>Preview not matching</strong></span><br>';
		            $_SESSION['files_missed']++;
		        }
		    }
		    else {
		        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:red"><strong>File not matching in disk</strong></span><br>';
		        $_SESSION['messages_ignored']++;
		        $_SESSION['files_missed']++;
		    }
		    update_message($cur_post);
		}
		else {
		    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:red"><strong>File not matching in message</strong></span><br>';		    
		    $_SESSION['messages_ignored']++;
		    $_SESSION['items_missed_in_messages']++;
		}
	    
	    $post_id = $cur_post['id'];
	}

	// Check if there is more work to do
	$query = array(
		'SELECT'	=> 'id',
		'FROM'		=> 'iu_images',
		'WHERE'		=> 'id > '.$post_id,
		'ORDER BY'	=> 'id',
		'LIMIT'		=> '1'
	);


	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$next_posts_to_proced = $forum_db->result($result);

	$query_str = '';
	if (!is_null($next_posts_to_proced) && $next_posts_to_proced !== false)
	{
		$query_str = '?i_per_page='.$per_page.'&i_start_at='.$next_posts_to_proced.'&csrf_token='.generate_form_token('reindex'.$forum_user['id']);
	}

	//echo 'next id '. $next_posts_to_proced;
	echo '</p>';
	$forum_db->end_transaction();
	$forum_db->close();

	if ($query_str == '') {

        echo 'Перенесено Файлов '.$_SESSION['migrated_items'] . '<br>';
        echo 'Не найдены в сообщениях '.$_SESSION['items_missed_in_messages'] . '<br>';
        echo 'Проигнорировано сообщений '.$_SESSION['messages_ignored'] . '<br>';
        echo 'Не найдено файлов '.$_SESSION['files_missed'] . '<br>';
        echo 'Обновлено сообщений '.$_SESSION['messages_updated'] . '<br>';
        
	    exit;
    }
	exit('<script type="text/javascript">window.location="'.forum_link($forum_url['rebuild']).$query_str.'"</script><br />'. 'Javascript redirect' .' <a href="'.forum_link($forum_url['rebuild']).$query_str.'">'.'Click to continue'.'</a>.');
}

if (!$forum_db->table_exists('iu_images')) {
    exit('Nothing to migrate');
}


// Get the first post ID from the db
$query = array(
	'SELECT'	=> 'id',
	'FROM'		=> 'iu_images',
	'ORDER BY'	=> 'id',
	'LIMIT'		=> '1'
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$first_id = $forum_db->result($result);

if (is_null($first_id) || $first_id === false)
{
	unset($first_id);
}
$_SESSION['migrated_items'] = $_SESSION['items_missed_in_messages'] = $_SESSION['messages_ignored'] = $_SESSION['files_missed'] = $_SESSION['messages_updated'] = 0;
$_SESSION['images'] = array();
// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
);

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'reindex');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo 'Migrate uploader' ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p><?php echo 'Migrate info' ?></p>
		</div>
		<form class="frm-form" method="get" accept-charset="utf-8" action="<?php echo forum_link($forum_url['rebuild']) ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['rebuild'])) ?>" />
			</div>

			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo 'Migrate stat legend' ?></span></legend>

				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo 'chunk images per cycle' ?></span> <small></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo $forum_page['fld_count'] ?>" name="i_per_page" size="7" maxlength="7" value="100" /></span>
					</div>
				</div>

				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo 'Starting image id' ?></span> <small></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo $forum_page['fld_count'] ?>" name="i_start_at" size="7" maxlength="7" value="<?php echo (isset($first_id)) ? $first_id : 0 ?>" /></span>
					</div>
				</div>

			</fieldset>

			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="rebuild_index" value="<?php echo 'Migrate start' ?>" /></span>
			</div>
		</form>
	</div>
<?php



$tpl_temp = forum_trim(ob_get_contents());


$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';


exit;

