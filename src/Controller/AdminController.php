<?php

declare(strict_types=1);
/**
 * This file is part of zhuchunshu.
 * @link     https://github.com/zhuchunshu
 * @document https://github.com/zhuchunshu/super-forum
 * @contact  laravel@88.com
 * @license  https://github.com/zhuchunshu/super-forum/blob/master/LICENSE
 */
namespace App\Plugins\Xiuno\src\Controller;

use App\Middleware\AdminMiddleware;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: '/admin/xiuno')]
#[Middleware(AdminMiddleware::class)]
class AdminController
{
    #[GetMapping(path: '')]
    public function index()
    {
        // 获取xiuno 配置文件
        $conf = null;
        if (file_exists(plugin_path('Xiuno/conf.txt'))) {
            $conf_file = file_get_contents(plugin_path('Xiuno/conf.txt'));
            if (is_dir($conf_file) && file_exists($conf_file . '/conf/conf.php')) {
                $conf = require $conf_file . '/conf/conf.php';
                $conf['database']=$conf['db']['pdo_mysql']['master'];
            }
        }

        return view('Xiuno::admin.index', ['conf' => $conf]);
    }

    #[PostMapping(path: '')]
    public function set_xiuno_path()
    {
        $path = request()->input('path');
        if (! is_dir($path)) {
            return redirect()->with('danger', '所配置路径不存在')->back()->go();
        }
        file_put_contents(plugin_path('Xiuno/conf.txt'), $path);
        return redirect()->with('success', '保存成功!')->back()->go();
    }
}
