<?php
defined('FORUM_ROOT') or exit();

if (file_exists($ext_info['path'].'/lang/'.$forum_user['language'].'.php'))
    require $ext_info['path'].'/lang/'.$forum_user['language'].'.php';
else
    require $ext_info['path'].'/lang/English.php';

$forum_loader->add_js('//ulogin.ru/js/ulogin.js?v1', array('weight' => 55, 'async' => false, 'group' => FORUM_JS_GROUP_SYSTEM));
$forum_loader->add_js($ext_info['url'].'/js/ulogin.js?v1', array('weight' => 200));


$networks = require('networks.php');



$ulogin_data = array();

$query = array(
    'SELECT'	=> '*',
    'FROM'		=> 'ulogin',
    'WHERE'     => 'user_id='. $forum_user['id']
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

while ($cur_ulogin_data = $forum_db->fetch_assoc($result))
{
    if (array_key_exists($cur_ulogin_data['network'], $networks)) {
        $installed_networks[$cur_ulogin_data['network']] = $networks[$cur_ulogin_data['network']]; 
        unset($networks[$cur_ulogin_data['network']]);
    }
}

?>

<?php if (count($installed_networks) > 0) : ?>
<fieldset class="mf-set set1">
	<legend>
		<span><?php echo $ulogin_lang['Attached networks'] ?></span>
	</legend>
	<div class="mf-box">
	    <div class="mf-item"  id="ulogin-network-attached">
	    <ul class="reg-social-btns">	
<?php foreach ($installed_networks as $name => $value) : ?>
            <li class="reg-btn reg-btn-<?php echo $name ?>">
                <a class="reg-link  attached fa-<?php echo $name ?>" href="javascript:void(0)" data-deletenetwork="<?php echo $name ?>">
                    <?php echo $value ?>
                    <span></span>
                </a>
            </li>	
<?php endforeach; ?>	
	        </div>		
        </ul>
	</div>
</fieldset>
<?php endif; ?>

<?php if (count($networks) > 0) : ?>

<fieldset class="mf-set set2">
	<legend>
		<span><?php echo $ulogin_lang['Available networks'] ?></span>
	</legend>
	<div class="mf-box">
	    <div class="mf-item" id="ulogin-network-available">
	    <div id="uLogin" class="ulogin1" data-ulogin="display=buttons;callback=ulogin_callback" data-csrf_token="<?php echo generate_form_token(forum_link('extensions/ulogin/ulogin.php')) ?>" data-action="<?php echo forum_link('extensions/ulogin/ulogin.php') ?>">
	    <ul class="reg-social-btns">
<?php foreach ($networks as $name => $value) : ?>	

            <li class="reg-btn reg-btn-<?php echo $name ?>">
                <a class="reg-link fa-<?php echo $name ?>" href="javascript:void(0)" data-uloginbutton="<?php echo $name ?>">
                    <?php echo $value ?>
                </a>
            </li>
<?php endforeach; ?>
        </ul>
        </div>
        </div>
	</div>
</fieldset>

<?php endif; ?>