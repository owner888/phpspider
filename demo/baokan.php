<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '报刊',
    'tasknum' => 5,
    'domains' => array(
        'navi.cnki.net'
    ),
    'scan_urls' => array(
        "http://navi.cnki.net/knavi/journal/Detailq/CJFQ/ZGTS?Year=&Issue=&Entry=#ferh",
    ),
    'list_url_regexes' => array(
        "http://navi.cnki.net/KNavi/Journal/CatalogPaging\?page=\d+",
    ),
    'content_url_regexes' => array(
        "http://navi.cnki.net/KNavi/Journal/SkipHandles\?skipurl=http%3a%2f%2fwww.cnki.net%2fkcms%2fdetail%2fdetail.aspx%3fDbCode%3dCJFQ%26dbname%3dCAPJLAST%26filename%3dZGTS\d+%26uid%3d",
    ),
    'export' => array(
        'type' => 'db', 
        'table' => 'baokan_content',
    ),
    'fields' => array(
        array(
            'name' => "author",
            'selector' => "//div[contains(@class,'author')]",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function($phpspider) 
{
    requests::add_header('Referer','http://navi.cnki.net/knavi/journal/Detailq/CJFQ/ZGTS?Year=&Issue=&Entry=');
};

$spider->on_scan_page = function($page, $content, $phpspider) 
{
    for ($i = 1; $i <= 10; $i++) 
    {
        // 全国热点城市
        $url = "http://navi.cnki.net/KNavi/Journal/CatalogPaging?page={$i}";
        $options = array(
            'url_type' => $url,
            'method' => 'post',
            'params' => array(
                'year' => '', 
                'issue' => '',
                'productId' => 'CJFQ',
                'baseId' => 'ZGTS',
                'type' => 'nf',
                'page' => $i
            )
        );
        $phpspider->add_url($url, $options);
    }
};

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'author') 
    {
        $data = strip_tags($data);
        $data = str_replace(array('&#13;'), '', $data);
    }
    return $data;
};

$spider->start();
