<?php
ini_set("memory_limit", "10240M");
require dirname(__FILE__).'/../core/init.php';
//$communitys = include PATH_DATA."/communitys.php";

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$json = requests::get("http://ip.taobao.com/service/getIpInfo.php?ip=122.88.60.28");
$rs = json_decode($json, true);
echo $rs['data']['country'];
exit;
$files = array(
    'file2' => "/Users/yangzetao/Dropbox/data/web/platoa2/admin/static/img/breadcrumb.png",
    'file1' => "test.log",
);
$res = requests::post("http://www.platoa2.com/demo/upload_file.php", array("name"=>"yang"), $files);
print_r(requests::$headers);

var_dump($res);
exit;
//$fp  = finfo_open(FILEINFO_MIME);
//$mime = finfo_file($fp, realpath($path));
//finfo_close($fp);

var_dump($mime);
exit;
$files = new CURLFile(realpath($path),'image/png','testpic');
var_dump($files);
exit;
$type = "";
echo '@'.realpath($path).";type=".$type.";filename=".$filename;
exit;
$url = 'http://www.baidu.com/link?url=77I2GJqjJ4zBBpC8yDF8xDhiqDSn1JZjFWsHhEoSNd85PkV8Xil-rckpQ8_kjGKNNq';
$data = requests::get($url);
print_r(requests::$info);
exit;
$html =<<<STR
    <div id="demo">
        <span class="tt">yyy</span>
        <span class="xx">zzz</span>
        <p>nnn</p>
    </div>
STR;

$data = selector::select($html, "//span[contains(@class,'tt')]");
print_r($data);
exit("\n");
preg_match('#<span class="tt">(.*?)</span>#', $html, $out);
$data = $out[1];
exit("\n");

$urls = selector::select($html, "//a/@href");
if (!is_array($urls)) 
{
    $urls = array($urls);
}

print_r($urls);
exit;
$data = selector::select($html, "//span[contains(@class,'tt') and not(contains(@class, 'xx'))]");
print_r($data);
exit;
$html = selector::select($html, "//div[contains(@id,'demo')]");
$data = selector::remove($html, "//span[contains(@class,'tt')]");
print_r($data);

exit;
$useragents = array(
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_3 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13G34 Safari/601.1",
    "Mozilla/5.0 (Linux; U; Android 6.0.1;zh_cn; Le X820 Build/FEXCNFN5801507014S) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/49.0.0.0 Mobile Safari/537.36 EUI Browser/5.8.015S",
);
requests::set_useragents($useragents);
requests::set_proxies(array("http"=>"http://H784U84R444YABQD:57A8B0B743F9B4D2@proxy.abuyun.com:9010"));

for ($i = 0; $i < 10000; $i++) 
{
    echo "投第 {$i} 票\n";
    requests::del_cookies("heishi.mhacn.com");
    $referer = "http://heishi.mhacn.com/mhaapi/vote/page";
    requests::get($referer);
    //$cookies = requests::get_cookies("heishi.mhacn.com");

    $url = "http://heishi.mhacn.com/mhaapi/game/vote?class_id=4&game_id=213";
    requests::set_referer($referer);
    $json = requests::get($url);
    $data = json_decode($json, true);
    print_r($data);
}
exit;

//$html = file_get_contents($url);
//preg_match('#<div class="content">([^<]*)</div>#iU', $html, $arr);
//print_r($arr);
//preg_match("#<title>([^<]*)</title>#iU", $html, $arr);
//print_r($arr);
//exit;

//----------------------------------
// 计算小区每平方租金
//----------------------------------

//$sql = "Select `community`,`area`,`rent` From `zushoubi_zu_content`";
//$rows = db::get_all($sql);
//$communitys = array();
//foreach ($rows as $row) 
//{
    //if (isset($communitys[$row['community']])) 
    //{
        //$communitys[$row['community']]['area'] = $communitys[$row['community']]['area']+$row['area'];
        //$communitys[$row['community']]['rent'] = $communitys[$row['community']]['rent']+$row['rent'];
    //}
    //else 
    //{
        //$communitys[$row['community']] = $row;
    //}
    ////$price = floor($row['rent'] / $row['area']);
    ////echo $price."\n";
