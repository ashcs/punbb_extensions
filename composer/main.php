<?php

if (!defined('FORUM_ROOT'))
    define('FORUM_ROOT', '../../');

require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';
require 'functions.php';

if ($forum_user['g_id'] != FORUM_ADMIN)
    message($lang_common['No permission']);


if (!isset($_POST['id'])) {
    echo 'ext id not set';
    exit; 
}

if (!file_exists(FORUM_ROOT.'extensions/'.forum_htmlencode($_POST['id']).'/composer.json') &&
    $_POST['action'] != 'install' && $_POST['id'] != 'composer') {
    echo 'ext composer.json not exist';
    exit;
}

if (!isset($_POST['action'])) {
    echo 'action not set';
    exit;
}
else {
    if ($_POST['action'] == 'install') {
        if ($_POST['id'] == 'composer') {
            composer_self_install();
        }
        else {
            composer_install($_POST['id']);
        }
        exit;
    }
    elseif ($_POST['action'] == 'uninstall') {
        composer_uninstall($_POST['id']);
        exit;
    }    
    elseif ($_POST['action'] == 'finish') {
        $_SESSION['composer_passed'] = true;
        header("Content-Type: text/json; charset=utf-8");
        echo json_encode(array('ok'));
        exit;
    }
    else {
        echo 'action onvalid';
        exit;       
    }
}


/*
use Symfony\Component\Console\Input\ArrayInput;
$input = new ArrayInput(array('command' => 'install'));
$application = new Application();
$application->setAutoExit(false); // prevent `$application->run` method from exitting the script
$application->run($input);
*/

