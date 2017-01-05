<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */
define("TAG", "小说");

$configs = array(
    'name' => '豆瓣读书',
    'log_show' => true,
    'tasknum' => 1,
    //'save_running_state' => true,
    'domains' => array(
        'book.douban.com',
    ),
    'scan_urls' => array(
        'https://book.douban.com/tag/'.TAG.'/'
    ),
    'list_url_regexes' => array(
        "https://book.douban.com/tag/".TAG."?start=\d+\?s=\d+&type=T"
    ),
    'content_url_regexes' => array(
        "https://book.douban.com/subject/\d+",
    ),
    'max_try' => 5,
    //'export' => array(
        //'type' => 'db', 
        //'table' => 'book_content',
    //),
    'fields' => array(
        array(
            'name' => "book_tag",
            'selector' => "//strong[contains(@class,'rating_num')]",
            'required' => true,
        ),
        array(
            'name' => "book_name",
            'selector' => "//span[contains(@property,'v:itemreviewed')]",
            'required' => true,
        ),
        array(
            'name' => "book_author",
            'selector' => "//div[contains(@id,'info')]//span//a",
            'required' => true,
        ),
        array(
            'name' => "book_publishing_house",
            'selector' => "@出版社:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_original_name",
            'selector' => "@原作名:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_subtitle",
            'selector' => "@副标题:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_translator",
            'selector' => "@译者:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_publishing_year",
            'selector' => "@出版年:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_page_num",
            'selector' => "@页数:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_price",
            'selector' => "@定价:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_binding",
            'selector' => "@装帧:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_series",
            'selector' => "@丛书:</span>&nbsp;(.*)<br>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_isbn",
            'selector' => "@ISBN:</span>(.*)<br/>@",
            'selector_type' => "regex",
        ),
        array(
            'name' => "book_rating",
            'selector' => "//strong[contains(@class,'rating_num')]",
            'required' => true,
        ),
        array(
            'name' => "book_rating_people",
            'selector' => "//span[contains(@property,'v:votes')]",
            'required' => true,
        ),
        array(
            'name' => "book_star5",
            'selector' => '@[^xyz]*5星[^xyz]*</span>[^xyz]*<div class="power" style="width:\d+px"></div>[^xyz]*<span class="rating_per">(.*)</span>[^xyz]*<br>@',
            'selector_type' => "regex",
            'required' => true,
        ),
        array(
            'name' => "book_star4",
            'selector' => '@[^xyz]*4星[^xyz]*</span>[^xyz]*<div class="power" style="width:\d+px"></div>[^xyz]*<span class="rating_per">(.*)</span>[^xyz]*<br>@',
            'selector_type' => "regex",
            'required' => true,
        ),
        array(
            'name' => "book_star3",
            'selector' => '@[^xyz]*3星[^xyz]*</span>[^xyz]*<div class="power" style="width:\d+px"></div>[^xyz]*<span class="rating_per">(.*)</span>[^xyz]*<br>@',
            'selector_type' => "regex",
            'required' => true,
        ),
        array(
            'name' => "book_star2",
            'selector' => '@[^xyz]*2星[^xyz]*</span>[^xyz]*<div class="power" style="width:\d+px"></div>[^xyz]*<span class="rating_per">(.*)</span>[^xyz]*<br>@',
            'selector_type' => "regex",
            'required' => true,
        ),
        array(
            'name' => "book_star1",
            'selector' => '@[^xyz]*1星[^xyz]*</span>[^xyz]*<div class="power" style="width:\d+px"></div>[^xyz]*<span class="rating_per">(.*)</span>[^xyz]*<br>@',
            'selector_type' => "regex",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);
$spider->on_extract_field = function($fieldname, $data, $page) {
    if ($fieldname == 'book_tag') {
        $data = TAG;
    }
    if ($fieldname == 'book_author') {
        $data = str_replace(')', ']', str_replace('(', '[', str_replace('）', ']', str_replace('（', '[', $data))));;
        $rsb=strpos($data,']');
        $space=strpos($data,' ');
        if(!$space&&($rsb+1)!==$space){
            $data=str_replace(']', '] ',$data);
        }
    }
    if ($fieldname == 'book_series') {
        $la=strpos($data,'>');
        $ra=strpos($data,'</');
        $data=substr($data, $la+1, $ra-$la-1);
    }
    if ($fieldname == 'book_star5') {
        $percent=strpos($data,'%');
        $data=substr($data, 0, $percent);
    }
    if ($fieldname == 'book_star4') {
        $percent=strpos($data,'%');
        $data=substr($data, 0, $percent);
    }
    if ($fieldname == 'book_star3') {
        $percent=strpos($data,'%');
        $data=substr($data, 0, $percent);
    }
    if ($fieldname == 'book_star2') {
        $percent=strpos($data,'%');
        $data=substr($data, 0, $percent);
    }
    if ($fieldname == 'book_star1') {
        $percent=strpos($data,'%');
        $data=substr($data, 0, $percent);
    }
    return $data;
};
$spider->start();


