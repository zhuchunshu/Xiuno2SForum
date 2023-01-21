<?php

declare(strict_types=1);
/**
 * This file is part of zhuchunshu.
 * @link     https://github.com/zhuchunshu
 * @document https://github.com/zhuchunshu/super-forum
 * @contact  laravel@88.com
 * @license  https://github.com/zhuchunshu/super-forum/blob/master/LICENSE
 */
namespace App\Plugins\Xiuno;

class Xiuno
{
    public function handler()
    {
        $this->bootstrap();
    }

    private function bootstrap()
    {
        require_once __DIR__ . '/bootstrap.php';
    }
}
