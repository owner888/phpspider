<?php
ini_set("memory_limit", "10240M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$url = "http://www.wuxiaworld.com/absolute-choice-index/";
$html = requests::get($url);
if (empty($html)) 
{
    // php的include可以return，return后只是当前文件下面代码不会执行而已，不影响其他被require的php文件
    return;
}
$rows = selector::select($html, "//div[contains(@class,'collapseomatic_content')]/a");

// 有新书上架
if (count($rows) > 207) 
{
    echo "have new book";
    return;
}

for ($i = 1; $i <= $last_chapter_num; $i++) 
{
    if ($i < $db_count) 
    {
        continue;
    }

    $url = "http://www.wuxiaworld.com/absolute-choice-index/";
    $html = requests::get($url);
}
exit;
$not_book_num = 0;
foreach ($rows as $k=>$v) 
{
    if (strpos($v, "About Us") !== false) 
    {
        $not_book_num = $k;
    }
}
$data = array();
foreach ($rows as $k=>$v) 
{
    if ($k >= $not_book_num) 
    {
        continue;
    }
    preg_match('#<a href="(.*?)">(.*?)</a>#', $v, $out);
    $data[] = array(
        'url' => $out[1],
        'name' => $out[2],
    );
}
print_r($data);
exit;

$url = "http://www.wuxiaworld.com/";
$html = requests::get($url);
$rows = selector::select($html, "//ul[contains(@class,'sub-menu')]/li");
$not_book_num = 0;
foreach ($rows as $k=>$v) 
{
    if (strpos($v, "About Us") !== false) 
    {
        $not_book_num = $k;
    }
}
$data = array();
foreach ($rows as $k=>$v) 
{
    if ($k >= $not_book_num) 
    {
        continue;
    }
    preg_match('#<a href="(.*?)">(.*?)</a>#', $v, $out);
    $data[] = array(
        'url' => $out[1],
        'name' => $out[2],
    );
}
print_r($data);
exit;
