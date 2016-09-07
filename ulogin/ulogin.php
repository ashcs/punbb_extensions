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
 * ulogin не содержит записей с представленным мылом
 */
if (empty($ulogin_info)) {
    $_SESSION['ulogin_data'] = $ulogin_data;

    /**
     * Может есть такой зарегистрированный?
     */
    
    $query = array(
        'SELECT'	=> '*',
        'FROM'		=> 'users',
        'WHERE'		=> 'email = \''.$forum_db->escape($ulogin_data['email']).'\''
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
    
    $check_user = $forum_db->fetch_assoc($result);

    // Зареганого нет, предлагаем регистрацию
    if (!isset($check_user['id']))
    {
        header('Content-type: application/json; charset=utf-8');
        echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
        exit();    
    }
    
    // Зареганый есть - предлагаем зайти под его данными или восстановить пароль, если забыл
    // Например редирект на страницу логина и вывод информационного окна 
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'user with email already registered', 'code' => -2, 'destination_url' => forum_link($forum_url['login'])));
    exit();
    
}

// в ulogin есть запись с представленным мылом


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
        // Зареганого нет, предлагаем регистрацию
        if (!isset($check_user['id']))
        {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
            exit();
        }
        
        // Зареганый есть - предлагаем зайти под его данными или восстановить пароль, если забыл
        // Например редирект на страницу логина и вывод информационного окна
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

// Зареганого нет, предлагаем регистрацию
if (!isset($check_user['id']))
{
    header('Content-type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'user not find', 'code' => -2, 'destination_url' => forum_link($forum_url['register'])));
    exit();
}

// Зареганый есть - предлагаем зайти под его данными или восстановить пароль, если забыл
// Например редирект на страницу логина и вывод информационного окна
header('Content-type: application/json; charset=utf-8');
echo json_encode(array('error' => 'user with email already registered', 'code' => -2, 'destination_url' => forum_link($forum_url['login'])));
exit();



header('Content-type: application/json; charset=utf-8');
echo json_encode($ulogin_data);
exit();