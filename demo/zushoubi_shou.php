<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//$url = "http://guangzhou.anjuke.com/prop/view/A658123987?from=structured_dict-saleMetro&spread=commsearch_p&position=6&now_time=1479176839";
//$html = requests::get($url);
//file_put_contents(PATH_DATA."/test1.html", $html); exit;
//$html = file_get_contents(PATH_DATA."/test1.html");
//$data = selector::select($html, "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'first-col')]/dl/dd/a[1]"); // 小区
//$data = selector::select($html, "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'first-col')]/dl[3]/dd");   // 年代
//$data = selector::select($html, "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'second-col')]/dl[2]/dd");  // 面积
//$data = selector::select($html, "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'third-col')]/dl[2]/dd");   // 单价
//print_r($data);
//exit;


$configs = array(
    'name' => '租售比-出售',
    'tasknum' => 8,
    'multiserver' => true,
    'serverid' => 1,
    'log_show' => true,
    'proxy' => 'http://H569I1428O9U0Z5P:67545392FA7A66E1@proxy.abuyun.com:9010',
    //'proxy' => 'http://H6F9I7K565Y49A5P:90C146A1FE287149@proxy.abuyun.com:9010',
    'domains' => array(
        'guangzhou.anjuke.com'
    ),
    'scan_urls' => array(
        'http://guangzhou.anjuke.com/sale/'
    ),
    'list_url_regexes' => array(
        "http://guangzhou.anjuke.com/sale/p\d+/.*?"
    ),
    'content_url_regexes' => array(
        "http://guangzhou.anjuke.com/prop/view/A.*?",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'zushoubi_shou_content',
    ),
    'fields' => array(
        array(
            'name' => "community",
            'selector' => "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'first-col')]/dl/dd/a[1]",
            'required' => true,
        ),
        array(
            'name' => "building_year",
            'selector' => "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'first-col')]/dl[3]/dd",
            'required' => true,
        ),
        array(
            'name' => "area",
            'selector' => "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'second-col')]/dl[2]/dd",
            'required' => true,
        ),
        array(
            'name' => "price",
            'selector' => "//div[contains(@class,'houseInfo-detail')]/div[contains(@class,'third-col')]/dl[2]/dd",
            'required' => true,
        ),
        // 下面三个随便设置，on_extract_field回调里面会替换
        array(
            'name' => "url",
            'selector' => "//div[contains(@class,'shijieshangzuihaodeyuyan_php')]",   
        ),
        array(
            'name' => "depth",
            'selector' => "//div[contains(@class,'shijieshangzuihaodeyuyan_php')]",   
        ),
        array(
            'name' => "taskid",
            'selector' => "//div[contains(@class,'shijieshangzuihaodeyuyan_php')]",   
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'building_year') 
    {
        $data = intval($data);
    }
    elseif ($fieldname == 'area') 
    {
        $data = intval($data);
    }
    elseif ($fieldname == 'price') 
    {
        $data = intval($data);
    }
    // 把当前内容页URL替换上面的field
    elseif ($fieldname == 'url') 
    {
        $data = $page['url'];
    }
    elseif ($fieldname == 'depth') 
    {
        $data = $page['request']['depth'];
    }
    elseif ($fieldname == 'taskid') 
    {
        $data = $page['request']['taskid'];
    }
    return $data;
};

$spider->start();
