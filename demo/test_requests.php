<?php
ini_set("memory_limit", "10240M");
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$bot_token = "10100386:Z0dT3Oalvu5IGC71OrvGs3hT";

$url = "https://api.potato.im:8443/{$bot_token}/sendTextMessage";

$data = array(
    'chat_type' => 2,
    'chat_id'   => 10160267,
    'text'      => 'Hello',
);
$data = json_encode($data);
requests::set_header("Content-Type", "application/json");
$html = requests::post($url, $data);
var_dump($html);
exit;


//$url = "https://api.telegram.org/bot631221524:AAHmiCfIDNfJdae1WXXNNQvhC7t2qSSjqPE/setWebhook";
$url = "https://api.potato.im:8443/{$bot_token}/setWebhook";

$data = array('url'=>'https://www.quivernote.com/bot.php');
$data = json_encode($data);
requests::set_header("Content-Type", "application/json");
$html = requests::post($url, $data);
var_dump($html);    


exit;
$html = requests::get('http://lishi.zhuixue.net/xiachao/576024.html');
//echo $html;
$data = selector::select($html, "//div[@class='list']");
print_r($data);
exit;

//$html =<<<STR
    //<div id="demo">
        //aaa
        //<span class="tt">bbb</span>
        //<span>ccc</span>
        //<p>ddd</p>
    //</div>
//STR;

//// 获取id为demo的div内容
////$data = selector::select($html, "//div[contains(@id,'demo')]");
//$data = selector::select($html, "#demo", "css");
//print_r($data);

requests::set_proxy(array('223.153.69.150:42354'));
$html = requests::get('https://www.quivernote.com/test.php');
var_dump($html);    
exit;
$html = requests::get('http://www.qiushibaike.com/article/118914171');
//echo $html;
//exit;
$data = selector::select($html, "div.author", "css");
echo $data;
