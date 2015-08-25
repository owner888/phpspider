<?php
/**
 * 获取知乎关联用户 和 用户信息
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author seatle<seatle@foxmail.com>
 * @copyright seatle<seatle@foxmail.com>
 * @link http://www.epooll.com/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 获取用户详细信息
 * 
 * @param string $username
 * @return void
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-07-28 09:46
 */
function get_user_about($content)
{
    $data = array();

    if (empty($content)) 
    {
        return $data;
    }

    // 一句话介绍
    preg_match('#<span class="bio" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['headline'] = empty($out[1]) ? '' : $out[1];

    // 头像
    //preg_match('#<img alt="龙威廉"\ssrc="(.*?)"\sclass="zm-profile-header-img zg-avatar-big zm-avatar-editor-preview"/>#', $content, $out);
    preg_match('#<img class="avatar avatar-l" alt=".*?" src="(.*?)" srcset=".*?" />#', $content, $out);
    $data['headimg'] = empty($out[1]) ? '' : $out[1];

    // 居住地
    preg_match('#<span class="location item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['location'] = empty($out[1]) ? '' : $out[1];

    // 所在行业
    preg_match('#<span class="business item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['business'] = empty($out[1]) ? '' : $out[1];

    // 性别
    preg_match('#<span class="item gender" ><i class="icon icon-profile-(.*?)"></i></span>#', $content, $out);
    $gender = empty($out[1]) ? 'other' : $out[1];
    if ($gender == 'female') 
        $data['gender'] = 0;
    elseif ($gender == 'male') 
        $data['gender'] = 1;
    else
        $data['gender'] = 2;

    // 公司或组织名称
    preg_match('#<span class="employment item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['employment'] = empty($out[1]) ? '' : $out[1];

    // 职位
    preg_match('#<span class="position item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['position'] = empty($out[1]) ? '' : $out[1];

    // 学校或教育机构名
    preg_match('#<span class="education item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['education'] = empty($out[1]) ? '' : $out[1];

    // 专业方向
    preg_match('#<span class="education-extra item" title=["|\'](.*?)["|\']>#', $content, $out);
    $data['education_extra'] = empty($out[1]) ? '' : $out[1];

    // 新浪微博
    preg_match('#<a class="zm-profile-header-user-weibo" target="_blank" href="(.*?)"#', $content, $out);
    $data['weibo'] = empty($out[1]) ? '' : $out[1];

    // 个人简介
    preg_match('#<span class="content">\s(.*?)\s</span>#s', $content, $out);
    $data['description'] = empty($out[1]) ? '' : trim(strip_tags($out[1]));

    // 关注了、关注者
    preg_match('#<span class="zg-gray-normal">关注了</span><br />\s<strong>(.*?)</strong><label> 人</label>#', $content, $out);
    $data['followees'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#<span class="zg-gray-normal">关注者</span><br />\s<strong>(.*?)</strong><label> 人</label>#', $content, $out);
    $data['followers'] = empty($out[1]) ? 0 : intval($out[1]);

    // 关注专栏
    preg_match('#<strong>(.*?) 个专栏</strong>#', $content, $out);
    $data['followed'] = empty($out[1]) ? 0 : intval($out[1]);

    // 关注话题
    preg_match('#<strong>(.*?) 个话题</strong>#', $content, $out);
    $data['topics'] = empty($out[1]) ? 0 : intval($out[1]);

    // 关注专栏
    preg_match('#个人主页被 <strong>(.*?)</strong> 人浏览#', $content, $out);
    $data['pv'] = empty($out[1]) ? 0 : intval($out[1]);

    // 提问、回答、专栏文章、收藏、公共编辑
    preg_match('#提问\s<span class="num">(.*?)</span>#', $content, $out);
    $data['asks'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#回答\s<span class="num">(.*?)</span>#', $content, $out);
    $data['answers'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#专栏文章\s<span class="num">(.*?)</span>#', $content, $out);
    $data['posts'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#收藏\s<span class="num">(.*?)</span>#', $content, $out);
    $data['collections'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#公共编辑\s<span class="num">(.*?)</span>#', $content, $out);
    $data['logs'] = empty($out[1]) ? 0 : intval($out[1]);

    // 赞同、感谢、收藏、分享
    preg_match('#<strong>(.*?)</strong> 赞同#', $content, $out);
    $data['votes'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#<strong>(.*?)</strong> 感谢#', $content, $out);
    $data['thanks'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#<strong>(.*?)</strong> 收藏#', $content, $out);
    $data['favs'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#<strong>(.*?)</strong> 分享#', $content, $out);
    $data['shares'] = empty($out[1]) ? 0 : intval($out[1]);
    return $data;
}

function get_user($content)
{
    $data = array();

    if (empty($content)) 
    {
        return $data;
    }
    // 从用户主页获取用户最后一条动态信息
    preg_match('#<div class="zm-profile-section-item zm-item clearfix" data-time="(.*?)"#', $content, $out);
    $data['last_message_time'] = empty($out[1]) ? 0 : intval($out[1]);
    preg_match('#<div class="zm-profile-section-main zm-profile-section-activity-main zm-profile-activity-page-item-main">(.*?)</div>#s', $content, $out);
    $data['last_message'] = empty($out[1]) ? 0 : trim(str_replace("\n", " ", strip_tags($out[1])));
    return $data;
}

/**
 * 保存用户信息
 * 
 * @param object $worker
 * @return array
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-08-02 12:30
 */
function save_user_info($worker = null)
{
    // 先给一条记录上锁
    $progress_id = posix_getpid();
    $time = time();

    // 不要按照uptime排序然后又去更新uptime，锁的索引值会变成整张表，如果所有进程都锁住整张表，就会出现死锁
    //$sql = "Update `user` Set `info_progress_id`='{$progress_id}', `info_uptime`='{$time}' Order By `info_uptime` Asc Limit 1";
    //$sql = "Update `user` Set `info_progress_id`='{$progress_id}' Order By `info_uptime` Asc Limit 1";
    //db::query($sql);
    // 因为uptime在下面修改，所以这里还是正序
    //$sql = "Select `username` From `user` Where `info_progress_id`='{$progress_id}' Order By `info_uptime` Asc Limit 1";
    //$row = db::get_one($sql);
    //if (!empty($row['username'])) 
    $username = get_user_queue('info');
    if (!empty($username)) 
    {
        $username = addslashes($username);
        $worker->log("采集用户信息 --- " . $username . " --- 开始\n");
        $data = get_user_info($username);
        if (!empty($data)) 
        {
            $worker->log("采集用户信息 --- " . $username . " --- 成功\n");
            // 更新采集时间, 让队列每次都取到不同的用户，形成采集死循环
            $data['info_uptime'] = $time;
            $data['info_progress_id'] = $progress_id;
            $data['last_message_week'] = empty($data['last_message_time']) ? 7 : intval(date("w"));
            $data['last_message_hour'] = empty($data['last_message_time']) ? 24 : intval(date("H"));
            $sql = db::update('user', $data, "`username`='{$username}'", true);
            db::query($sql);
        }
        else 
        {
            $worker->log("采集用户信息 --- " . $username . " --- 失败\n");
            // 更新采集时间, 让队列每次都取到不同的用户，形成采集死循环
            $sql = "Update `user` Set `info_uptime`='{$time}',`info_progress_id`='{$progress_id}' Where `username`='{$username}'";
            db::query($sql);
        }
    }
    else 
    {
        $worker->log("采集用户 ---  队列不存在");
    }
}

/**
 * 获取用户采集队列
 * 
 * @param string $key
 * @param int $count
 * @return void
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-08-03 19:36
 */
function get_user_queue($key = 'list', $count = 10000)
{
    // 如果队列为空, 从数据库取一些
    if (!cache::get_instance()->lsize($key)) 
    {
        $sql = "Select `username`, `{$key}_uptime` From `user` Order By `{$key}_uptime` Asc Limit {$count}";
        $rows = db::get_all($sql);
        foreach ($rows as $row) 
        {
            //echo $row['username'] . " --- " . date("Y-m-d H:i:s", $row[$key.'_uptime']) . "\n";
            cache::get_instance()->lpush($key, $row['username']);
        }
    }
    // 从队列中取出一条数据
    return cache::get_instance()->lpop($key);
}

/**
 * 保存用户索引
 * 
 * @return void
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-08-02 12:30
 */
function save_user_index($worker = null)
{
    // 先给一条记录上锁, 采用队列之后就不需要了，这个多进程下还是有问题
    $progress_id = posix_getpid();
    $time = time();

    // 会和下面的更新采集时间发送死锁，因为Order By 会扫描整张表，虽然desc出来的rows为1，也不知道为什么
    //$sql = "Update `user` Set `index_progress_id`='{$progress_id}' Order By `index_uptime` Asc Limit 1";
    // 效率太低
    //$sql = "Update `user` Set `index_progress_id`='15895' Where `index_uptime` = (Select Min(`index_uptime`) From (Select tmp.* From user tmp) a limit 1);";
    // 语法错误
    //$sql = "Update `user` Set `index_progress_id`='{$progress_id}' Where `index_uptime` = (Select Min(`index_uptime`) From `user`)";
    //db::query($sql);


    //$sql = "Select `username`, `depth` From `user` Where `index_progress_id`='{$progress_id}' Order By `index_uptime` Asc Limit 1";
    //$row = db::get_one($sql);
    //if (!empty($row['username'])) 
    $username = get_user_queue('index');
    if (!empty($username)) 
    {
        $username = addslashes($username);
        // 先把用户深度拿出来，下面要增加1给新用户
        $sql = "Select `depth` From `user` Where `username`='{$username}'";
        $row = db::get_one($sql);
        $depth = $row['depth'];

        // 更新采集时间, 让队列每次都取到不同的用户
        $sql = "Update `user` Set `index_uptime`='{$time}',`index_progress_id`='{$progress_id}' Where `username`='{$username}'";
        db::query($sql);

        $worker->log("采集用户列表 --- " . $username . " --- 开始");
        // $user_rows = get_user_index($username);
        // $user_type followees 、followers
        // 获取关注了
        $followees_user = get_user_index($username, 'followees', $worker);
        $worker->log("采集用户列表 --- " . $username . " --- 关注了 --- 成功");
        // 获取关注者
        $followers_user = get_user_index($username, 'followers', $worker);
        $worker->log("采集用户列表 --- " . $username . " --- 关注者 --- 成功");
        // 合并 关注了 和 关注者
        $user_rows = array_merge($followers_user, $followees_user);

        if (!empty($user_rows)) 
        {
            $worker->log("采集用户列表 --- " . $username . " --- 成功");

            foreach ($user_rows as $user_row) 
            {
                // 子用户
                $c_username = addslashes($user_row['username']);
                $sql = "Select Count(*) As count From `user` Where `username`='{$c_username}'";
                $row = db::get_one($sql);
                // 如果用户不存在
                if (!$row['count']) 
                {
                    $user_row['depth'] = $depth+1;
                    $user_row['parent_username'] = $username;
                    $user_row['addtime'] = $user_row['index_uptime'] = $user_row['info_uptime'] = time();
                    if (db::insert('user', $user_row))
                    {
                        $worker->log("入库用户 --- " . $c_username . " --- 成功");
                    }
                    else 
                    {
                        $worker->log("入库用户 --- " . $c_username . " --- 失败");
                    }
                }
            }
        }
        else 
        {
            $worker->log("采集用户列表 --- " . $username . " --- 失败");
        }
    }
    else 
    {
        $worker->log("采集用户 ---  队列不存在");
    }
}

/**
 * 获取用户
 * 
 * @param string $username
 * @param string $user_type followees 、followers
 * @return void
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-07-28 09:46
 */
function get_user_index($username, $user_type = 'followees', $worker)
{
    $url = "http://www.zhihu.com/people/{$username}/{$user_type}";
    set_cookie();
    cls_curl::set_gzip(true);
    $content = cls_curl::get($url);

    if (empty($content)) 
    {
        return array();
    }

    $users = array();

    // 用户不足20个的时候，从ajax取不到用户，所以首页这里还是要取一下
    preg_match_all('#<h2 class="zm-list-content-title"><a data-tip=".*?" href="http://www.zhihu.com/people/(.*?)" class="zg-link" title=".*?">(.*?)</a></h2>#', $content, $out);
    $count = count($out[1]);
    for ($i = 0; $i < $count; $i++) 
    {
        $d_username = empty($out[1][$i]) ? '' : $out[1][$i]; 
        $d_nickname = empty($out[2][$i]) ? '' : $out[2][$i]; 
        if (!empty($d_username) && !empty($d_nickname)) 
        {
            $users[$d_username] = array(
                'username'=>$d_username,
                'nickname'=>$d_nickname,
            );
        }
    }

    $keyword = $user_type == 'followees' ? '关注了' : '关注者';
    $worker->log("采集用户 --- " . $username . " --- {$keyword} --- 主页 --- 成功\n");

    preg_match('#<span class="zg-gray-normal">'.$keyword.'</span><br />\s<strong>(.*?)</strong><label> 人</label>#', $content, $out);
    $user_count = empty($out[1]) ? 0 : intval($out[1]);

    preg_match('#<input type="hidden" name="_xsrf" value="(.*?)"/>#', $content, $out);
    $_xsrf = empty($out[1]) ? '' : trim($out[1]);

    preg_match('#<div class="zh-general-list clearfix" data-init="(.*?)">#', $content, $out);
    $url_params = empty($out[1]) ? '' : json_decode(html_entity_decode($out[1]), true);
    if (!empty($_xsrf) && !empty($url_params) && is_array($url_params)) 
    {
        $url = "http://www.zhihu.com/node/" . $url_params['nodename'];
        $params = $url_params['params'];

        $j = 1;
        for ($i = 0; $i < $user_count; $i=$i+20) 
        {
            $params['offset'] = $i;
            $post_data = array(
                'method'=>'next',
                'params'=>json_encode($params),
                '_xsrf'=>$_xsrf,
            );
            $content = cls_curl::post($url, $post_data);
            if (empty($content)) 
            {
                $worker->log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 失败\n");
                continue;
            }
            $rows = json_decode($content, true);
            if (empty($rows['msg']) || !is_array($rows['msg'])) 
            {
                $worker->log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 失败\n");
                continue;
            }
            $worker->log("采集用户 --- " . $username . " --- {$keyword} --- 第{$j}页 --- 成功\n");

            foreach ($rows['msg'] as $row) 
            {
                preg_match_all('#<h2 class="zm-list-content-title"><a data-tip=".*?" href="http://www.zhihu.com/people/(.*?)" class="zg-link" title=".*?">(.*?)</a></h2>#', $row, $out);
                $d_username = empty($out[1][0]) ? '' : $out[1][0]; 
                $d_nickname = empty($out[2][0]) ? '' : $out[2][0]; 
                if (!empty($d_username) && !empty($d_nickname)) 
                {
                    $users[$d_username] = array(
                        'username'=>$d_username,
                        'nickname'=>$d_nickname,
                    );
                }
            }
            $j++;
        }
    }
    return $users;
}

