<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '天行云',
    'log_show' => true,
    'tasknum' => 1,
    //'save_running_state' => true,
    'max_try' => 5,
    'domains' => array(
        'www.xyb2b.com'
    ),
    'scan_urls' => array(
        'http://www.xyb2b.com/Products/Index'
    ),
    'list_url_regexes' => array(
        "http://www.xyb2b.com/Products/Index/start/\d+",
    ),
    'content_url_regexes' => array(
        "http://www.xyb2b.com/Home/Products/detail/gid/\d+",
    ),
    //'export' => array(
        //'type' => 'db', 
        //'conf' => array(
            //'host'  => 'localhost',
            //'port'  => 3306,
            //'user'  => 'root',
            //'pass'  => '',
            //'name'  => 'demo',
        //),
        //'table' => 'txy',
    //),
    'fields' => array(
        array(
            'name' => "txy_price",
            'selector' => '//*[@id="goodsForm"]/div[6]/div[1]/div[2]/div[2]/div/div/ul/li/h2/text()',
            'required' => true,
        ),
        array(
            'name' => "article_content",
            'selector' => "//h1[@id='detailName']",
            'required' => true,
        ),
        array(
            'name' => "article_num",
            'selector' => '//*[@id="showNum"]',
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
$spider->on_start = function($phpspider) 
{
    // 登录请求url
    $login_url = "http://www.xyb2b.com/Home/User/doLogin";
    // 提交的参数
    $options = array(
        "m_name" => "public",
        "m_password" => "123456",
        "savelogin" => "0",
        "requsetUrl" => "http://www.xyb2b.com/Products/Index/start/1",
    );
    // 发送登录请求
    requests::post($login_url, $options);
    //$phpspider->request_url($login_url, $options);
    // 登录成功后本框架会把Cookie保存到www.waduanzi.com域名下，我们可以看看是否是已经收集到Cookie了
    $cookies = requests::get_cookies("www.xyb2b.com");
    // print_r($cookies);exit;  // 可以看到已经输出Cookie数组结构

    // 框架自动收集Cookie，访问这个域名下的URL会自动带上
    // 接下来我们来访问一个需要登录后才能看到的页面
    // $url = "http://www.xyb2b.com/Ucenter/Cart/pageList";
    // $html = $phpspider->request_url($url);
    // echo $html;     // 可以看到登录后的页面，非常棒👍
};

$spider->on_scan_page = function($page, $content, $phpspider) 
{
    preg_match('#<a href="(.*)" class="change">尾页</a>#', $content, $out);
    preg_match('(\d+)', $out[1], $out_2);
    for ($i = 0; $i <=$out_2[0]; $i++) //
    {
        $url = "http://www.xyb2b.com/Products/Index/start/{$i}";
        $options = array(
            'url_type' => $url,
            'method' => 'get',
            //    'Referer'=>'http://www.xyb2b.com/Home/Products/Index'
        );
        $phpspider->add_url($url, $options);
    };
};

$spider->on_download_page = function($page, $phpspider) 
{
    preg_match('/gid\/(\d+)$/', $page['request']['url'], $out);
    if(!empty($out[1]))
    {
        // 从$content里面找出ajax地址，获取内容
        $sukList = $phpspider->request_url("http://www.xyb2b.com/Home/Products/getDetailSkus?item_type=0&gid=".$out[1]);
        // 拼接到当前内容页内容后面去
        $page['raw'] = $page['raw'].$sukList;
    }
    return $page;
};

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
    echo "<br/>";
    return $data;
};

$spider->start();


