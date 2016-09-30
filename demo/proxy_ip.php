<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */


$proxy = "http://proxy.abuyun.com:9010";
$proxy_auth = "H569I1428O9U0Z5P:67545392FA7A66E1";
$url = "http://ip.kkk5.com";
$url = "https://www.youtube.com";

$proxy = "127.0.0.1:8008";
cls_curl::set_proxy($proxy);
$html = cls_curl::get($url);
echo $html."\n";
exit;
for ($i = 0; $i < 10; $i++) 
{
    $html = cls_curl::get($url);
    echo $html."\n";
    sleep(5);
}

exit;
$curl = new rolling_curl();
$curl->window_size = 5;
$curl->callback = function($response, $info, $request, $error) {
    if ($error) 
    {
        var_dump($info);
        echo $error."\n";
    }
    else 
    {
        echo $response."\n";
    }
};
$curl->set_proxy($proxy, $proxy_auth);
$curl->set_headers(array('Proxy-Switch-Ip: yes'));
for ($i = 0; $i < 100; $i++) 
{
    $curl->get($url);
}
$data = $curl->execute();


exit;
$curl = new rolling_curl();
$curl->window_size = 1;
for ($i = 0; $i < 10; $i++) 
{
    $curl->set_proxy($proxy, $proxy_auth);
    $curl->callback = function($response, $info, $request, $error) {
        var_dump($response);
    };

    $url = "http://ip.kkk5.com";
    for ($j = 0; $j < 5; $j++) 
    {
        $curl->get($url);
        $data = $curl->execute();
        // 睡眠100毫秒，太快了会被认为是ddos
        usleep(100000);
    }
}