//}


//$array = array();
//foreach ($communitys as $community) 
//{
    //$array[$community['community']] = floor($community['rent']/$community['area']);
//}
//$str = '<?php return '.var_export($array, true).";";
//file_put_contents(PATH_DATA."/communitys.php", $str);
//exit;
//print_r($array);

//----------------------------------
// 计算赚钱的小区
//----------------------------------

$sql = "Select `community`,`area`,`price`,`rent`,`building_year` From `zushoubi`";
$rows = db::get_all($sql);
$csv_str = "小区,面积,30年租金,房子总价,租30年后能赚多少钱,租30年回报率,年回报率\n";
foreach ($rows as $row) 
{
    $price = $row['price'] * $row['area'];
    $rent = $row['rent'] * 12 * 30;
    if ($rent > $price) 
    {
        $money = $rent - $price;
        $money_year_30 = floor($money / $price * 100);
        $money_year_1 = floor($money_year_30 / 30);
        $csv_str .= $row['community'].",".$row['area'].",".$rent.",".$price.",".$money.",".$money_year_30."%,".$money_year_1."%\n";
        //echo "小区：".$row['community']." --- 面积：".$row['area']." --- 30年租金：".$rent." --- 房子价格：".$price." --- 租30年后赚：".$money."\n";
    }
}

$csv_str = iconv("utf-8", "gbk", $csv_str);
echo $csv_str;

exit;


//----------------------------------
// 计算每月租金
//----------------------------------

$sql = "Select `community`,`area`,`price`,`building_year` From `zushoubi_shou_content`";
$rsid = db::query($sql);
while ($row = db::fetch($rsid))
{
    // 如果小区和面积都对的上，直接取租金
    $sql = "Select `community`,`area`,`rent`,`building_year` From `zushoubi_zu_content` Where `community`='{$row['community']}' And `area`='{$row['area']}'";
    $zu_rows = db::get_all($sql);
    if ($zu_rows) 
    {
        foreach ($zu_rows as $zu_row) 
        {
            $row['rent'] = $zu_row['rent'];
            if (empty($row['building_year'])) 
            {
                $row['building_year'] = $zu_row['building_year'];
            }
            echo $row['community']." --- ".$row['area']." --- ".$row['rent']." --- ".$row['price']."\n";
            db::insert("zushoubi", $row);
        }
    }
    else 
    {
        if (isset($communitys[$row['community']])) 
        {
            $row['rent'] = $row['area'] * $communitys[$row['community']];
            echo $row['community']." --- ".$row['area']." --- ".$row['rent']." --- ".$row['price']."\n";
            db::insert("zushoubi", $row);
        }
    }
}
exit;
//$x = '';
//for ($i = 0; $i <= 100; $i+=2) 
//{
    //printf("progress:[%-50s]%d%%\r", $x, $i);
    //sleep(1);
    //$x = "#".$x;
//}
//echo "\n";
//exit;
$x = '';
foreach ($data as $k=>$v) 
{
    printf("progress:[%-100s]%d%%\r", $x, $k);
    sleep(1);
    $x = "#".$x;
}
echo "\n";
exit;
$url = "http://www.qiushibaike.com/article/117943865";
requests::set_useragent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36");
$html = requests::get($url);
$data = selector::select($html, "//*[@id='single-next-link']//div[contains(@class,'content')]/text()[1]");
echo $data;
exit;

