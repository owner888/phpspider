<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

//requests::set_cookies("__gads=ID=71d11421dd9291e3:T=1437507612:S=ALNI_MZq9o6qr4kNuHQjtQnLADk00forkQ; YF-Ugrow-G0=57484c7c1ded49566c905773d5d00f82; _s_tentry=sg.weibo.com; Apache=3079349338077.009.1437507901192; SINAGLOBAL=3079349338077.009.1437507901192; YF-Page-G0=19f6802eb103b391998cb31325aed3bc; login_sid_t=fba51dddd3aed26bfcbcb77c9e32e9cd; TC-V5-G0=d6c372d8b8b800aa7fd9c9d95a471b97; TC-Ugrow-G0=e66b2e50a7e7f417f6cc12eec600f517; TC-Page-G0=4e714161a27175839f5a8e7411c8b98c; WBStore=8ca40a3ef06ad7b2|undefined; ULV=1468806626310:1:1:1:3079349338077.009.1437507901192:; YF-V5-G0=55f24dd64fe9a2e1eff80675fb41718d; wb_g_upvideo_1749184974=1; appkey=; UOR=,,login.sina.com.cn; SCF=AsDo-k6pDrvKiZG7rt09qEnO7PRFsCo5jX9TOcX5nL7875QBrakXsYfJZxvFzgND9kxyUheagphgNxAqa0CqnAc.; SUB=_2A251ecsRDeRxGeBP61YY8i3LzjiIHXVWDrvZrDV8PUNbmtBeLWrakW9ikjtCvjlFKW863eRsIaMtpW0aug..; SUBP=0033WrSXqPxfM725Ws9jqgMF55529P9D9Whi1UBiCw3kWKEHbl_7gEHC5JpX5KzhUgL.FoqpehB4eoeNSKB2dJLoI7_o9PSQIK241hn7S5tt; SUHB=0M21Z-kXTa_mNL; ALF=1516170945; SSOLoginState=1484634945; wvr=6; wb_g_upvideo_6104923754=1; WBtopGlobal_register_version=60539f809b40ed0d");
//$url = "http://weibo.com/p/1003061212812142/follow?page=3";
//$html = requests::get($url);
//echo $html;exit;
//$page = selector::select($html, "//div[contains(@class,'W_pages')]/a[contains(@class,'page_dis')][2]/following-sibling::*[1]");

//$names = selector::select($html, "//ul[contains(@class,'follow_list')]/li/dl/dt/a/img/@alt");

//$action_datas = selector::select($html, "//ul[contains(@class,'follow_list')]/li/@action-data");

//$addrs = selector::select($html, "//div[contains(@class,'info_add')]/span");
//var_dump($addrs);exit;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => '微博用户',
    'log_show' => true,
    'user_agent' => 'PHPSpider',
    //'multiserver' => true,
    //'serverid' => 2,
    'tasknum' => 1,
    //'save_running_state' => true,
    //'proxy' => 'http://H784U84R444YABQD:57A8B0B743F9B4D2@proxy.abuyun.com:9010',
    //'proxy' => 'http://H569I1428O9U0Z5P:67545392FA7A66E1@proxy.abuyun.com:9010',
    'proxy' => 'http://H6F9I7K565Y49A5P:90C146A1FE287149@proxy.abuyun.com:9010',
    'domains' => array(
        'weibo.com',
        'www.weibo.com'
    ),
    'scan_urls' => array(
        'http://weibo.com/p/1003061212812142/follow?page=3',
    ),
    'list_url_regexes' => array(
        "http://weibo.com/p/\d+/follow\?page=\d+",              // 他的关注
        "http://weibo.com/p/\d+/follow\?relate=fans\&page=\d+"  // 他的粉丝
    ),
    'content_url_regexes' => array(
    ),
    'max_try' => 5,
    'fields' => array(
    
    ),
);

$spider = new phpspider($configs);

