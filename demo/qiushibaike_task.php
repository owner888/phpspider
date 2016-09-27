<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '糗事百科',
    'tasknum' => 5,
    'continue' => true,
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
    'collect_fails' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'content',
    ),
    'fields' => array(
        array(
            'name' => "article_title",
            'selector' => "//*[@id='single-next-link']//div[contains(@class,'content')]/text()[1]",
            'required' => true,
        ),
        array(
            'name' => "article_author",
            'selector' => "//div[contains(@class,'author')]//h2",
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => "//*[@id='single-next-link']//div[contains(@class,'content')]",
            'required' => true,
        ),
        array(
            'name' => "article_publish_time",
            'selector' => "//div[contains(@class,'author')]//h2",
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
    elseif ($fieldname == 'article_publish_time') 
    {
        // 用当前采集时间戳作为发布时间
        $data = time();
    }
    return $data;
};

$w = new worker();
// 直接使用上面配置的任务数作为worker进程数
$w->count = $configs['tasknum'];
$w->on_worker_start = function($worker) use ($spider) {

    $master_task = false;
    // 把第一个worker进程当做主任务
    if ($worker->worker_id == 1) 
    {
        $master_task = true;
    }
    $spider->start($master_task, $worker->worker_id);

};

$w->run();