$url = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx/JS.aspx?type=ct&st=(BalFlowMain)&sr=-1&p=10&ps=50&js=var%20HwSqidOb={pages:(pc),date:%222014-10-22%22,data:[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._A&sty=DCFFITA&rt=49292006';
$html = requests::get($url);
$json = str_replace("var HwSqidOb=", "", $html);

$data = json_decode($json);
print_r($data);
exit;
echo $html;exit;
$link = array(
    'url'          => "http://www.baidu.com",
    'url_type'     => '',             
    'try_num'      => 0,                 
    //'max_try'      => 0,
    //'depth'        => 0,             
    //'method'       => 'get',             
    //'headers'      => array(),             
    //'params'       => array(),             
    //'context_data' => array(),             
    //'proxy'        => '',             
);

$json = json_encode($link);
//$json = gzdeflate($json);
for ($i = 0; $i < 10000000; $i++) 
{
    $url = $link['url'].$i;
    $key = md5($url);
    cls_redis::set($key, $json);
}

echo util::memory_get_usage()."\n";
exit;


$array = array();

$url = "http://www.baidu.com";
echo util::memory_get_usage()."\n";
for ($i = 0; $i < 1000000; $i++) 
{
    $url = gzdeflate($url);
    $array[] = $url;
}
echo util::memory_get_usage()."\n";
exit;
$url = "http://www.epooll.com/archives/806/";
$html = requests::get($url);

// 抽取文章标题
$selector = "//div[contains(@class,'page-header')]//h1/a";
$title = selector::select($html, $selector);
echo $title;

exit;
$html = requests::get("https://aldcdn.tmall.com/recommend.htm?itemId=539158048764&categoryId=50000557&sellerId=1124599300&shopId=101892145&brandId=213788847&refer=&brandSiteId=0&rn=&appId=03054&isVitual3C=false&isMiao=false&count=15&callback=jsonpAld03054");
preg_match("@\((.*?)\)@", $html, $matchs);
$json = $matchs[1];
$json = iconv("gbk", "utf-8", $json);
$data = json_decode($json, true);
print_r($data);
exit;
$data = selector::select($html, "//div[contains(@class,'hotnews')]");
print_r($data);
exit;
//$proxies = array(
    //'http' => 'http://H569I1428O9U0Z5P:67545392FA7A66E1@proxy.abuyun.com:9010'
//);
//requests::set_proxies($proxies);
//$url = "http://ip.kkk5.com/";
//$html = requests::get($url);
//echo $html;
//exit;

//$html = requests::get("http://www.epooll.com/archives/806/");
//util::put_file(PATH_DATA."/test.html", $html);
//exit;
$html = util::get_file(PATH_DATA."/test.html");
//$urls = selector::select($html, '//a/@href');
//exit;
for ($i = 0; $i < 1000; $i++) 
{
    //preg_match_all("/<a.*href=[\"'](.*)[\"']{0,1}[> \r\n\t]{1,}/isU", $html, $matchs); 
    //$urls = !empty($matchs[1]) ? $matchs[1] : array();
    $urls = selector::select($html, '//a/@href');
    //$urls = selector::select($html, '//a[href]', 'css');
}
exit;
//preg_match_all("/<img(.*)src=[\"']{0,1}(.*)[\"']{0,1}[> \r\n\t]{1,}/isU", $html, $imgs); 

print_r($urls);
exit;
//$data = selector::select($html, '@<title>(.*?)</title>@', "regex");
//$data = selector::select($html, ".page-header > h1 > a", "css");
//$data = selector::select($html, "//div[contains(@class,'page-header')]//h1//a");
print_r($data);
//var_dump($data);

exit;

// 抽取文章标题
$selector = "//div[contains(@class,'page-header')]//h1/a";
$title = selector::select($html, $selector);
// 检查是否抽取到标题
//echo $title;exit;

// 抽取文章作者
$selector = "//div[contains(@class,'page-header')]//h6/span[1]";
$author = selector::select($html, $selector);
// 检查是否抽取到作者
//echo $author;exit;
// 去掉 作者：
$author = str_replace("作者：", "", $author);

// 抽取文章内容
$selector = "//div[contains(@class,'entry-content')]";
$content = selector::select($html, $selector);
// 检查是否抽取到内容
//echo $author;exit;

$data = array(
    'title' => $title,
    'author' => $author,
    'content' => $content,
);

// 查看数据是否正常
//print_r($data);

// 入库
db::insert("content", $data);
exit;
$html = requests::get("http://m.52mnw.cn/photo/47390.html");
$html = selector::select($html, "//title");
var_dump($html);
//$html = selector::select($html, "//div[contains(@class,'pages-box')]");
//$html = selector::select($html, '#<div class="title">(.*?)</div>#i', "regex");
//$html = selector::select($html, '#<input type="hidden" value="(.*?)" class="(.*?)"/>#i', "regex");
exit;

$url = "https://detailskip.taobao.com/service/getData/1/p1/item/detail/sib.htm?itemId=537543886461&sellerId=2680633276&modules=dynStock,qrcode,viewer,price,contract,duty,xmpPromotion,delivery,sellerDetail,activity,fqg,zjys,coupon,couponActivity,soldQuantity&callback=onSibRequestSuccess";
requests::set_referer("https://item.taobao.com/item.htm?spm=a230r.1.14.33.4FRAHN&id=537543886461&ns=1&abbucket=7");
$html = requests::get($url);
var_dump($html);

exit;
// 登录请求url
$login_url = "http://www.waduanzi.com/login?url=http%3A%2F%2Fwww.waduanzi.com%2F";
// 提交的参数
$params = array(
    "LoginForm[returnUrl]" => "http%3A%2F%2Fwww.waduanzi.com%2F",
    "LoginForm[username]" => "13712899314",
    "LoginForm[password]" => "854230",
    "yt0" => "登录",
);
// 发送登录请求
requests::post($login_url, $params);
// 登录成功后本框架会把Cookie保存到www.waduanzi.com域名下，我们可以看看是否是已经收集到Cookie了
$cookies = requests::get_cookies("www.waduanzi.com");
print_r($cookies);  // 可以看到已经输出Cookie数组结构

// 框架自动收集Cookie，访问这个域名下的URL会自动带上
// 接下来我们来访问一个需要登录后才能看到的页面
$url = "http://www.waduanzi.com/member";
$html = requests::get($url);
echo $html;     // 可以看到登录后的页面，非常棒👍

exit;
//$proxy = "http://H569I1428O9U0Z5P:67545392FA7A66E1@proxy.abuyun.com:9010";

//$cookies = 'PHPSESSID=ernpuur1hpuegrjd672tu33nn6';

//$url = "http://www.kkk5.com/index.php?ct=user&ac=register";
//$fields = array(
//'username' => 'test98999900',
//'usrpwd'=>'123456',
//'usrpwdc'=>'123456',
//'verifycode'=>'hgat',
//);
//requests::add_cookies($cookies);
requests::add_header("referer", "http://www.baidu.com");
requests::add_header("user-agent", "yangzetao");
$url = "http://ip.kkk5.com";
$json = requests::get($url);
//$data = json_decode($json, true);
//print_r($data); 
print_r(requests::$raw);
exit;
$cookies = requests::get_cookies();
print_r($cookies);
exit;
var_dump($json);
exit;
//requests::set_proxy($proxy);
$html = requests::get($url);
$html = requests::put($url);
$html = requests::delete($url);
echo $html."\n";
exit;
for ($i = 0; $i < 10; $i++) 
{
    $html = cls_curl::get($url);
    echo $html."\n";
    sleep(5);
}

exit;
$curl = new rolling_curl();
$curl->window_size = 5;
$curl->callback = function($response, $info, $request, $error) {
    if ($error) 
    {
        var_dump($info);
        echo $error."\n";
    }
    else 
    {
        echo $response."\n";
    }
};
$curl->set_proxy($proxy);
$curl->set_headers(array('Proxy-Switch-Ip: yes'));
for ($i = 0; $i < 100; $i++) 
{
    $curl->get($url);
}
$data = $curl->execute();


exit;
$curl = new rolling_curl();
$curl->window_size = 1;
for ($i = 0; $i < 10; $i++) 
{
    $curl->set_proxy($proxy);
    $curl->callback = function($response, $info, $request, $error) {
        var_dump($response);
    };

    $url = "http://ip.kkk5.com";
    for ($j = 0; $j < 5; $j++) 
    {
        $curl->get($url);
        $data = $curl->execute();
        // 睡眠100毫秒，太快了会被认为是ddos
        usleep(100000);
    }
}
