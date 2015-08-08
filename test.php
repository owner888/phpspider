<?php
//设置时区
date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', 1);
include "config.php";
include "rolling_curl.php";
include "db.php";
include "cache.php";
include "worker.php";
include "user.php";

//$cookie = '_za=36643642-e546-4d60-a771-8af8dcfbd001; q_c1=a57a2b9f10964f909b8d8969febf3ab2|1437705596000|1437705596000; _xsrf=f0304fba4e44e1d008ec308d59bab029; cap_id="YWY1YmRmODlmZGVmNDc3MWJlZGFkZDg3M2E0M2Q5YjM=|1437705596|963518c454bb6f10d96775021c098c84e1e46f5a"; z_c0="QUFCQVgtRWZBQUFYQUFBQVlRSlZUVjR6NEZVUTgtRkdjTVc5UDMwZXRJZFdWZ2JaOWctNVhnPT0=|1438164574|aed6ef3707f246a7b64da4f1e8c089395d77ff2b"; __utma=51854390.1105113342.1437990174.1438160686.1438164116.10; __utmc=51854390; __utmz=51854390.1438134939.8.5.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/people/yangzetao; __utmv=51854390.100-1|2=registration_date=20131030=1^3=entry_date=20131030=1';

//$curl = new rolling_curl();
//$curl->set_cookie($cookie);
//$curl->set_gzip(true);
//$curl->callback = function($response, $info, $request, $error) {
    //$data = get_user_about($response);
    //file_put_contents("./html2/".md5($request['url']).".json", json_encode($data));
//};
//for ($i = 0; $i < 100; $i++) 
//{
    //$username = get_user_queue();
    //$username = addslashes($username);
    //$url = "http://www.zhihu.com/people/{$username}/about";
    //$curl->get($url);
//}
//$data = $curl->execute();

//exit;

$w = new worker();
$w->count = 10;
$w->is_once = true;
$w->log_show = false;

$count = 10;        // 每个进程循环多少次
$w->on_worker_start = function($worker) use ($count) {

    //echo $worker->worker_pid . " --- " . $worker->worker_id."\n";
    $cookie = '_za=36643642-e546-4d60-a771-8af8dcfbd001; q_c1=a57a2b9f10964f909b8d8969febf3ab2|1437705596000|1437705596000; _xsrf=f0304fba4e44e1d008ec308d59bab029; cap_id="YWY1YmRmODlmZGVmNDc3MWJlZGFkZDg3M2E0M2Q5YjM=|1437705596|963518c454bb6f10d96775021c098c84e1e46f5a"; z_c0="QUFCQVgtRWZBQUFYQUFBQVlRSlZUVjR6NEZVUTgtRkdjTVc5UDMwZXRJZFdWZ2JaOWctNVhnPT0=|1438164574|aed6ef3707f246a7b64da4f1e8c089395d77ff2b"; __utma=51854390.1105113342.1437990174.1438160686.1438164116.10; __utmc=51854390; __utmz=51854390.1438134939.8.5.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/people/yangzetao; __utmv=51854390.100-1|2=registration_date=20131030=1^3=entry_date=20131030=1';

    $curl = new rolling_curl();
    $curl->set_cookie($cookie);
    $curl->set_gzip(true);
    $curl->callback = function($response, $info, $request, $error) {
        $data = get_user_about($response);
        file_put_contents("./html/".md5($request['url']).".json", json_encode($data));
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

