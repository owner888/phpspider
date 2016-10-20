<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '马蜂窝',
    'tasknum' => 6,
    //'save_running_state' => true,
    'domains' => array(
        'www.mafengwo.cn'
    ),
    'scan_urls' => array(
        "http://www.mafengwo.cn/travel-scenic-spot/mafengwo/10088.html",            // 随便定义一个入口，反正没用
    ),
'list_url_regexes' => array(
    "http://www.mafengwo.cn/mdd/base/list/pagedata_citylist\?page=\d+",         // 城市列表页
    "http://www.mafengwo.cn/gonglve/ajax.php\?act=get_travellist\&mddid=\d+",   // 文章列表页
),
    'content_url_regexes' => array(
        "http://www.mafengwo.cn/i/\d+.html",
    ),
    'export' => array(
        'type' => 'db', 
        'table' => 'mafengwo_content',
    ),
    'fields' => array(
        // 标题
        array(
            'name' => "name",
            'selector' => "//h1[contains(@class,'headtext')]",
            //'selector' => "//div[@id='Article']//h1",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "city",
            'selector' => "//div[contains(@class,'relation_mdd')]//a",
            'required' => true,
        ),
        // 出发时间
        array(
            'name' => "date",
            'selector' => "//li[contains(@class,'time')]",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function($phpspider) 
{
    requests::add_header('Referer','http://www.mafengwo.cn/mdd/citylist/21536.html');
};

$spider->on_scan_page = function($page, $content, $phpspider) 
{
    for ($i = 0; $i <= 297; $i++) 
    {
        // 全国热点城市
        $url = "http://www.mafengwo.cn/mdd/base/list/pagedata_citylist?page={$i}";
        $options = array(
            'url_type' => $url,
            'method' => 'post',
            'params' => array(
                'mddid'=>21536,
                'page'=>$i,
            )
        );
        $phpspider->add_url($url, $options);
    }
};

$spider->on_list_page = function($page, $content, $phpspider) 
{
    // 如果是城市列表页
    if (preg_match("#pagedata_citylist#", $page['request']['url']))
    {
        $data = json_decode($content, true);
        $html = $data['list'];
        preg_match_all('#<a href="/travel-scenic-spot/mafengwo/(.*?).html"#', $html, $out);
        if (!empty($out[1])) 
        {
            foreach ($out[1] as $v) 
            {
                $url = "http://www.mafengwo.cn/gonglve/ajax.php?act=get_travellist&mddid={$v}";
                $options = array(
                    'url_type' => $url,
                    'method' => 'post',
                    'params' => array(
                        'mddid'=>$v,
                        'pageid'=>'mdd_index',
                        'sort'=>1,
                        'cost'=>0,
                        'days'=>0,
                        'month'=>0,
                        'tagid'=>0,
                        'page'=>1,
                    )
                );
                $phpspider->add_url($url, $options);
            }
        }
    }
    // 如果是文章列表页
    else 
    {
        $data = json_decode($content, true);
        $html = $data['list'];
        // 遇到第一页的时候，获取分页数，把其他分页全部入队列
        if ($page['request']['params']['page'] == 1)
        {
            $data_page = trim($data['page']);
            if (!empty($data_page)) 
            {
                preg_match('#<span class="count">共<span>(.*?)</span>页#', $data_page, $out);
                for ($i = 0; $i < $out[1]; $i++) 
                {
                    $v = $page['request']['params']['mddid'];
                    $url = "http://www.mafengwo.cn/gonglve/ajax.php?act=get_travellist&mddid={$v}&page={$i}";
                    $options = array(
                        'url_type' => $url,
                        'method' => 'post',
                        'params' => array(
                            'mddid'=>$v,
                            'pageid'=>'mdd_index',
                            'sort'=>1,
                            'cost'=>0,
                            'days'=>0,
                            'month'=>0,
                            'tagid'=>0,
                            'page'=>$i,
                        )
                    );
                    $phpspider->add_url($url, $options);
                }
            }
        }

        // 获取内容页
        preg_match_all('#<a href="/i/(.*?).html" target="_blank">#', $html, $out);
        if (!empty($out[1])) 
        {
            foreach ($out[1] as $v) 
            {
                $url = "http://www.mafengwo.cn/i/{$v}.html";
                $phpspider->add_url($url);
            }
        }

    }
};

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'date') 
    {
        $data = trim(str_replace(array("出发时间","/"),"", strip_tags($data)));
    }
    return $data;
};

$spider->start();
