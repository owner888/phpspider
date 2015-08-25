<?php

include "phpspider/config.php";
include "phpspider/db.php";
include "phpspider/cache.php";
include "phpspider/rolling_curl.php";
include "user.php";

$cookie = trim(file_get_contents("cookie.txt"));

$curl = new rolling_curl();
$curl->set_cookie($cookie);
$curl->set_gzip(true);
$curl->callback = function($response, $info, $request, $error) {

    preg_match("@http://www.zhihu.com/people/(.*?)/@i", $request['url'], $out);
    $username = $out[1];

    // 更新采集时间, 让队列每次都取到不同的用户，形成采集死循环
    $server_data['info_uptime'] = time();
    $server_data['info_progress_id'] = posix_getpid();
    $server_data['info_server_id'] = 1;

    if (empty($response)) 
    {
        db::update('user', $server_data, "`username`='{$username}'");
        file_put_contents("./data/error_timeout.log", date("Y-m-d H:i:s") . ' ' . $username.' --- '.json_encode($error)."\n", FILE_APPEND);
        // 注意这里不要用 exit，否则整个程序就断开了
        return;
    }

    // 如果不是about的
    if (strpos($request['url'], 'about') !== false)
    {
        $data = get_user_about($response);
        if (empty($data)) 
        {
            db::update('user', $server_data, "`username`='{$username}'");
            file_put_contents("./data/error_emptydata.log", date("Y-m-d H:i:s") . ' ' . $username." about data not exists --- \n", FILE_APPEND);
            return;
        }

        $data = array_merge($data, $server_data);
        db::update('user', $data, "`username`='{$username}'");
        //file_put_contents("./data/info/".$username.".json", json_encode($data));
        return;
    }

    $data = get_user($response);
    if (empty($data)) 
    {
        db::update('user', $server_data, "`username`='{$username}'");
        file_put_contents("./data/error_emptydata.log", date("Y-m-d H:i:s") . ' ' . $username." info data not exists --- \n", FILE_APPEND);
        return;
    }
    $data['last_message_week'] = empty($data['last_message_time']) ? 7 : intval(date("w", $data['last_message_time']));
    $data['last_message_hour'] = empty($data['last_message_time']) ? 24 : intval(date("H", $data['last_message_time']));
    $data = array_merge($data, $server_data);
    db::update('user', $data, "`username`='{$username}'");
    //file_put_contents("./data/about/".$username.".json", json_encode($data));
};

for ($j = 0; $j < 1; $j++) 
{
    for ($i = 0; $i < 10; $i++) 
    {
        $username = get_user_queue('info');
        $username = addslashes($username);
        $url = "http://www.zhihu.com/people/{$username}/about";
        $curl->get($url);
        $url = "http://www.zhihu.com/people/{$username}/";
        $curl->get($url);
    }
    $data = $curl->execute();
    // 睡眠100毫秒，太快了会被认为是ddos
    usleep(100000);
}
