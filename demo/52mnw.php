<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '52mnw美女图',
    //'tasknum' => 8,
    'log_show' => true,
    'save_running_state' => false,
    'domains' => array(
        'm.52mnw.cn'
    ),
    'scan_urls' => array(
        "http://m.52mnw.cn/ikaimi/morepic.php?classid=6,7,8,10,11,15&line=10&order=newstime&page=1",
    ),
    'list_url_regexes' => array(
    ),
    'content_url_regexes' => array(
        "http://m.52mnw.cn/photo/\d+.html",
    ),
    'export' => array(
        'type' => 'db', 
        'table' => 'meinv_content',
    ),
    'db_config' => array(
        'host'  => '127.0.0.1',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'root',
        'name'  => 'qiushibaike',
    ),
    'fields' => array(
        // 标题
        array(
            'name' => "name",
            'selector' => "//title",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "category",
            'selector' => "//div[contains(@class,'header')]//span",
            'required' => true,
        ),
        // 发布时间
        array(
            'name' => "addtime",
            'selector' => "//div[contains(@class,'content-msg')]",
            //'required' => true,
        ),
        // 图片
        array(
            'name' => "image",
            'selector' => "//li[contains(@class,'swiper-slide')]//img/@lazysrc",
            'required' => true,
            'repeated' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function($phpspider)
{
    for ($i = 2; $i <= 932; $i++) 
    {
        $url = "http://m.52mnw.cn/ikaimi/morepic.php?classid=6,7,8,10,11,15&line=10&order=newstime&page={$i}";
        $phpspider->add_scan_url($url);
    }
};

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'name') 
    {
        $data = str_replace("-我爱美女网手机版", "", $data);
    }
    elseif ($fieldname == 'addtime') 
    {
        $data = time();
    }
    return $data;
};

$categorys = array(
    '性感美女' => array(
        'dir' => 'xingganmeinv',
        'name' => '性感美女',
    ),
    '女星写真' => array(
        'dir' => 'mingxingmeinv',
        'name' => '明星美女',
    ),
    '高清美女' => array(
        'dir' => 'qingchunmeinv',
        'name' => '清纯美女',
    ),
    '模特美女' => array(
        'dir' => 'meinvmote',
        'name' => '美女模特',
    ),
    '丝袜美腿' => array(
        'dir' => 'siwameitui',
        'name' => '丝袜美女',
    ),
    '唯美写真' => array(
        'dir' => 'weimeixiezhen',
        'name' => '唯美写真',
    ),
);
$spider->on_extract_page = function($page, $data) use ($categorys)
{
    if (!isset($categorys[$data['category']])) 
    {
        return false;
    }
    $data['dir'] = $categorys[$data['category']]['dir'];
    $data['category'] = $categorys[$data['category']]['name'];
    $data['content'] = implode(",", $data['image']);
    $data['image'] = $data['image'][0];

    //$data['dir'] = $category[$data['category']];
    //$data['content'] = $data['image'].','.$data['content'];
    //$sql = "Select Count(*) As `count` From `meinv_content` Where `name`='{$data['name']}'";
    //$row = db::get_one($sql);
    //if (!$row['count']) 
    //{
        //db::insert("meinv_content", $data);
    //}
    return $data;
};

$spider->start();
