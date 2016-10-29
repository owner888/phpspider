<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '糗事百科CSS选择器示例',
    'log_show' => true,
    'tasknum' => 1,
    //'save_running_state' => true,
    'domains' => array(
        'qiushibaike.com',
        'www.qiushibaike.com'
    ),
    'scan_urls' => array(
        'http://www.qiushibaike.com/'
    ),
    'list_url_regexes' => array(
        "http://www.qiushibaike.com/8hr/page/\d+\?s=\d+"
    ),
    'content_url_regexes' => array(
        "http://www.qiushibaike.com/article/\d+",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'content',
    ),
    'fields' => array(
        array(
            'name' => "article_title",
            'selector' => "#single-next-link > div.content",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "article_author",
            'selector' => "div.author > a > h2",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "article_headimg",
            'selector' => "//div.author > a:eq(0)",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => "#single-next-link > div.content",
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "article_publish_time",
            'selector' => "div.author > a > h2",  // 这里随便设置，on_extract_field回调里面会替换
            'selector_type' => 'css',
            'required' => true,
        ),
        array(
            'name' => "url",
            'selector' => "div.author > a > h2",  // 这里随便设置，on_extract_field回调里面会替换
            'selector_type' => 'css',
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'article_title') 
    {
        $data = trim($data);
        if (strlen($data) > 10) 
        {
            // 下面方法截取中文会有乱码
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
    return $data;
};

$spider->start();


