<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '糗事百科',
    //'log_show' => true,
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
            'name' => "article_headimg",
            'selector' => "//div[contains(@class,'author')]//a[1]",
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

    //$pathinfo = pathinfo($url);
    //$fileext = $pathinfo['extension'];
    //if (strtolower($fileext) == 'jpeg') 
    //{
        //$fileext = 'jpg';
    //}
    //// 以纳秒为单位生成随机数
    //$filename = uniqid().".".$fileext;
    //// 在data目录下生成图片
    //$filepath = PATH_ROOT."/images/{$filename}";
    //// 用系统自带的下载器wget下载
    //exec("wget -q {$url} -O {$filepath}");

    //// 替换成真是图片url
    //$img = str_replace($url, $filename, $img);
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
    return $data;
};

$spider->start();


