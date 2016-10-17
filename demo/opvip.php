<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => 'op_news',
    'tasknum' => 8,
    'save_running_state' => true,
    'domains' => array(
        'www.opvip.com',
    ),
    'scan_urls' => array(
        'http://www.opvip.com/news-latest.html',
    ),
    'list_url_regexes' => array(
        "http://www.opvip.com/news-latest.html?page=\d+"
    ),
    'content_url_regexes' => array(
        "http://www.opvip.com/wz-\d+.html",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'meilele',
    ),
    'fields' => array(
        array(
            'name' => "title",
            'selector' => "//div[contains(@class,'caption')]//h1",
            'required' => true,
        ),
        array(
            'name' => "author",
            'selector' => "//div[contains(@class,'date')]//span[2]",
            'required' => true,
        ),
        array(
            'name' => "description",
            'selector' => "//div[contains(@class,'desc_box')]//div",
            'required' => true,
        ),
        array(
            'name' => "publish_time",
            'selector' => "//div[contains(@class,'desc_box')]//div",
            'required' => true,
        ),
    ),
);
$spider = new phpspider($configs);

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
    elseif ($fieldname == 'publish_time') 
    {
        // 用当前采集时间戳作为发布时间
        $data = time();
    }
    return $data;
};

$spider->start();
