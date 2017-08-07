<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//$url = "http://gz.zu.anjuke.com/fangyuan/1027188319";
//$html = requests::get($url);
//$html = file_get_contents(PATH_DATA."/test.html");
//$data = selector::select($html, "//div[contains(@id,'commmap')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[3]/dd");
//print_r($data);
//exit;


$configs = array(
    'name' => '租售比',
    'tasknum' => 1,
    //'multiserver' => true,
    //'serverid' => 4,
    'log_show' => true,
    'proxy' => 'http://H569I1428O9U0Z5P:67545392FA7A66E1@proxy.abuyun.com:9010',
    //'proxy' => 'http://H6F9I7K565Y49A5P:90C146A1FE287149@proxy.abuyun.com:9010',
    'domains' => array(
        'gz.zu.anjuke.com'
    ),
    'scan_urls' => array(
        'http://gz.zu.anjuke.com/'
    ),
    'list_url_regexes' => array(
        "http://gz.zu.anjuke.com/fangyuan/p\d+/"
    ),
    'content_url_regexes' => array(
        "http://gz.zu.anjuke.com/fangyuan/\d+",
    ),
    'max_try' => 5,
    'export' => array(
        'type' => 'db', 
        'table' => 'zushoubi_content',
    ),
    'fields' => array(
        array(
            'name' => "rent",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[1]/dd/strong/span",
            'required' => true,
        ),
        array(
            'name' => "rent_pay",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[2]/dd",
            'required' => true,
        ),
        array(
            'name' => "room",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[3]/dd",
            'required' => true,
        ),
        array(
            'name' => "lease",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[4]/dd",
            'required' => true,
        ),
        array(
            'name' => "community",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[5]/dd/a",
            'required' => true,
        ),
        array(
            'name' => "address",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'litem')]/dl[6]/dd",
            'required' => true,
        ),
        array(
            'name' => "decorate",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[2]/dd",
            'required' => true,
        ),
        array(
            'name' => "area",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[3]/dd",
            'required' => true,
        ),
        array(
            'name' => "toward",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[4]/dd",
            'required' => true,
        ),
        array(
            'name' => "floor",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[5]/dd",
            'required' => true,
        ),
        array(
            'name' => "max_floor",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[5]/dd",
            'required' => true,
        ),
        array(
            'name' => "type",
            'selector' => "//div[contains(@class,'pinfo')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[6]/dd",
            'required' => true,
        ),
        array(
            'name' => "building_year",
            'selector' => "//div[contains(@id,'commmap')]/div[contains(@class,'box')]/div[contains(@class,'phraseobox')]/div[contains(@class,'ritem')]/dl[3]/dd",
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
    if ($fieldname == 'rent_pay') 
    {
        $data = trim(str_replace("我要贷款", "", strip_tags($data)));
    }
    elseif ($fieldname == 'floor') 
    {
        $arr = explode("/", $data);
        $data = empty($arr[0]) ? 0 : $arr[0];
    }
    elseif ($fieldname == 'max_floor') 
    {
        $arr = explode("/", $data);
        $data = empty($arr[1]) ? 0 : $arr[1];
    }
    elseif ($fieldname == 'address') 
    {
        $data = str_replace("  "," ", trim(strip_tags($data)));
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
