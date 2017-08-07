<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */


$configs = array(
    'name' => '微信公共账号-传送门',
    'interval' => 350,
    'max_try' => 30,
    'timeout' => 20,
    'tasknum' => 5,
    'log_show' => false,
    'save_running_state' => true,
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36',
    'proxy' => 'http://H784U84R444YABQD:57A8B0B743F9B4D2@proxy.abuyun.com:9010',
    //'proxy' => 'http://H6F9I7K565Y49A5P:90C146A1FE287149@proxy.abuyun.com:9010',
    'domains' => array(
        'chuansong.me',
        'www.chuansong.me'
    ),
    'scan_urls' => array(
        'http://chuansong.me/'
    ),
    'list_url_regexes' => array(
        "http://chuansong.me/account/*+",
        "http://chuansong.me/\?start=\d+",
        "http://chuansong.me/[A-Za-z]{2,20}",
        "http://chuansong.me/[A-Za-z]{2,20}/\?start=\d+"
    ),
    'content_url_regexes' => array(
        "http://chuansong.me/n/\d+?",
    ),
    //'export' => array(
    //'type' => 'csv',
    //'file' => PATH_DATA.'/qiushibaike.csv',
    //),
    //'export' => array(
    //'type'  => 'sql',
    //'file'  => PATH_DATA.'/qiushibaike.sql',
    //'table' => 'content',
    //),
    'export' => array(
        'type' => 'db',
        'table' => 'chuansongme',
    ),
    'fields' => array(
        array(
            'name' => "article_title",
            'selector' => "//*[@id='activity-name']",
            'required' => true,
        ),
/*
        array(
            'name' => "article_author",
            'selector' => "//*[@id='img-content']/div[1]/em[2]",
            'required' => true,
        ),
 */
        array(
            'name' => "article_account_name",
            'selector' => "//*[@id='post-user']",
            'required' => true,
        ),
        array(
            'name' => "article_account_id",
            'selector' => "//*[@id='post-user']/@href",
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => "//*[@id='js_content']",
            'required' => true,
        ),
        array(
            'name' => "article_publish_time",
            'selector' => "//*[@id='post-date']",
            'required' => true,
        ),
        array(
            'name' => "article_url",
            'selector' => "/html/head/title",   // 这里随便设置，on_extract_field回调里面会替换
            'required' => true,
        ),
        array(
            'name' => "depth",
            'selector' => "/html/head/title",   // 这里随便设置，on_extract_field回调里面会替换
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function($phpspider)
{
    //requests::add_header("Referer", "http://buluo.qq.com/p/index.html");
    //requests::add_cookie("name", "yangzetao");
    //$phpspider->add_header('Referer','http://www.mafengwo.cn/mdd/citylist/21536.html');
    //$spider->add_header('Proxy-Switch-Ip','yes');
};

$spider->on_status_code = function($status_code, $url, $content, $phpspider)
{
    // 如果状态码为429，说明对方网站设置了不让同一个客户端同时请求太多次
    if ($status_code == '429')
    {
        $phpspider->add_url($url);
        return false;
    }
    // 如果状态码为503，说明服务器繁忙
    elseif ($status_code == '503')
    {
        $phpspider->add_url($url);
        return false;
    }
    return $content;
};

$spider->is_anti_spider = function($url, $content, $phpspider)
{
    // $content中包含"404页面不存在"字符串
    if (strpos($content, "你是爬虫吗(Are you a robot)") !== false)
    {
        // 将url插入待爬的队列中,等待再次爬取
        $phpspider->add_url($url);
        return true; // 返回当前网页被反爬虫了
    }
    return false;
};

$spider->on_extract_field = function($fieldname, $data, $page)
{
    // if ($fieldname == 'article_title')
    // {
    //     if (strlen($data) > 100)
    //     {
    //         // 下面方法截取中文会有异常
    //         //$data = substr($data, 0, 10)."...";
    //         $data = mb_substr($data, 0, 60, 'UTF-8')."...";
    //     }
    // }
    //elseif ($fieldname == 'article_publish_time')
    // {
    //     // 用当前采集时间戳作为发布时间
    //     // $data = time();
    // }
    // 把当前内容页URL替换上面的field
    //elseif ($fieldname == 'article_url')
    if ($fieldname == 'article_title')
    {
        $data = trim($data);
    }
    elseif ($fieldname == 'article_url')
    {
        $data = $page['url'];
    }
    elseif ($fieldname == 'depth')
    {
        $data = $page['request']['depth'];
    }
    // 处理公共微信ID，例：/account/gobybike → gobybike
    elseif ($fieldname == 'article_account_id')
    {
        $data = str_ireplace('/account/','',$data);
    }
    return $data;
};

$spider->start();
