<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

// 1. 使用账户登录 it橘子
// 2. 使用chrome浏览器，把cookie复制出来，如下
// 3. 在 on_start 中设置cookie


// 4. 配置参数 开始抓
$configs = array(
    'name' => 'IT桔子',
    'log_show' => true,
    'tasknum' => 1,
    //'save_running_state' => true,
    'domains' => array(
        'www.itjuzi.com',
        'https://www.itjuzi.com'
    ),
    'scan_urls' => array(
        'https://www.itjuzi.com/investfirm?user_id=305129'
    ),
    'list_url_regexes' => array(
        "https://www.itjuzi.com/investfirm\?user_id=305129&page=\d+"
    ),
    'content_url_regexes' => array(
        //内容页https://www.itjuzi.com/investfirm/6552
        "https://www.itjuzi.com/investfirm/\d+\$",
    ),
    'max_try' => 5,
    //'export' => array(
        //'type' => 'db',
        //'table' => 'iijuzi',
    //),
    'fields' => array(
        array(
            'name' => "title",
            'selector' => "//span[contains(@class,'title')]",
            'required' => true,
        ),
        array(
            'name' => "content",
            'selector' => "//div[contains(@class,'des')]",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);
$spider->on_start = function ($spider) 
{
    // 模拟登陆
    $cookies = 'gr_user_id=a224b239-3f39-4514-9eae-8996ac37ae02; acw_tc=AQAAAIhFpS+qngMAg1aA3qhElUrfu83a; session=dad804d9a73187d3abcb2c1ba5569578f3bfe697; _ga=GA1.2.614489783.1479262399; _gat=1; gr_session_id_eee5a46c52000d401f969f4535bdaa78=156149b6-d911-4e68-93e7-5a23eecade16; Hm_lvt_1c587ad486cdb6b962e94fc2002edf89=1479262399,1479434528; Hm_lpvt_1c587ad486cdb6b962e94fc2002edf89=1479439873; identity=aspyong%40163.com; remember_code=0tCCVc0vUZ';
    requests::add_cookies($cookies, 'www.itjuzi.com');

    // 生成列表页URL入Redis
    for ($i = 0; $i <= 652; $i++) 
    {
        $url = "https://www.itjuzi.com/investfirm?user_id=305129&page={$i}";
        $spider->add_url($url);
    }
};
$spider->on_extract_field = function($fieldname, $data, $page)
{
    log::debug($fieldname.'=>'.var_export($data,1));
    return $data;
};
$spider->start();


