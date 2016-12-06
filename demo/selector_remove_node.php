<?php
ini_set("memory_limit", "10240M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$html =<<<STR
    <div id="demo">
        aaa
        <span class="tt">bbb</span>
        <span>ccc</span>
        <p>ddd</p>
    </div>
STR;

//----------------------------------
// xpath selector
//----------------------------------

// 获取id为demo的div内容
$html = selector::select($html, "//div[contains(@id,'demo')]");
// 在上面获取内容基础上，删除class为tt的span标签
$data = selector::remove($html, "//span[contains(@class,'tt')]");
print_r($data);


//----------------------------------
// css selector
//----------------------------------

//// 获取id为demo的div内容
//$html = selector::select($html, "div#demo", "css");
//// 在上面获取内容基础上，删除class为tt的span标签
//$data = selector::remove($html, "span.tt", "css");
//print_r($data);


//----------------------------------
// regex selector
//----------------------------------

//// 获取id为demo的div内容
//$html = selector::select($html, '@<div id="demo">(.*?)</div>@s', "regex");
//// 在上面获取内容基础上，删除class为tt的span标签
//$data = selector::remove($html, '@<span class="tt">(.*?)</span>@', "regex");
//print_r($data);

