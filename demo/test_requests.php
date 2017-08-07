<?php
ini_set("memory_limit", "10240M");
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$html = requests::get('https://zhuanlan.zhihu.com/p/26369491');
//echo $html;
$data = selector::select($html, "//title");
echo $data;
