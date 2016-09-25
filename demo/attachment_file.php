<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$spider = new phpspider();

$spider->on_attachment_file = function($url, $filetype, $phpspider) 
{
    // 输出文件URL地址和文件类型
    //var_dump($url, $filetype);

    if ($filetype == 'jpg') 
    {
        // 以纳秒为单位生成随机数
        $filename = uniqid();
        // 在data目录下生成图片
        $filepath = PATH_DATA."/{$filename}.jpg";
        // 用系统自带的下载器wget下载
        exec("wget {$url} -O {$filepath}");

        // 用PHP函数下载，容易耗尽内存，慎用
        //$data = file_get_contents($attachment_url);
        //file_put_contents($filepath, $attachment_url);
    }
};

$url = "http://ocnt0imhl.bkt.clouddn.com/imgs/1637/2015-07/k306n1wzvkq669nm.jpg";
$url = "http://www.epooll.com/archives/806/";
$spider->request_url($url);
