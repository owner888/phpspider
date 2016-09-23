<?php
include "phpspider/config.php";
include "phpspider/worker.php";
include "phpspider/rolling_curl.php";
include "phpspider/db.php";
include "phpspider/cache.php";
include "phpspider/cls_query.php";
include "user.php";
include "phpspider/cls_curl.php";

function get_slide($content, $country)
{
    $array = array();
    preg_match('@<div id="owl-banner" class="owl-carousel owl-theme">(.*?)<div class="main-block min-h0">@is',$content, $out);
    preg_match_all('@<a href=\'(.*?)\'.*?<img src="(.*?)"@is', $content, $out);
    if (empty($out[1])) 
    {
        return $array;
    }
    $count = count($out[1]);
    for ($i = 0; $i < $count; $i++) 
    {
        $time = time();
        $arr = explode(".", $out[2][$i]);
        $ext = end($arr);
        $filename = uniqid().'.'.$ext;
        $filedata = file_get_contents($out[2][$i]);
        file_put_contents("./images/".$filename, $filedata);
        $num = $i+1;
        $array[] = array(
            'name'=>'slide_'.$num,
            'country'=>$country,
            'url_flag'=>$country,
            'type'=>1,
            'orderid'=>$num,
            'url'=>$out[1][$i],
            'uid'=>7,
            'image'=>$filename,
            'addtime'=>$time,
            'uptime'=>$time,
            'status'=>1,
        );
    }
    return $array;
}

function get_app($content, $country)
{
    $array = array();
    preg_match('@<div class="title">Recommend Apps</div>(.*?)<div class="main-block min-h0">@is',$content, $out);
    preg_match_all('@<li><a href="(.*?)".*?<img src="(.*?)".*?<a class="app-title".*?>(.*?)</a></li>@is', $content, $out);
    print_r($out);
    exit;
    if (empty($out[1])) 
    {
        return $array;
    }
    $count = count($out[1]);
    for ($i = 0; $i < $count; $i++) 
    {
        $time = time();
        $arr = explode(".", $out[2][$i]);
        $ext = end($arr);
        $filename = uniqid().'.'.$ext;
        $filedata = file_get_contents($out[2][$i]);
        file_put_contents("./images/".$filename, $filedata);
        $num = $i+1;
        $array[] = array(
            'name'=>'slide_'.$num,
            'country'=>$country,
            'url_flag'=>$country,
            'type'=>1,
            'orderid'=>$num,
            'url'=>$out[1][$i],
            'uid'=>7,
            'image'=>$filename,
            'addtime'=>$time,
            'uptime'=>$time,
            'status'=>1,
        );
    }
    return $array;
}
$sql = "Select subname From `country` Where subname != 'in' Order By `id` Asc";
$rows = db::get_all($sql);
foreach ($rows as $row) 
{
    $url = "http://best.higames.cc/?channel=".$row['subname'];
    echo $url."\n";
    $content = file_get_contents($url);
    //$slides = get_slide($content, $row['subname']);
    $apps = get_app($content, $row['subname']);
    exit;
}
exit;
$cookie = trim(file_get_contents("cookie.txt"));

$curl = new rolling_curl();
$curl->window_size = 2;
$curl->set_cookie($cookie);
$curl->set_gzip(true);
$curl->callback = function($response, $info, $request, $error) {

    preg_match("@http://www.zhihu.com/people/(.*?)/about@i", $request['url'], $out);
    $username = $out[1];
    //echo $username."\n";

    if (empty($response)) 
    {
        file_put_contents("./timeout/".$username."_info.json", json_encode($info)."\n", FILE_APPEND);
        file_put_contents("./timeout/".$username."_error.json", json_encode($error)."\n", FILE_APPEND);
    }
    else 
    {
        $data = get_user_about($response);
        if (empty($data)) 
        {
            file_put_contents("./timeout_data.txt", $request['url']."\n", FILE_APPEND);
        }
        else 
        {
            file_put_contents("./html/".$username.".json", json_encode($data));
        }
    }

};
for ($i = 0; $i < 50; $i++) 
{
    $username = get_user_queue();
    $username = addslashes($username);
    $url = "http://www.zhihu.com/people/{$username}/about";
    $curl->get($url);
}
$data = $curl->execute();
exit;

$w = new worker();
$w->count = 10;
$w->is_once = true;
$w->log_show = false;

$count = 100;        // 每个进程循环多少次
$w->on_worker_start = function($worker) use ($count) {

    //echo $worker->worker_pid . " --- " . $worker->worker_id."\n";
    $cookie = trim(file_get_contents("cookie.txt"));

    $curl = new rolling_curl();
    $curl->set_cookie($cookie);
    $curl->set_gzip(true);
    $curl->callback = function($response, $info, $request, $error) {

        preg_match("@http://www.zhihu.com/people/(.*?)/about@i", $request['url'], $out);
        $username = $out[1];
        if (empty($response)) 
        {
            var_dump($info);
            file_put_contents("./timeout/".$username."_info.json", json_encode($info)."\n", FILE_APPEND);
            file_put_contents("./timeout/".$username."_error.json", json_encode($error)."\n", FILE_APPEND);
        }
        else 
        {
            $data = get_user_about($response);
            if (empty($data)) 
            {
                file_put_contents("./timeout_data.txt", $request['url']."\n", FILE_APPEND);
            }
            else 
            {
                preg_match("@http://www.zhihu.com/people/(.*?)/about@i", $request['url'], $out);
                file_put_contents("./html/".$out[1].".json", json_encode($data));
            }
        }

    };

    for ($i = 0; $i < $count; $i++) 
    {
        $username = get_user_queue();
        $username = addslashes($username);
        $url = "http://www.zhihu.com/people/{$username}/about";
        $curl->get($url);
        $data = $curl->execute();
    }
}; 

$w->run();

