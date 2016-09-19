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

if (!defined('FORUM_ROOT'))
    define('FORUM_ROOT', '../../');

require FORUM_ROOT.'include/common.php';

if (file_exists('lang/'.$forum_user['language'].'.php'))
    require 'lang/'.$forum_user['language'].'.php';
else
    require 'lang/English.php';

$networks = require('networks.php');

if (isset($_POST['delete'])) {
    
    header('Content-type: application/json; charset=utf-8');
    
    if (!isset($networks[trim($_POST['delete'])])) {
        echo json_encode(array('error' => 'Invalid network'));
        exit();
    }
    
    $query = array(
        'DELETE'	=> 'ulogin',
        'WHERE'		=> 'user_id = '.$forum_user['id'].' AND network = \''.$forum_db->escape($_POST['delete']).'\''
    );
    
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    
    $forum_flash->add_info($ulogin_lang['Network deleted from user']);
    echo json_encode(array('code' => 0, 'reload' => 1));
    exit();
}

if (!isset($_POST['token'])) {
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'invalid token'));
    exit();
}

$ch = curl_init('https://ulogin.ru/token.php?token='.$_POST['token'].'&host='.$_SERVER['HTTP_HOST']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Ulogin API client;');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
 
$result = curl_exec($ch);
 
if ($result === false)
{
    $e = curl_error($ch);
    curl_close($ch);
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'service unavailable', 'message' => $e));
    exit();
} 
else
{
    curl_close($ch);
}
        
$ulogin_data = json_decode($result, true);

/*
 * Если пользователь не гость на форуме, то либо добавляем авторизующую сеть пользователю
 * Либо отказываем
*/
if (!$forum_user['is_guest'])
{
    $query = array(
        'SELECT'	=> '*',
        'FROM'		=> 'ulogin',
        'WHERE'		=> 'uid = \''.$forum_db->escape($ulogin_data['uid']).'\' AND network = \''.$forum_db->escape($ulogin_data['network']).'\''
    );
    
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    // авторизующая сеть занята - отказ
    $ulogin_info = $forum_db->fetch_assoc($result);
    
    if ($ulogin_info) {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('error' => sprintf($ulogin_lang['Network already attached'], forum_htmlencode($networks[$ulogin_data['network']])), 'code' => 0));
        exit();
    }
    
    // авторизующая сеть свободна - присоединяем
    $query = array(
        'INSERT'	=> 'user_id, email, network, identity, uid, nickname, manual, response',
        'INTO'		=> 'ulogin',
        'VALUES'	=> '\''.$forum_user['id'].'\', \''.$forum_db->escape($ulogin_data['email']).'\', \''.$forum_db->escape($ulogin_data['network']).'\', \''.$forum_db->escape($ulogin_data['identity']).'\', \''.$forum_db->escape($ulogin_data['uid']).'\', \''.$forum_db->escape($ulogin_data['nickname']).'\', \''.$forum_db->escape($ulogin_data['manual']).'\', \''.$forum_db->escape(serialize($ulogin_data)).'\''
    );
    
    $forum_db->query_build($query) or error(__FILE__, __LINE__);    
    $forum_flash->add_info($ulogin_lang['Network attachet to user']);
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('reload' => 1, 'code' => 0));
    exit();
}

$query = array(
    'SELECT'	=> '*',
    'FROM'		=> 'ulogin',
    'WHERE'		=> 'email = \''.$forum_db->escape($ulogin_data['email']).'\''
);

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$ulogin_info = array ();

while ($cur_ulogin = $forum_db->fetch_assoc($result)) 
    $ulogin_info[$cur_ulogin['network']] = $cur_ulogin;

/**
 * ulogin не содержит записей с представленным email
 */
if (empty($ulogin_info)) {
    $_SESSION['ulogin_data'] = $ulogin_data;

    /**
     * есть такой зарегистрированный?
     */
    
    $query = array(
        'SELECT'	=> '*',
        'FROM'		=> 'users',
        'WHERE'		=> 'email = \''.$forum_db->escape($ulogin_data['email']).'\''
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    
    $check_user = $forum_db->fetch_assoc($result);

    // предлагаем регистрацию
    if (!isset($check_user['id']))
    {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
        exit();    
    }
    
    // зарегистрированны есть - предлагаем зайти под его данными или восстановить пароль, если забыл
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'user with email already registered', 'code' => -2, 'destination_url' => forum_link($forum_url['login'])));
    exit();
    
}

/**
 *  ulogin содержит запись с соотвествующей авторизующей сетью.
 */
if (isset($ulogin_info[$ulogin_data['network']]))
{
    require FORUM_ROOT.'lang/'.$forum_user['language'].'/login.php';
    
    $query = array(
        'SELECT'	=> 'u.id, u.group_id, u.password, u.salt',
        'FROM'		=> 'users AS u',
        'WHERE'		=> 'id = \''.$forum_db->escape($ulogin_info[$ulogin_data['network']]['user_id']).'\''
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    
    list($user_id, $group_id, $db_password_hash, $salt) = $forum_db->fetch_row($result);
    
    // Есть такой пользователь. авторизовать
    if ($user_id)
    {
        // Remove this user's guest entry from the online list
        $query = array(
            'DELETE'	=> 'online',
            'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
        );
        
        $forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        $expire = time() + $forum_config['o_timeout_visit'];
        forum_setcookie($cookie_name, base64_encode($user_id.'|'.$db_password_hash.'|'.$expire.'|'.sha1($salt.$db_password_hash.forum_hash($expire, $salt))), $expire);

        unset($_SESSION['ulogin_data']);
        
        $forum_flash->add_info($lang_login['Login redirect']);
        
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('message' => $lang_login['Login redirect'], 'code' => -2, 'destination_url' => forum_htmlencode($forum_user['prev_url'])));
        exit();
    }
    else {
        // в users нет записи соответствующей записи в ulogin
        $_SESSION['ulogin_data'] = $ulogin_data;
        $query = array(
            'SELECT'	=> '*',
            'FROM'		=> 'users',
            'WHERE'		=> 'email = \''.$forum_db->escape($ulogin_data['email']).'\''
        );
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
        
        $check_user = $forum_db->fetch_assoc($result);
        // зарегистрированного нет, предлагаем регистрацию
        if (!isset($check_user['id']))
        {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
            exit();
        }
        
        // зарегистрированный есть - предлагаем зайти под его данными или восстановить пароль, если забыл
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'user with email already registered', 'code' => -2, 'destination_url' => forum_link($forum_url['login'])));
        exit();        
    }    
}


$_SESSION['ulogin_data'] = $ulogin_data;
// ulogin не содержит записи соотвествующей авторизующей сети, 
// проверить наличие user с таким email

$query = array(
    'SELECT'	=> '*',
    'FROM'		=> 'users',
    'WHERE'		=> 'email = \''.$forum_db->escape($ulogin_data['email']).'\''
);
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
$check_user = $forum_db->fetch_assoc($result);

// зарегистрированного нет, предлагаем регистрацию
if (!isset($check_user['id']))
{
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
    exit();
}

// зарегистрированный есть - предлагаем зайти под его данными или восстановить пароль, если забыл
header('Content-type: application/json; charset=utf-8');
echo json_encode(array('error' => 'user with email already registered', 'code' => -2, 'destination_url' => forum_link($forum_url['login'])));
exit();

