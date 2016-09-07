<?php

defined('FORUM_ROOT') or exit('Derect access not allowed');

require_once('extracted/vendor/autoload.php');

function build_composer_json($composer_json, $installable_json)
{
    $sections = ['autoload', 'require', 'repository', 'scripts'];

    foreach ($sections as $cur_section)
    {
        if (isset($installable_json[$cur_section])) {
            if (isset($composer_json[$cur_section])) {
                if ($cur_section == 'autoload') {
                    foreach ($installable_json[$cur_section] as $psr => $psr_content)
                    {
                        if (isset($composer_json[$cur_section][$psr])) {
                            $composer_json[$cur_section][$psr] = array_merge(
                                $composer_json[$cur_section][$psr],
                                $installable_json[$cur_section][$psr]
                            );
                        }
                    }
                }
                else {
                    $composer_json[$cur_section] = array_merge($composer_json[$cur_section], $installable_json[$cur_section]);
                }
            }
            else {
                $composer_json[$cur_section] = $installable_json[$cur_section];
            }
        }
    }
    return $composer_json;
}

function composer_install($ext)
{
    $composer_json = json_decode(file_get_contents(FORUM_ROOT.'extensions/composer/composer.json'), true);
    $installable_json = json_decode(file_get_contents(FORUM_ROOT.'extensions/'.forum_htmlencode($ext).'/composer.json'), true);
    $composer_json = build_composer_json($composer_json, $installable_json);

    file_put_contents(
    FORUM_ROOT.'extensions/composer/composer.json',
    Composer\Json\JsonFormatter::format(
    str_replace("\/", '/', json_encode($composer_json)), false, false
    ));

    if (file_exists(FORUM_ROOT.'extensions/composer/composer.lock')) {
        unlink(FORUM_ROOT.'extensions/composer/composer.lock');
    }
    //run_command('update -o --ignore-platform-reqs --prefer-dist');
    run_command('update -o');
}

function composer_uninstall($ext)
{
    global $forum_db;

    $composer_json = json_decode(file_get_contents(FORUM_ROOT.'extensions/composer/composer.src'), true);

    $query = array(
        'SELECT'	=> 'id',
        'FROM'		=> 'extensions AS e',
        'WHERE'		=> 'e.disabled = 0 AND e.id <> "composer" AND e.id <> "'.$ext.'"'
    );
    $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

    $need_rebuild = false;
    while ($cur_extension = $forum_db->fetch_assoc($result))
    {
        if (file_exists(FORUM_ROOT.'extensions/'.$cur_extension['id'].'/composer.json'))
        {
            $need_rebuild = true;
            $installable_json = json_decode(file_get_contents(FORUM_ROOT.'extensions/'.forum_htmlencode($cur_extension['id']).'/composer.json'), true);
            $composer_json = build_composer_json($composer_json, $installable_json);
        }
    }

    if ($need_rebuild)
    {
        unlink(FORUM_ROOT.'extensions/composer/composer.json');
        file_put_contents(FORUM_ROOT.'extensions/composer/composer.json', Composer\Json\JsonFormatter::format(str_replace("\/", '/', json_encode($composer_json)), false, false));
        //run_command('update -o --ignore-platform-reqs --prefer-dist');
        run_command('update -o');
    }
    else
    {
        echo 'exec_result=0';
    }
}

function composer_self_install()
{
    if (!file_exists(FORUM_ROOT.'extensions/composer/composer.json')) {
        copy(FORUM_ROOT.'extensions/composer/composer.src', FORUM_ROOT.'extensions/composer/composer.json');
    }
    //run_command('install -o --ignore-platform-reqs --prefer-dist');
    run_command('install -o');
}

function composer_self_uninstall() 
{
    @unlink(FORUM_ROOT.'extensions/composer/composer.json');
    @unlink(FORUM_ROOT.'extensions/composer/composer.lock');
}

function run_command($command)
{
    @set_time_limit(-1);
    putenv('COMPOSER_HOME=' . './');
    putenv('COMPOSER_CACHE_DIR=' . './composer_cache');
    $cur_dir = getcwd().'/';
    chdir(__DIR__);
//	system('php -d memory_limit=1024M composer.phar '.$command.' 2>&1');
//	echo 'exec_result=0';
//	return;
    $input = new Symfony\Component\Console\Input\StringInput($command);
    $output = new Symfony\Component\Console\Output\StreamOutput(fopen('php://output','w'));
    $app = new Composer\Console\Application();
    $app->setAutoExit(false);
    $result = $app->run($input,$output);
    $output->writeln('exec_result='.$result);
}
