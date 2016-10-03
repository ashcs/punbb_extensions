<?php
defined('FORUM_ROOT') or exit();

class Installer {

    private static $loginza_to_ulogin = array (
        'vkontakte'	=>	array('vk.com', 'vkontakte.ru'),
        'twitter'	=>	'twitter',
        'mailru'	=>	'mail.ru',
        'facebook'	=>	'facebook',
        'odnoklassniki'	=>	array('odnoklassniki', 'ok.ru'),
        'yandex'	=>	'yandex',
        'google'	=>	'www.google',
        'steam'	=>	'steam',
        'soundcloud'	=>	'soundcloud',
        'lastfm'	=>	'lastfm',
        'linkedin'	=>	'linkedin',
        'flickr'	=>	'flickr',
        'livejournal'	=>	'livejournal',
        'openid'	=>	'openid',
        'webmoney'	=>	'webmoney',
        'youtube'	=>	'youtube',
        'foursquare'	=>	'foursquare',
        'tumblr'	=>	'tumblr',
        'googleplus'	=>	'plus.google',
        'vimeo'	=>	'vimeo',
        'instagram'	=>	'instagram',
        'wargaming'	=>	'wargaming.net',
    );
    
    
    private static $config = array(
        'o_ulogin_id'   => '',
        'o_ulogin_force_reg'   => '1',
    );

    private static $schema = array(
        'ulogin' => array(
            'FIELDS' => array(
                'id' => array(
                    'datatype' => 'SERIAL',
                    'allow_null' => false
                ),
                'user_id' => array(
                    'datatype' => 'INT(10) UNSIGNED',
                    'allow_null' => false,
                    'default' => '0'
                ),
                'email' => array(
                    'datatype' => 'VARCHAR(80)',
                    'allow_null' => true
                ),
                'network' => array(
                    'datatype' => 'VARCHAR(80)',
                    'allow_null' => true
                ),
                'identity' => array(
                    'datatype' => 'VARCHAR(255)',
                    'allow_null' => true
                ),
                'uid' => array(
                    'datatype' => 'VARCHAR(80)',
                    'allow_null' => true
                ),
                'nickname' => array(
                    'datatype' => 'VARCHAR(80)',
                    'allow_null' => true
                ),
                'manual' => array(
                    'datatype' => 'VARCHAR(255)',
                    'allow_null' => true
                ),
                'response' => array(
                    'datatype' => 'TEXT',
                    'allow_null' => true
                )
            )
            ,
            'PRIMARY KEY' => array(
                'id'
            ),
            'INDEXES' => array(
                'ulogin_user_id_idx' => array(
                    'user_id'
                ),
                'ulogin_user_email' => array(
                    'email'
                ),
            )
        )
    );

    static function install()
    {
        global $forum_db, $forum_config;
        
        foreach (self::$schema as $table_name => $schema)
        {
            $forum_db->create_table($table_name, $schema);
        }
        
        foreach (self::$config as $key => $value)
        {
            if (!isset($forum_config[$key])) {
                forum_config_add($key, $value);
            }
        }
        self::loginza_migrate();
    }

    static function uninstall($cache_path = null)
    {
        global $forum_db;
        
        forum_config_remove(array_keys(self::$config));
        foreach (self::$schema as $table_name => $schema)
        {
            $forum_db->drop_table($table_name);
        }

    }
    
    private static function loginza_migrate()
    {
        global $forum_db;
        
        if ($forum_db->field_exists('users', 'loginza_identity')) {
            
            $query = array(
                'SELECT'	=> 'id, username, email, loginza_identity, loginza_uid, loginza_provider',
                'FROM'		=> 'users',
                'WHERE'		=> 'loginza_identity  IS NOT NULL'
            );
            
            $result = $forum_db->query_build($query) or false;
            while (true == ($cur_user = $forum_db->fetch_assoc($result))) {
                $network = self::network_from_loginza_provider($cur_user['loginza_provider']);
                if ($network == false) {
                    continue;
                }
                else {
                    $query = array(
                        'INSERT'	=> 'user_id, email, network, identity, uid, nickname, manual, response',
                        'INTO'		=> 'ulogin',
                        'VALUES'	=> '\''.$cur_user['id'].'\', \''.$forum_db->escape($cur_user['email']).'\', \''.$forum_db->escape($network).'\', \''.$forum_db->escape($cur_user['loginza_identity']).'\', \''.$forum_db->escape($cur_user['loginza_uid']).'\', \''.$forum_db->escape($cur_user['username']).'\', \' \', \''.$forum_db->escape(serialize(array())).'\''
                    );
                    
                    $forum_db->query_build($query) or error(__FILE__, __LINE__);                    
                }
            }                        
        }
    }

    private static function network_from_loginza_provider($loginza_provider)
    {
        foreach (static::$loginza_to_ulogin as $network => $loginza_token) {
            if (is_array($loginza_token)) {
                foreach ($loginza_token as $cur_token) {
                    $pos = strpos($loginza_provider, $cur_token);
                    if ($pos !== false) {
                        return $network;
                    }
                }
            }
            else {
                $pos = strpos($loginza_provider, $loginza_token);
                if ($pos !== false) {
                    return $network;
                }
            }
        }
        return false;
    }
}