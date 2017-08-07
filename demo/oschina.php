<?php
/**
 * Created by PhpStorm.
 * User: xiangli
 * Date: 2016/12/7
 * Time: 19:30
 */

ini_set("memory_limit", "-1");
set_time_limit(0);
require dirname(__FILE__) . '/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

/*
$html	=	requests::get("https://my.oschina.net/u/1186749/");
$data	=	selector::select($html,	"//div[contains(@class,'list-item')]//a/@href");//获取链接
$content_url = selector::select($html, "//div[contains(@class,'list-item')]//a[contains(@class,'blog-title')]/@href");
// 在列表页中通过XPath提取到发布日期
$add_date = selector::select($html, "//div[contains(@class,'time')]/text()");
//var_dump($data);
//var_dump($content_url);
foreach($add_date as $val){
    $val = substr(trim($val),0,10);
    echo $val;
}
exit;
 */
/*
$string='<pre><code class="language-php">$exchange-&gt;setName(\'logs\');
$exchange-&gt;setType(AMQP_EX_TYPE_FANOUT);
$exchange-&gt;declare();
</code></pre>中阿斯蒂芬中阿斯蒂芬中阿斯蒂芬中阿斯蒂芬中阿斯蒂芬中阿斯蒂芬中阿斯蒂芬阿斯顿发生
<pre><code class="language-golang">$exchange-&gt;setName(\'logs\');
$exchange-&gt;setType(AMQP_EX_TYPE_FANOUT);
$exchange-&gt;declare();
</code></pre>
';
$regix="/<pre><code class=\"language-(.*?)\">([^<]*)<\/code><\/pre>/i";
$string = preg_replace($regix,'```$1$2```', $string);
echo $string;
exit;
preg_match_all($regix,$string,$matchs);
if($matchs){
    foreach($matchs[0] as $key=>$va){
        $string=str_replace($va,'```'.$matchs[1][$key].$matchs[2][$key].'```',$string);
    }
}
echo $string;
exit;
 */

$configs = array(
    'name' => 'OSCHINA BLOG',
    'tasknum' => 1,
    'log_show' => true,
    'domains' => array(
        'my.oschina.net',
        'oschina.net'
    ),
    'scan_urls' => array(
        //扫描的起始地址
        'https://my.oschina.net/u/1186749/'
    ),
    'list_url_regexes' => array(
        "https://my.oschina.net/u/1186749/?sort=time&p=\d+&",
    ),
    'content_url_regexes' => array(
        //以数字结尾，因此有个\$，不然会把什么邮箱的也采集进来造成超时
        //"https://my.oschina.net/u/1186749/blog/\d+\$",
    ),
    //'export' => array(
        //'type' => 'db',
        //'conf' => array(
            //'host' => 'localhost',
            //'port' => 3306,
            //'user' => 'root',
            //'pass' => 'password',
            //'name' => 'my_blog',
        //),
        //'table' => 'articles',
    //),
    'max_try' => 5,
    'fields' => array(
        array(
            'name' => "content",
            'selector' => "//div[contains(@class,'BlogContent')]",
            'required' => true,
        ),
        array(
            'name' => "html_content",
            'selector' => "//div[contains(@class,'BlogContent')]",
            'required' => true,
        ),
        array(
            'name' => "title",
            'selector_type' => 'regex',
            'selector' => '#<div\sclass="heading">([^<]+)</div>#i',
            'required' => true,
        ),
        array(
            'name' => "user_id",
            'selector_type' => 'regex',
            'selector' => '#<div\sclass="heading">([^<]+)</div>#i',
            'required' => false,
        ),
        array(
            'name' => "created_at",
            'selector' => "//div[contains(@class,'add-date')]",
            'required' => false,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_list_page = function($page, $content, $phpspider)
{
    echo $content;
    // 在列表页中通过XPath提取到内容页URL
    $content_url = selector::select($content, "//div[contains(@class,'list-item')]//a[contains(@class,'blog-title')]/@href");
    // 在列表页中通过XPath提取到发布日期
    $add_date = selector::select($content, "//div[contains(@class,'time')]/text()");
    // 拼出包含包含发布日期的HTML
    $add_date = '<div class="add-date">' . trim($add_date) . '</div>';

    $options = array(
        'method' => 'get',
        'context_data' => $add_date,
    );

    $phpspider->add_url($content_url, $options);
    return true;
};


$spider->on_extract_field = function ($fieldname, $data, $page) {
    if ($fieldname == 'html_content') {
        $regix = "/<pre><code class=\"language-(.*?) hljs\">([^<]*)<\/code><\/pre>/i";
        $data = preg_replace($regix, '```$1$2```', $data);
    } elseif ($fieldname == 'content') {
        $data = strip_tags($data);
    } elseif ($fieldname == 'title') {
        $data = strip_tags($data);
    } elseif ($fieldname == 'user_id') {
        $data = 1;
    } elseif ($fieldname == 'created_at') {
        if(empty($data)){
            $data = date('Y-m-d');
        }
        else{
            $data = substr(trim($data),0,10);
        }
    }
    return $data;
};

$spider->start();
