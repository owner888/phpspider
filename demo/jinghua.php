<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//$url = "http://news.jinghua.cn/20161205/f256601.shtml";
//requests::$output_encoding = "utf8";
//$html = requests::get($url);
//$data = selector::select($html, "//div[contains(@class, 'img_list')]/ul/li/a/@href");
//print_r($data);
//exit;
//echo $html;
//exit;
$configs = array(
    'name' => '京华网',
    'tasknum' => 1,
    'log_show' => true,
    'domains' => array(
        'news.jinghua.cn'
    ),
    'scan_urls' => array(
        "http://news.jinghua.cn/20161205/f256601.shtml",
    ),
    'list_url_regexes' => array(
    ),
    'content_url_regexes' => array(
        "http://news.jinghua.cn/20161205/f256601.shtml",
    ),
    'fields' => array(
        // 标题
        array(
            'name' => "name",
            'selector' => "//div[contains(@class, 'w670')]/h1",
            'required' => true,
        ),
        // 内容
        array(
            'name' => "content",
            'selector' => "//div[contains(@class, 'img_list')]/ul/li/a/@href",
            'repeated' => true,
            'required' => true,
            'children' => array(
                array(
                    // 抽取出其他分页的url待用
                    'name' => 'content_page_url',
                    'selector' => "//text()"
                ),
                array(
                    // 抽取其他分页的内容
                    'name' => 'page_content',
                    // 发送 attached_url 请求获取其他的分页数据
                    // attached_url 使用了上面抓取的 content_page_url
                    'source_type' => 'attached_url',
                    'attached_url' => 'content_page_url',
                    'selector' => "//div[contains(@class, 'content')]"
                ),
            ),
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'content') 
    {
        $contents = $data;
        $array = array();
        foreach ($contents as $content) 
        {
            $array[] = $content['page_content'];
        }
        $data = implode("\n", $array);
    }
    return $data;
};

$spider->start();

