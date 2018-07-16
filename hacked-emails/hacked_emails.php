<?php
ini_set("memory_limit", "10240M");
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\requests;
use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

hacked_emails::random_banner();
exit;
class hacked_emails
{
    // Colors
    // green - yellow - blue - red - white - magenta - cyan - reset
    public static $color_g = "\033[92m";
    public static $color_y = "\033[93m";
    public static $color_b = "\033[94m";
    public static $color_r = "\033[91m";
    public static $color_w = "\033[0m";
    public static $color_m = "\x1b[35m";
    public static $color_c = "\x1b[36m";
    public static $end = "\x1b[39m";
    public static $bold = "\033[1m";

    public static function random_banner()
    {
        $banners = file_get_contents("banners.txt");
        $banners = explode('$$$$$AnyShIt$$$$$$', $banners);
        $banner = $banners[count($banners)-1];
        $banner_to_print = self::$color_g;
        $banner_to_print .= $banner;
        $banner_to_print .= self::$end;

        $name = self::$color_b."Hacked Emails By ".self::$bold."@seatle -".self::$color_m." V0.1".self::$color_g;
        $banner_to_print = str_replace("{Name}", $name, $banner_to_print);
        $description = self::$color_c."Know the dangers of email credentials reuse attacks.".self::$color_g;
        $banner_to_print = str_replace("{Description}", $description, $banner_to_print);
	    $loaded = self::$color_b."Loaded ".self::$color_y."14".self::$color_b." website.".self::$color_g;
        $banner_to_print = str_replace("{Loaded}", $loaded, $banner_to_print);
        echo $banner_to_print;
    }
}

$html = requests::get('http://www.qiushibaike.com/article/118914171');
//echo $html;
//exit;
$data = selector::select($html, "div.author", "css");
echo $data;
