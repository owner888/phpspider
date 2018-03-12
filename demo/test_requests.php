<?php
ini_set("memory_limit", "10240M");
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

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

$html = requests::get('http://www.qiushibaike.com/article/118914171');
//echo $html;
//exit;
$data = selector::select($html, "div.author", "css");
echo $data;