$spider->on_start = function($phpspider) 
{
    requests::set_cookies("__gads=ID=71d11421dd9291e3:T=1437507612:S=ALNI_MZq9o6qr4kNuHQjtQnLADk00forkQ; YF-Ugrow-G0=57484c7c1ded49566c905773d5d00f82; _s_tentry=sg.weibo.com; Apache=3079349338077.009.1437507901192; SINAGLOBAL=3079349338077.009.1437507901192; YF-Page-G0=19f6802eb103b391998cb31325aed3bc; login_sid_t=fba51dddd3aed26bfcbcb77c9e32e9cd; TC-V5-G0=d6c372d8b8b800aa7fd9c9d95a471b97; TC-Ugrow-G0=e66b2e50a7e7f417f6cc12eec600f517; TC-Page-G0=4e714161a27175839f5a8e7411c8b98c; WBStore=8ca40a3ef06ad7b2|undefined; ULV=1468806626310:1:1:1:3079349338077.009.1437507901192:; YF-V5-G0=55f24dd64fe9a2e1eff80675fb41718d; wb_g_upvideo_1749184974=1; appkey=; UOR=,,login.sina.com.cn; SCF=AsDo-k6pDrvKiZG7rt09qEnO7PRFsCo5jX9TOcX5nL7875QBrakXsYfJZxvFzgND9kxyUheagphgNxAqa0CqnAc.; SUB=_2A251ecsRDeRxGeBP61YY8i3LzjiIHXVWDrvZrDV8PUNbmtBeLWrakW9ikjtCvjlFKW863eRsIaMtpW0aug..; SUBP=0033WrSXqPxfM725Ws9jqgMF55529P9D9Whi1UBiCw3kWKEHbl_7gEHC5JpX5KzhUgL.FoqpehB4eoeNSKB2dJLoI7_o9PSQIK241hn7S5tt; SUHB=0M21Z-kXTa_mNL; ALF=1516170945; SSOLoginState=1484634945; wvr=6; wb_g_upvideo_6104923754=1; WBtopGlobal_register_version=60539f809b40ed0d");

    for ($i = 1; $i <= 5; $i++) 
    {
        $url = "http://weibo.com/p/1003061212812142/follow?page={$i}";
        $phpspider->add_url($url);
    }
};



$spider->on_list_page = function($page, $content, $phpspider)
{
    //$page = selector::select($content, "//div[contains(@class,'W_pages')]/a[contains(@class,'page_dis')][2]/following-sibling::*[1]");
    $follow = selector::select($content, "//div[contains(@class,'info_connect')]/span[1]/em/a");
    $fans   = selector::select($content, "//div[contains(@class,'info_connect')]/span[2]/em/a");
    $weibo  = selector::select($content, "//div[contains(@class,'info_connect')]/span[3]/em/a");
    $addrs  = selector::select($content, "//div[contains(@class,'info_add')]/span");
    $infos  = selector::select($content, "//ul[contains(@class,'follow_list')]/li/@action-data");

    foreach ($infos as $k=>$v) 
    {
        $arr = util::http_split_query($v, true);
        $arr['follow'] = $follow[$k];
        $arr['fans']   = $fans[$k];
        $arr['weibo']  = $weibo[$k];
        $arr['addrs']  = $addrs[$k];

        // 微博只支持看前5页关注
        // 他的关注
        $follow_page = intval($arr['follow'] / 20);
        $follow_page = $follow_page > 5 ? 5 : $follow_page;
        if (!empty($follow_page)) 
        {
            for ($i = 1; $i <= $follow_page; $i++) 
            {
                $url = "http://weibo.com/p/{$arr['uid']}/follow?page={$i}";
                $phpspider->add_url($url);
            }
        }

        // 他的粉丝
        $fans_page = intval($arr['fans'] / 20);
        $fans_page = $fans_page > 5 ? 5 : $fans_page;
        if (!empty($fans_page)) 
        {
            for ($i = 1; $i <= $fans_page; $i++) 
            {
                $url = "http://weibo.com/p/{$arr['uid']}/follow?relate=fans&page={$i}";
                $phpspider->add_url($url);
            }
        }

        $sql = "Select Count(*) As `count` From `weibo_user` Where `uid`='{$arr['uid']}'";
        $row = db::get_one($sql);
        if (!$row['count']) 
        {
            db::insert("weibo_user", $arr);
        }
    }

};

$spider->on_extract_field = function($fieldname, $data, $page) 
{
};

$spider->start();
