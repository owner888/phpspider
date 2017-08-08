<?php
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\db;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

//$url = "https://istore.oppomobile.com/storeapp/home?size=10&start=0";
//$data = requests::get($url);
//$info = requests::$info;
//print_r($info);
//exit;

$configs = array(
    'name' => '13384美女图',
    'tasknum' => 1,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'www.13384.com'
    ),
    'scan_urls' => array(
        "http://www.13384.com/qingchunmeinv/",
        "http://www.13384.com/xingganmeinv/",
        "http://www.13384.com/mingxingmeinv/",
        "http://www.13384.com/siwameitui/",
        "http://www.13384.com/meinvmote/",
        "http://www.13384.com/weimeixiezhen/",
    ),
    'list_url_regexes' => array(
        "http://www.13384.com/qingchunmeinv/index_\d+.html",
        "http://www.13384.com/xingganmeinv/index_\d+.html",
        "http://www.13384.com/mingxingmeinv/index_\d+.html",
        "http://www.13384.com/siwameitui/index_\d+.html",
        "http://www.13384.com/meinvmote/index_\d+.html",
        "http://www.13384.com/weimeixiezhen/index_\d+.html",
    ),
    'content_url_regexes' => array(
        "http://www.13384.com/qingchunmeinv/\d+.html",
        "http://www.13384.com/xingganmeinv/\d+.html",
        "http://www.13384.com/mingxingmeinv/\d+.html",
        "http://www.13384.com/siwameitui/\d+.html",
        "http://www.13384.com/meinvmote/\d+.html",
        "http://www.13384.com/weimeixiezhen/\d+.html",
    ),
    //'export' => array(
        //'type' => 'db', 
        //'table' => 'meinv_content',
    //),
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
            'selector' => "//div[@id='Article']//h1",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "category",
            'selector' => "//div[contains(@class,'crumbs')]//span//a",
            'required' => true,
        ),
        // 发布时间
        array(
            'name' => "addtime",
            'selector' => "//p[contains(@class,'sub-info')]//span",
            'required' => true,
        ),
        // API URL
        array(
            'name' => "url",
            'selector' => "//p[contains(@class,'sub-info')]//span",
            'required' => true,
        ),
        // 图片
        array(
            'name' => "image",
            'selector' => "//*[@id='big-pic']//a//img",
            'required' => true,
        ),
        // 内容
        array(
            'name' => "content",
            'selector' => "//div[@id='pages']//a//@href",
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
                    'selector' => "//*[@id='big-pic']//a//img"
                ),
            ),
        ),
    ),
);

$spider = new phpspider($configs);


$spider->on_start = function($phpspider) 
{
    $db_config = $phpspider->get_config("db_config");
    //print_r($db_config);
    //exit;
    // 数据库连接
    db::set_connect('default', $db_config);
    db::init_mysql();
};

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'url') 
    {
        $data = $page['request']['url'];
    }
    elseif ($fieldname == 'name') 
    {
        $data = trim(preg_replace("#\(.*?\)#", "", $data));
    }
    if ($fieldname == 'addtime') 
    {
        $data = strtotime(substr($data, 0, 19));
    }
    elseif ($fieldname == 'content') 
    {
        $contents = $data;
        $array = array();
        foreach ($contents as $content) 
        {
            $url = $content['page_content'];
            // md5($url) 过滤重复的URL
            $array[md5($url)] = $url;

            //// 以纳秒为单位生成随机数
            //$filename = uniqid().".jpg";
            //// 在data目录下生成图片
            //$filepath = PATH_ROOT."/images/{$filename}";
            //// 用系统自带的下载器wget下载
            //exec("wget -q {$url} -O {$filepath}");
            //$array[] = $filename;
        }
        $data = implode(",", $array);
    }
    return $data;
};

$category = array(
    '丝袜美女' => 'siwameitui',
    '唯美写真' => 'weimeixiezhen',
    '性感美女' => 'xingganmeinv',
    '明星美女' => 'mingxingmeinv',
    '清纯美女' => 'qingchunmeinv',
    '美女模特' => 'meinvmote',
);

$spider->on_extract_page = function($page, $data) use ($category)
{
    if (!isset($category[$data['category']])) 
    {
        return false;
    }
    
    $data['dir'] = $category[$data['category']];
    $data['content'] = $data['image'].','.$data['content'];
    $data['image'] = str_replace("ocnt0imhl.bkt.clouddn.com", "file.13384.com", $data['image']);
    $data['image'] = $data['image']."?imageView2/1/w/320/h/420";
    $data['content'] = str_replace("ocnt0imhl.bkt.clouddn.com", "file.13384.com", $data['content']);
    $sql = "Select Count(*) As `count` From `meinv_content` Where `name`='{$data['name']}'";
    $row = db::get_one($sql);
    if (!$row['count']) 
    {
        db::insert("meinv_content", $data);
    }
    return $data;
};

$spider->start();

