<?php

declare(strict_types=1);
/**
 * This file is part of zhuchunshu.
 * @link     https://github.com/zhuchunshu
 * @document https://github.com/zhuchunshu/super-forum
 * @contact  laravel@88.com
 * @license  https://github.com/zhuchunshu/super-forum/blob/master/LICENSE
 */
namespace App\Plugins\Xiuno\src\Command;

use App\Plugins\Comment\src\Model\TopicComment;
use App\Plugins\Core\src\Models\Post;
use App\Plugins\Topic\src\Models\Topic;
use App\Plugins\Topic\src\Models\TopicTag;
use App\Plugins\User\src\Models\User;
use App\Plugins\User\src\Models\UserClass;
use App\Plugins\User\src\Models\UsersOption;
use App\Plugins\User\src\Models\UserUpload;
use App\Plugins\Xiuno\src\Model\XiunoMigrate;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use HyperfExt\Hashing\Hash;
use Illuminate\Support\Str;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
#[Command]
class Xiuno extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \App\Plugins\Xiuno\src\Service\Xiuno
     */
    #[Inject]
    protected $xiuno;

    private string $prefix;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('plugin:xiuno');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('xiuno数据迁移');
    }

    public function handle()
    {
        $this->line(
            <<<'HTML'
 ___________                          _____ __   ___                   
/  ___|  ___|                        / __  \ \ / (_)                  
\ `--.| |_ ___  _ __ _   _ _ __ ___  `' / /' \ V / _ _   _ _ __   ___  
 `--. \  _/ _ \| '__| | | | '_ ` _ \   / /   /   \| | | | | '_ \ / _ \ 
/\__/ / || (_) | |  | |_| | | | | | |./ /___/ /^\ \ | |_| | | | | (_) |
\____/\_| \___/|_|   \__,_|_| |_| |_|\_____/\/   \/_|\__,_|_| |_|\___/                                                                                                                                                                                                                                                                        
HTML
        );
        if ($this->xiuno->conf() === null) {
            $this->line('xiuno配置文件获取失败', 'info');
            exit;
        }
        $this->prefix = $this->xiuno->conf()['prefix'];
        // 迁移upload 目录
        $this->upload();
        // 迁移用户组
        $this->group();
        // 迁移用户
        $this->user();
        // 迁移板块
        $this->forum();
        // 迁移主题
        $this->topic();
        // 迁移评论
        $this->comments();
        // 迁移Post
        $this->post();
        // 整合数据
        $this->data_integration();
        // 迁移帖子附件数据
        $this->attach();
        $this->info('全部数据迁移成功，可以卸载此插件了');
        $this->info('再见👋 ');
    }

    /**
     * 迁移xiuno upload目录.
     */
    private function upload()
    {
        // 迁移upload 目录
        $this->line('开始迁移upload目录资源', );
        // xiuno 所在地址
        $xiuno_path = $this->xiuno->conf()['root_path'];
        $this->xiuno->files($xiuno_path . '/upload');
        $this->info('upload目录资源迁移成功', );
    }

    /**
     * db.
     */
    private function db(): \Medoo\Medoo
    {
        $db = $this->xiuno->conf()['database'];
        return $this->xiuno->db($db['host'], $db['name'], $db['user'], $db['password']);
    }

    /**
     * 迁移xiuno用户组.
     */
    private function group(): void
    {
        // 迁移upload 目录
        $this->line('开始迁移用户组', );
        foreach ($this->db()->select($this->prefix . 'group', ['gid', 'name']) as $data) {
            if (! XiunoMigrate::query()->where(['table' => 'group', '_id' => $data['gid']])->exists()) {
                $uc = UserClass::query()->create([
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
   <circle cx="9" cy="7" r="4"></circle>
   <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
   <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
   <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path>
</svg>',
                    'name' => $data['name'],
                    'color' => '#000000',
                    'quanxian' => '["comment_create","comment_edit","comment_remove","report_comment","report_topic","topic_create","topic_delete","topic_edit","topic_tag_create"]',
                    'permission-value' => '1',
                ]);
                $uid = $uc->id;
                XiunoMigrate::query()->create([
                    'table' => 'group',
                    '_id' => $data['gid'],
                    'sf_id' => $uid,
                ]);
            }
        }
        $this->info('用户组迁移完毕', );
    }

    private function user()
    {
        $this->line('开始迁移用户', );
        foreach ($this->db()->select($this->prefix . 'user', ['uid', 'gid', 'email', 'username', 'qq', 'mobile', 'credits', 'golds', 'create_date', 'avatar']) as $data) {
            if (! XiunoMigrate::query()->where(['table' => 'user', '_id' => $data['uid']])->exists()) {
                // 获取头像
                $dir = substr(sprintf('%09d', $data['uid']), 0, 3);
                // hook model_user_format_avatar_url_before.php
                $avatar = $data['avatar'] ? ('/upload/' . "avatar/{$dir}/{$data['uid']}.png?" . $data['avatar']) : null;
                $userOption = UsersOption::query()->create(['qianming' => 'no bio', 'qq' => $data['qq'], 'phone' => $data['mobile'], 'golds' => $data['golds'], 'credits' => $data['credits']]);
                if (! XiunoMigrate::query()->where(['table' => 'group', '_id' => $data['gid']])->exists()) {
                    $gid = UserClass::query()->first()->id;
                } else {
                    $gid = XiunoMigrate::query()->where(['table' => 'group', '_id' => $data['gid']])->first()->sf_id;
                }
                $user = User::query()->create([
                    'avatar' => $avatar,
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password' => Hash::make(Str::random(12)),
                    'class_id' => $gid,
                    'email_ver_time' => date('Y-m-d H:i:s', (int) $data['create_date']),
                    '_token' => Str::random(),
                    'options_id' => $userOption->id,
                    'created_at' => date('Y-m-d H:i:s', (int) $data['create_date']),
                ]);
                XiunoMigrate::query()->create([
                    'table' => 'user',
                    '_id' => $data['uid'],
                    'sf_id' => $user->id,
                ]);
            }
        }
        $this->info('用户迁移完毕', );
    }

    /**
     * 迁移板块.
     */
    private function forum()
    {
        $this->line('开始迁移板块', );
        $forums = $this->db()->select($this->prefix . 'forum', ['fid', 'name', 'brief']);
        foreach ($forums as $forum) {
            if (! XiunoMigrate::query()->where(['table' => 'forum', '_id' => $forum['fid']])->exists()) {
                $tag = TopicTag::create([
                    'name' => $forum['name'],
                    'color' => '#000000',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-news" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
   <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
   <path d="M16 6h3a1 1 0 0 1 1 1v11a2 2 0 0 1 -4 0v-13a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1v12a3 3 0 0 0 3 3h11"></path>
   <line x1="8" y1="8" x2="12" y2="8"></line>
   <line x1="8" y1="12" x2="12" y2="12"></line>
   <line x1="8" y1="16" x2="12" y2="16"></line>
</svg>',
                    'description' => @$forums['brief'] ?: '暂无描述',
                ]);
                XiunoMigrate::query()->create([
                    'table' => 'forum',
                    '_id' => $forum['fid'],
                    'sf_id' => $tag->id,
                ]);
            }
        }
        $this->info('板块数据迁移完毕', );
    }

    /**
     * 迁移 post.
     */
    private function post()
    {
        $this->line('开始迁移post表', );
        $posts = $this->db()->select($this->prefix . 'post', ['tid', 'pid', 'isfirst', 'uid', 'create_date', 'message_fmt']);

        foreach ($posts as $post) {
            if (! XiunoMigrate::query()->where(['table' => 'post', '_id' => $post['pid']])->exists()) {
                $_post = Post::create([
                    'content' => $post['message_fmt'],
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
                    'topic_id' => 0,
                    'comment_id' => 0,
                    'user_id' => $post['uid'],
                    'created_at' => date('Y-m-d H:i:s', (int) $post['create_date']),
                    'updated_at' => date('Y-m-d H:i:s', (int) $post['create_date']),
                ]);
                XiunoMigrate::create([
                    'table' => 'post',
                    '_id' => $post['pid'],
                    'sf_id' => $_post->id,
                ]);
            }
        }
        $this->info('Post数据迁移完毕', );
    }

    /**
     * 迁移 主题.
     */
    private function topic()
    {
        $this->line('开始迁移主题(文章)', );
        $thread = $this->db()->select($this->prefix . 'thread', ['fid', 'tid', 'uid', 'create_date', 'last_date', 'subject', 'views', 'firstpid']);
        foreach ($thread as $data) {
            if (! XiunoMigrate::query()->where(['table' => 'thread', '_id' => $data['tid']])->exists()) {
                $topic = Topic::create([
                    'title' => $data['subject'],
                    'post_id' => 0,
                    'user_id' => XiunoMigrate::query()->where(['table' => 'user', '_id' => $data['uid']])->first()->sf_id ?: User::query()->first()->id,
                    'status' => 'publish',
                    'view' => $data['views'],
                    'tag_id' => XiunoMigrate::query()->where(['table' => 'forum', '_id' => $data['fid']])->first()->sf_id ?: TopicTag::query()->first()->id,
                    'created_at' => date('Y-m-d H:i:s', (int) $data['create_date']),
                    'updated_at' => date('Y-m-d H:i:s', (int) $data['last_date']),
                ]);
                XiunoMigrate::query()->create([
                    'table' => 'thread',
                    '_id' => $data['tid'],
                    'sf_id' => $topic->id,
                ]);
            }
        }
        $this->info('主题数据迁移完毕', );
    }

    private function comments()
    {
        $this->line('开始迁移评论', );
        $comments = $this->db()->select($this->prefix . 'post', ['tid', 'pid', 'isfirst', 'uid', 'create_date', 'quotepid'], [
            'isfirst' => 0,
        ]);

        foreach ($comments as $comment) {
            // 评论不存在则创建
            if (! XiunoMigrate::query()->where(['table' => 'comment', '_id' => $comment['pid']])->exists()) {
                if ((int) $comment['quotepid'] === 0) {
                    // 创建评论
                    $_comment = TopicComment::create([
                        'post_id' => 0,
                        'topic_id' => XiunoMigrate::query()->where(['table' => 'thread', '_id' => $comment['tid']])->first()->sf_id,
                        'user_id' => XiunoMigrate::query()->where(['table' => 'user', '_id' => $comment['uid']])->first()->sf_id ?: User::query()->first()->id,
                        'status' => 'publish',
                        'created_at' => date('Y-m-d H:i:s', (int) $comment['create_date']),
                        'updated_at' => date('Y-m-d H:i:s', (int) $comment['create_date']),
                    ]);
                    XiunoMigrate::query()->create([
                        'table' => 'comment',
                        '_id' => $comment['pid'],
                        'sf_id' => $_comment->id,
                    ]);
                } else {
                    // 创建回复
                    $_comment = TopicComment::create([
                        'post_id' => 0,
                        'parent_id' => XiunoMigrate::query()->where(['table' => 'comment', '_id' => $comment['quotepid']])->first()->sf_id ?: null,
                        'topic_id' => XiunoMigrate::query()->where(['table' => 'thread', '_id' => $comment['tid']])->first()->sf_id,
                        'user_id' => XiunoMigrate::query()->where(['table' => 'user', '_id' => $comment['uid']])->first()->sf_id ?: User::query()->first()->id,
                        'status' => 'publish',
                        'created_at' => date('Y-m-d H:i:s', (int) $comment['create_date']),
                        'updated_at' => date('Y-m-d H:i:s', (int) $comment['create_date']),
                    ]);
                    XiunoMigrate::query()->create([
                        'table' => 'comment',
                        '_id' => $comment['pid'],
                        'sf_id' => $_comment->id,
                    ]);
                }
            }
        }
        $this->info('评论数据迁移完毕', );
    }

    /**
     * 数据整合.
     */
    private function data_integration()
    {
        // 帖子
        foreach (XiunoMigrate::query()->where('table', 'thread')->get() as $item) {
            //sf帖子id
            $topic_id = $item->sf_id;
            // xiuno tid
            $tid = $item->_id;
            // 判断是否需要更新数据
            if (Topic::query()->where(['id' => $topic_id, 'post_id' => '0'])->exists()) {
                // xiuno 帖子信息
                $xiuno_topic = $this->db()->get($this->prefix . 'thread', ['firstpid', 'tid'], [
                    'tid' => $tid,
                ]);
                // xiuno pid
                $xiuno_pid = $xiuno_topic['firstpid'];
                // sf post_id
                $sf_post_id = XiunoMigrate::query()->where(['table' => 'post', '_id' => $xiuno_pid])->first()->sf_id;
                // 更新数据
                Db::table('topic')->where('id', $topic_id)->update([
                    'post_id' => $sf_post_id,
                ]);
                Db::table('posts')->where('id', $sf_post_id)->update([
                    'topic_id' => $topic_id,
                ]);
            }
        }

        // 评论
        foreach (XiunoMigrate::query()->where('table', 'comment')->get() as $item) {
            //sf评论id
            $comment_id = $item->sf_id;
            // xiuno pid
            $pid = $item->_id;
            // 判断是否需要更新数据
            if (TopicComment::query()->where(['id' => $comment_id, 'post_id' => '0'])->exists()) {
                // xiuno 评论信息
                $sf_post_id = XiunoMigrate::query()->where(['table' => 'post', '_id' => $pid])->first()->sf_id;
                // 更新数据
                Db::table('topic_comment')->where('id', $comment_id)->update([
                    'post_id' => $sf_post_id,
                ]);
                Db::table('posts')->where('id', $sf_post_id)->update([
                    'comment_id' => $comment_id,
                ]);
            }
        }
        $this->info('数据整合完毕');
    }

    /**
     * 迁移附件数据.
     */
    private function attach()
    {
        $this->line('开始迁移附件数据', );
        $files = $this->db()->select($this->prefix . 'attach', ['aid', 'pid', 'uid', 'create_date', 'filename']);

        foreach ($files as $file) {
            // 评论不存在则创建
            if (! XiunoMigrate::query()->where(['table' => 'attach', '_id' => $file['aid']])->exists()) {
                $path = public_path('upload/attach/' . $file['filename']);
                $url = ('/upload/attach/' . $file['filename']);
                $user_id = XiunoMigrate::query()->where(['table' => 'user', '_id' => $file['uid']])->first()->sf_id ?: User::query()->first()->id;
                $post_id = XiunoMigrate::query()->where(['table' => 'post', '_id' => $file['pid']])->first()->sf_id ?: null;
                $uf = UserUpload::query()->create([
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'path' => $path,
                    'url' => $url,
                    'created_at' => date('Y-m-d H:i:s', (int) $file['create_date']),
                    'updated_at' => date('Y-m-d H:i:s', (int) $file['create_date']),
                ]);
                XiunoMigrate::query()->create([
                    'table' => 'attach',
                    '_id' => $file['aid'],
                    'sf_id' => $uf->id,
                ]);
            }
        }
        $this->info('附件数据迁移完毕', );
    }
}
