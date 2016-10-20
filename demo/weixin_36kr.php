<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '36氪',
    'log_show' => true,
    'tasknum' => 1,
    //'save_running_state' => true,
    'domains' => array(
        'chuansong.me',
        'www.chuansong.me'
    ),
    'scan_urls' => array(
        'http://chuansong.me/account/wow36kr/'
    ),
    'list_url_regexes' => array(
        "http://chuansong.me/account/wow36kr?start=\d+"
    ),
    'content_url_regexes' => array(
        "http://chuansong.me/n/\d+",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'conf' => array(
            'host'  => 'localhost',
            'port'  => 3306,
            'user'  => 'root',
            'pass'  => '',
            'name'  => 'demo',
        ),
        'table' => '360ky',
    ),
//    'export' => array(
//        'type' => 'csv',
//        'file' => PATH_DATA.'/360kr.csv',
//    ),
    'fields' => array(
        array(
            'name' => "article_title",
            'selector' => '//*[@id="activity-name"]',
            'required' => true,
        ),
        array(
            'name' => "article_author",
            'selector' => '//*[@id="img-content"]/div[1]/em[2]',
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => '//*[@id="js_content"]//p',
            'repeated' => true,
        ),
        array(
            'name' => "article_publish_time",
            'selector' => "//div[contains(@class,'author')]//h2",
            'required' => true,
        ),
        array(
            'name' => "url",
            'selector' => "//div[contains(@class,'author')]//h2",   // 这里随便设置，on_extract_field回调里面会替换
            'required' => true,
        ),
    ),
);
$spider = new phpspider($configs);
$spider->on_handle_img = function($fieldname, $img) 
{
    $regex = '/src="(https?:\/\/.*?)"/i';
    preg_match($regex, $img, $rs);
    if (!$rs) 
    {
        return $img;
    }
    $url = $rs[1];
    $img = $url;

    return $img;
};
$spider->on_extract_field = function($fieldname, $data, $page) 
{

    
    if ($fieldname == 'article_title') 
    {
        if (strlen($data) > 10) 
        {
            // 下面方法截取中文会有异常
            //$data = substr($data, 0, 10)."...";
            $data = mb_substr($data, 0, 10, 'UTF-8')."...";
        }
    }
    elseif ($fieldname == 'article_publish_time') 
    {
        // 用当前采集时间戳作为发布时间
        $data = time();
    }
    // 把当前内容页URL替换上面的field
    elseif ($fieldname == 'url') 
    {
        $data = $page['url'];
    }
    elseif ($fieldname == 'article_content') 
    {
              $text =null;
       foreach ($data as $value)
       {
           $text.=$value;
       }
       $data = $text;
    }
    echo "<br/>";
    return $data;
};

$spider->start();


