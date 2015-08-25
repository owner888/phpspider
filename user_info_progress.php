<?php

include "phpspider/config.php";
include "phpspider/db.php";
include "phpspider/cache.php";
include "phpspider/worker.php";
include "phpspider/rolling_curl.php";
include "user.php";

$w = new worker();
$w->count = 10;
$w->is_once = true;
$w->log_show = false;

$count = 1;        // 每个进程循环多少次
$w->on_worker_start = function($worker) use ($count) {

    $cookie = trim(file_get_contents("cookie.txt"));
    $curl = new rolling_curl();
    $curl->set_cookie($cookie);
    $curl->set_gzip(true);

    // 更新采集时间, 让队列每次都取到不同的用户，形成采集死循环
    $server_data['info_uptime'] = time();
    $server_data['info_progress_id'] = posix_getpid();
    $server_data['info_server_id'] = 2;

    for ($i = 0; $i < $count; $i++) 
    {
        $username = get_user_queue('info');
        if (empty($username)) 
        {
            return;
        }
        $username = addslashes($username);
        $worker->log("采集用户信息 --- " . $username . " --- 开始\n");

        // 采集用户最后发信息时间和内容 ===========================================================
        $data = array();
        $url = "http://www.zhihu.com/people/{$username}/";
        $curl->get($url);
        $content = $curl->execute();

        if (empty($content)) 
        {
            file_put_contents("./data/error_timeout.log", date("Y-m-d H:i:s") . ' ' . $username."\n", FILE_APPEND);
            db::update('user', $server_data, "`username`='{$username}'");
            return;
        }

        $data = get_user($content);
        if (empty($data)) 
        {
            file_put_contents("./data/error_emptydata.log", date("Y-m-d H:i:s") . ' ' . $username." info data not exists --- \n", FILE_APPEND);
            db::update('user', $server_data, "`username`='{$username}'");
            return;
        }

        //$worker->log("采集用户信息 --- " . $username . " --- 成功\n");
        $data['last_message_week'] = empty($data['last_message_time']) ? 7 : intval(date("w", $data['last_message_time']));
        $data['last_message_hour'] = empty($data['last_message_time']) ? 24 : intval(date("H", $data['last_message_time']));
        $data = array_merge($data, $server_data);
        db::update('user', $data, "`username`='{$username}'");

        // 采集用户详细信息 =======================================================================
        $data = array();
        $url = "http://www.zhihu.com/people/{$username}/about";
        $curl->get($url);
        $content = $curl->execute();

        if (empty($content)) 
        {
            file_put_contents("./data/error_timeout.log", date("Y-m-d H:i:s") . ' ' . $username."\n", FILE_APPEND);
            db::update('user', $server_data, "`username`='{$username}'");
            return;
        }

        $data = get_user_about($content);
        if (empty($data)) 
        {
            file_put_contents("./data/error_emptydata.log", date("Y-m-d H:i:s") . ' ' . $username." about data not exists --- \n", FILE_APPEND);
            db::update('user', $server_data, "`username`='{$username}'");
            return;
        }

        $data = array_merge($data, $server_data);
        db::update('user', $data, "`username`='{$username}'");
    }

}; 
