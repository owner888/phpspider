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
    'log_show' => true,
    //'save_running_state' => true,
    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.59 Safari/537.36',
    'proxy' => 'http://H6F9I7K565Y49A5P:90C146A1FE287149@proxy.abuyun.com:9010',
    'domains' => array(
        'chuansong.me',
        'www.chuansong.me'
    ),
    'scan_urls' => array(
        'http://chuansong.me/'
    ),
    'list_url_regexes' => array(
      	"http://chuansong.me/account/.*+",
      	"http://chuansong.me/\?start=\d+$",
        "http://chuansong.me/[A-Za-z]{2,20}$",
        "http://chuansong.me/[A-Za-z]{2,20}/\?start=\d+$"
    ),
    'content_url_regexes' => array(
        "http://chuansong.me/n/\d+",
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
    // $phpspider->add_scan_url("http://chuansong.me/");
    // $phpspider->add_scan_url("http://chuansong.me/select");
    // $phpspider->add_scan_url("http://chuansong.me/auto");
    // $phpspider->add_scan_url("http://chuansong.me/ideatech");
    // $phpspider->add_scan_url("http://chuansong.me/newsmedia");
    // $phpspider->add_scan_url("http://chuansong.me/fun");
    // $phpspider->add_scan_url("http://chuansong.me/lifejourney");
    // $phpspider->add_scan_url("http://chuansong.me/utility");
    // $phpspider->add_scan_url("http://chuansong.me/hisbook");
    // $phpspider->add_scan_url("http://chuansong.me/finance");
    // $phpspider->add_scan_url("http://chuansong.me/food");
    // $phpspider->add_scan_url("http://chuansong.me/moviemusic");
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/select?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/auto?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/ideatech?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/newsmedia?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/fun?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/lifejourney?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/utility?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/hisbook?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/finance?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/food?start=".$x);
    // }
    // for ($x=25; $x<=975; $x+=25) {
    //   //echo "数字是：$x <br>";
    //   $phpspider->add_scan_url("http://chuansong.me/moviemusic?start=".$x);
    // }
    //requests::set_useragents(array(
        //"Mozilla/4.0 (compatible; MSIE 6.0; ) Opera/UCWEB7.0.2.37/28/",
        //"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.1)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1",
        //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; WOW64; Trident/6.0)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)",
        //"Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; ARM; Trident/6.0)",
        //"Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.120 Safari/535.2",
        //"Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.2 (KHTML, like Gecko) Ubuntu/10.04 Chromium/15.0.874.106 Chrome/15.0.874.106 Safari/535.2",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.106 Safari/535.2",
        //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.75 Safari/535.7",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.75 Safari/535.7",
        //"Mozilla/5.0 (Windows NT 5.1; rv:6.0) Gecko/20100101 Firefox/6.0",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:6.0.2) Gecko/20100101 Firefox/6.0.2",
        //"Mozilla/5.0 (Windows NT 6.0; rv:7.0.1) Gecko/20100101 Firefox/7.0.1",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.5; rv:8.0.1) Gecko/20100101 Firefox/8.0.1",
        //"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:9.0.1) Gecko/20100101 Firefox/9.0.1",
        //"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; ja-jp) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2",
        //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8) AppleWebKit/536.25 (KHTML, like Gecko) Version/6.0 Safari/536.25",
        //"Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; LCJB; rv:11.0) like Gecko",
        //"Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36 Core/1.47.933.400 QQBrowser/9.4.8699.400"
    //));
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
    if ($fieldname == 'article_url')
    {
        $data = $page['url'];
    }
    elseif ($fieldname == 'depth')
    {
        $data = preg_replace("@\s@is", '', $page['request']['depth']);
    }
    // 处理公共微信ID，例：/account/gobybike → gobybike
    elseif ($fieldname == 'article_account_id')
    {
        $data = preg_replace("@\s@is", '', str_ireplace('/account/','',$data));
    }
    // 处理空格跟换行
    elseif ($fieldname == 'article_title' || $fieldname == 'article_account_name' || $fieldname == 'article_publish_time')
    {
        $data = preg_replace("@\s@is", '', $data);
    }
    return $data;
};

$spider->start();
