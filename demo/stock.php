<?php
ini_set("memory_limit", "10240M");
require dirname(__FILE__).'/../core/init.php';
//$communitys = include PATH_DATA."/communitys.php";

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$url = "http://www.aastocks.com/tc/ltp/rtquote.aspx?symbol=00187";
$html = requests::get($url);
$data = selector::select($html, "//div[contains(@class,'div-container')]/table[contains(@class,'tb-c')]");
var_dump($data);
exit;
for ($z = 1; $z <= 438; $z++) 
{
    echo "process page {$z}\n";
    $url = "http://www.aastocks.com/tc/stocks/quote/symbolsearch.aspx?page={$z}&order=symbol&seq=asc";
    $html = requests::get($url);
    $symbols = selector::select($html, "//table[contains(@class,'tblM')]/tr/td[1]");
    $names = selector::select($html, "//table[contains(@class,'tblM')]/tr/td[2]");
    $name_ens = selector::select($html, "//table[contains(@class,'tblM')]/tr/td[3]");

    $data = array();
    for ($i = 0; $i < 20; $i++) 
    {
        $data[] = array(
            'symbol' => str_replace(".HK", "", strip_tags($symbols[$i])),
            'name' => $names[$i],
            'name_en' => $name_ens[$i],
        );
    }
    db::insert_batch("stock", $data);
}

