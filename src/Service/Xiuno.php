<?php

declare(strict_types=1);
/**
 * This file is part of zhuchunshu.
 * @link     https://github.com/zhuchunshu
 * @document https://github.com/zhuchunshu/super-forum
 * @contact  laravel@88.com
 * @license  https://github.com/zhuchunshu/super-forum/blob/master/LICENSE
 */
namespace App\Plugins\Xiuno\src\Service;

use Medoo\Medoo;

class Xiuno
{
    /**
     * 获取xiuno 配置.
     */
    public function conf()
    {
        // 获取xiuno 配置文件
        $conf = null;
        if (file_exists(plugin_path('Xiuno/conf.txt'))) {
            $conf_file = file_get_contents(plugin_path('Xiuno/conf.txt'));
            if (is_dir($conf_file) && file_exists($conf_file . '/conf/conf.php')) {
                $conf = require $conf_file . '/conf/conf.php';
                $conf['root_path'] = $conf_file;
                $conf['database'] = $conf['db']['pdo_mysql']['master'];
                $conf['prefix']=$conf['database']['tablepre'];
            }
        }
        return $conf;
    }

    /**
     * 迁移上传的文件.
     * @param mixed $form
     */
    public function files($form)
    {
        copy_dir($form, public_path('upload'));
    }

    /**
     * database connet
     * @param $host
     * @param $database
     * @param $username
     * @param $password
     * @return Medoo
     */
    public function db($host, $database, $username, $password): Medoo
    {
        return new Medoo([
            'type' => 'mysql',
            'host' => $host,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ]);
    }
}
