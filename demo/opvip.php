<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//var_dump(cls_redis::get("lock"));
//var_dump(cls_redis::del("lock"));
//var_dump(cls_redis::setnx("lock", "name"));
//var_dump(cls_redis::setnx("lock", "name"));
//exit;
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
    'collect_fails' => 5,
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

//$spider->start();

$w = new worker();
// 直接使用上面配置的任务数作为worker进程数
$w->count = $configs['tasknum'];
$w->on_worker_start = function($worker) use ($spider) {

    $taskmaster = false;
    // 把第一个worker进程当做主任务
    if ($worker->worker_id == 1) 
    {
        $taskmaster = true;
    }
    $spider->start($taskmaster, $worker->worker_id);

};

$w->run();

