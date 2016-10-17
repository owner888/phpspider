<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '罗辑思维',
    'tasknum' => 16,
    'domains' => array(
        'luofans.com',
        'www.luofans.com'
    ),
    'scan_urls' => array(
        'http://www.luofans.com/audios'
    ),
    'list_url_regexes' => array(
        "http://www.luofans.com/audios\?offset=\d+&amp;max=10&amp;sort=publishAt&amp;order=desc"
    ),
    'content_url_regexes' => array(
        "http://www.luofans.com/audios/\d+",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'luojisiwei_content',
    ),
    'fields' => array(
        array(
            'name' => "content",
            'selector' => "//div[contains(@class,'article-content')]",
            'required' => true,
        ),
        array(
            'name' => "date",
            'selector' => "//li[contains(@class,'active')]",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'content') 
    {
        $data = strip_tags($data);
    }
    return $data;
};

$spider->start();


