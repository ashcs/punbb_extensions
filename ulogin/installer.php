<?php
defined('FORUM_ROOT') or exit();

class Installer {

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

}