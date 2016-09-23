<?php

/**
 * 实用函数集合
 *
 * @author seatle<seatle@seatle.com>
 * @version $Id$
 */
class util
{

    public static $client_ip = NULL;

    public static $cfc_handle = NULL;
    
    /**
     * get_table_name
     * 
     * @param mixed $table_name     表名
     * @param mixed $item_value     唯一索引
     * @param int $table_num        表数量
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-10-22 23:25
     */
    public static function get_table_name($table_name, $item_value, $table_num = 100)
    {
        //sha1:返回一个40字符长度的16进制数字
        $item_value = sha1(strtolower($item_value));
        //base_convert:进制建转换，下面是把16进制转成10进制，方便做除法运算
        //str_pad:把字符串填充为指定的长度，下面是在左边加0，共3位
        $step = $table_num > 100 ? 3 : 2;
        $item_value = str_pad(base_convert(substr($item_value, -2), 16, 10) % $table_num, $step, "0", STR_PAD_LEFT);
        return $table_name."_".$item_value;
    }

    // 多进程下获得当前使用内存
    public static function progress_memory_get_usage($progress_num)
    {
        $memory = memory_get_peak_usage(true) * $progress_num;
        return self::convert($memory);
    }

    // 获得当前使用内存
    public static function memory_get_usage()
    {
        $memory = memory_get_usage();
        return self::convert($memory);
    }
    
    // 获得最高使用内存
    public static function memory_get_peak_usage()
    {
        $memory = memory_get_peak_usage();
        return self::convert($memory);
    }
    
    // 转换大小单位
    public static function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * 获取数组大小
     *
     * @param mixed $arr 数组
     * @return string
     */
    public static function array_size($arr)
    {
        ob_start();
        print_r($arr);
        $mem = ob_get_contents();
        ob_end_clean();
        $mem = preg_replace("/\n +/", "", $mem);
        $mem = strlen($mem);
        return self::convert($mem);
    }
    
    /* 16位MD5 */
    public static function md5_16($str)
    {
        return substr(md5($str), 8, 16);
    }
    
    // 7位随机数
    public static function rand_num($num = 7)
    {
        $rand = "";
        for ($i = 0; $i < $num; $i ++)
        {
            $rand .= mt_rand(0, 9);
        }
        return $rand;
    }

    public static function rand_str($num = 10)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $string = "";
        for ($i = 0; $i < $num; $i ++)
        {
            $string .= substr($chars, rand(0, strlen($chars)), 1);
        }
        return $string;
    }
    
    /* PHP escape */
    public static function escape($str)
    {
        preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v)
        {
            if (ord($v[0]) < 128)
            {
                $ar[$k] = rawurlencode($v);
            }
            else
            {
                $ar[$k] = "%u" . bin2hex(iconv(NUll, "UCS-2", $v));
            }
        }
        return join("", $ar);
    }
    
    /* PHP unescape */
    public static function unescape($str)
    {
        $str = rawurldecode($str);
        preg_match_all("/(?:%u.{4})|.+/", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v)
        {
            if (substr($v, 0, 2) == "%u" && strlen($v) == 6)
            {
                $ar[$k] = iconv("UCS-2", NULL, pack("H4", substr($v, -4)));
            }
        }
        return join("", $ar);
    }

    /**
     * 汉字转拼单
     *
     * @param mixed $str 汉字
     * @param int $ishead
     * @param int $isclose
     * @static
     *
     *
     *
     *
     * @access public
     * @return string
     */
    public static function pinyin($str, $ishead = 0, $isclose = 1)
    {
        // $str = iconv("utf-8", "gbk//ignore", $str);
        $str = mb_convert_encoding($str, "gbk", "utf-8");
        global $pinyins;
        $restr = '';
        $str = trim($str);
        $slen = strlen($str);
        if ($slen < 2)
        {
            return $str;
        }
        if (count($pinyins) == 0)
        {
            $fp = fopen(PATH_DATA . '/pinyin.dat', 'r');
            while (!feof($fp))
            {
                $line = trim(fgets($fp));
                $pinyins[$line[0] . $line[1]] = substr($line, 3, strlen($line) - 3);
            }
            fclose($fp);
        }
        for ($i = 0; $i < $slen; $i ++)
        {
            if (ord($str[$i]) > 0x80)
            {
                $c = $str[$i] . $str[$i + 1];
                $i ++;
                if (isset($pinyins[$c]))
                {
                    if ($ishead == 0)
                    {
                        $restr .= $pinyins[$c];
                    }
                    else
                    {
                        $restr .= $pinyins[$c][0];
                    }
                }
                else
                {
                    // $restr .= "_";
                }
            }
            else if (preg_match("/[a-z0-9]/i", $str[$i]))
            {
                $restr .= $str[$i];
            }
            else
            {
                // $restr .= "_";
            }
        }
        if ($isclose == 0)
        {
            unset($pinyins);
        }
        return $restr;
    }

    /**
     * 生成字母前缀
     *
     * @param mixed $s0
     * @static
     *
     *
     *
     *
     * @access public
     * @return char
     */
    public static function letter_first($s0)
    {
        $firstchar_ord = ord(strtoupper($s0{0}));
        if (($firstchar_ord >= 65 and $firstchar_ord <= 91) or ($firstchar_ord >= 48 and $firstchar_ord <= 57)) return $s0{0};
        // $s = iconv("utf-8", "gbk//ignore", $s0);
        $s = mb_convert_encoding($s0, "gbk", "utf-8");
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if ($asc >= -20319 and $asc <= -20284) return "A";
        if ($asc >= -20283 and $asc <= -19776) return "B";
        if ($asc >= -19775 and $asc <= -19219) return "C";
        if ($asc >= -19218 and $asc <= -18711) return "D";
        if ($asc >= -18710 and $asc <= -18527) return "E";
        if ($asc >= -18526 and $asc <= -18240) return "F";
        if ($asc >= -18239 and $asc <= -17923) return "G";
        if ($asc >= -17922 and $asc <= -17418) return "H";
        if ($asc >= -17417 and $asc <= -16475) return "J";
        if ($asc >= -16474 and $asc <= -16213) return "K";
        if ($asc >= -16212 and $asc <= -15641) return "L";
        if ($asc >= -15640 and $asc <= -15166) return "M";
        if ($asc >= -15165 and $asc <= -14923) return "N";
        if ($asc >= -14922 and $asc <= -14915) return "O";
        if ($asc >= -14914 and $asc <= -14631) return "P";
        if ($asc >= -14630 and $asc <= -14150) return "Q";
        if ($asc >= -14149 and $asc <= -14091) return "R";
        if ($asc >= -14090 and $asc <= -13319) return "S";
        if ($asc >= -13318 and $asc <= -12839) return "T";
        if ($asc >= -12838 and $asc <= -12557) return "W";
        if ($asc >= -12556 and $asc <= -11848) return "X";
        if ($asc >= -11847 and $asc <= -11056) return "Y";
        if ($asc >= -11055 and $asc <= -10247) return "Z";
        return 0; // null
    }
    
    // 获得某天前的时间戳
    public static function getxtime($day)
    {
        $day = intval($day);
        return mktime(23, 59, 59, date("m"), date("d") - $day, date("y"));
    }

    /**
     * 获得用户的真实IP 地址
     *
     * HTTP_X_FORWARDED_FOR 的信息可以进行伪造
     * 对于需要检测用户IP是否重复的情况，如投票程序，为了防止IP伪造
     * 可以使用 REMOTE_ADDR + HTTP_X_FORWARDED_FOR 联合使用进行杜绝用户模拟任意IP的可能性
     *
     * @param 多个用多行分开
     * @return void
     */
    public static function get_client_ip()
    {
        $client_ip = '';
        
        if (self::$client_ip !== NULL)
        {
            return self::$client_ip;
        }
        
        // 分析代理IP
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR2']))
        {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR2'];
        }
        
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (empty($client_ip))
        {
            $client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        }
        
        preg_match("/[\d\.]{7,15}/", $client_ip, $onlineip);
        $client_ip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
        
        self::$client_ip = $client_ip;
        
        return $client_ip;
    }

    /**
     * 读文件
     */
    public static function get_file($url, $timeout = 10)
    {
        if (function_exists('curl_init'))
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $content = curl_exec($ch);
            curl_close($ch);
            if ($content) return $content;
        }
        $ctx = stream_context_create(array('http' => array('timeout' => $timeout)));
        $content = @file_get_contents($url, 0, $ctx);
        if ($content) return $content;
        return false;
    }

    /**
     * 写文件，如果文件目录不存在，则递归生成
     */
    public static function put_file($file, $content, $flag = 0)
    {
        $pathinfo = pathinfo($file);
        if (!empty($pathinfo['dirname']))
        {
            if (file_exists($pathinfo['dirname']) === false)
            {
                if (@mkdir($pathinfo['dirname'], 0777, true) === false)
                {
                    return false;
                }
            }
        }
        if ($flag === FILE_APPEND)
        {
            // 多个php-fpm写一个文件的时候容易丢失，要加锁
            //return @file_put_contents($file, $content, FILE_APPEND|LOCK_EX);
            return @file_put_contents($file, $content, FILE_APPEND);
        }
        else
        {
            return @file_put_contents($file, $content, LOCK_EX);
        }
    }

    /**
     * 获得当前的Url
     *
     * @static
     *
     *
     *
     *
     * @access public
     * @return void
     */
    public static function get_cururl()
    {
        if (!empty($_SERVER["REQUEST_URI"]))
        {
            $scriptName = $_SERVER["REQUEST_URI"];
            $nowurl = $scriptName;
        }
        else
        {
            $scriptName = $_SERVER["PHP_SELF"];
            $nowurl = empty($_SERVER["QUERY_STRING"]) ? $scriptName : $scriptName . "?" . $_SERVER["QUERY_STRING"];
        }
        return $nowurl;
    }

    /**
     * 检查路径是否存在,不存在则递归生成路径
     *
     * @param mixed $path 路径
     * @static
     *
     *
     *
     *
     * @access public
     * @return bool or string
     */
    public static function path_exists($path)
    {
        $pathinfo = pathinfo($path . '/tmp.txt');
        if (!empty($pathinfo['dirname']))
        {
            if (file_exists($pathinfo['dirname']) === false)
            {
                if (mkdir($pathinfo['dirname'], 0777, true) === false)
                {
                    return false;
                }
            }
        }
        return $path;
    }

    /**
     * 批量修改目录权限
     *
     * @param mixed $path 目录
     * @param mixed $filemode 权限
     * @return bool
     */
    public static function chmodr($path, $filemode)
    {
        if (!is_dir($path))
        {
            return @chmod($path, $filemode);
        }
        
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false)
        {
            if ($file != '.' && $file != '..')
            {
                $fullpath = $path . '/' . $file;
                if (is_link($fullpath))
                {
                    return FALSE;
                }
                elseif (!is_dir($fullpath) && !@chmod($fullpath, $filemode))
                {
                    return FALSE;
                }
                elseif (!self::chmodr($fullpath, $filemode))
                {
                    return FALSE;
                }
            }
        }
        
        closedir($dh);
        
        if (@chmod($path, $filemode))
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    /**
     * 判断是否为utf8字符串
     * @parem $str
     * @return bool
     */
    public static function is_utf8($str)
    {
        if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32"))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 公共分页函数
     *
     * @param array $config $config['current_page'] //当前页数
     *        $config['page_size'] //每页显示多少条
     *        $config['total_rs'] //总记录数
     *        $config['url_prefix'] //网址前缀
     *        $config['page_name'] //当前分页变量名(默认是page_no， 即访问是用 url_prefix&page_no=xxx )
     *        $config['move_size'] //前后偏移量（默认是5）
     *        $config['input'] //是否使用输入跳转框(0|1)
     *        输出格式：
     *        <div class="page">
     *        <span class="nextprev">&laquo; 上一页</span>
     *        <span class="current">1</span>
     *        <a href="">2</a>
     *        <a href="" class="nextprev">下一页 &raquo;</a>
     *        <span>共 100 页</span>
     *        </div>
     *       
     * @return string
     */
    public static function pagination($config)
    {
        // 参数处理
        $url_prefix = empty($config['url_prefix']) ? '' : $config['url_prefix'];
        $current_page = empty($config['current_page']) ? 1 : intval($config['current_page']);
        $page_name = empty($config['page_name']) ? 'page_no' : $config['page_name'];
        $page_size = empty($config['page_size']) ? 0 : intval($config['page_size']);
        $total_rs = empty($config['total_rs']) ? 0 : intval($config['total_rs']);
        $total_page = ceil($total_rs / $page_size);
        $move_size = empty($config['move_size']) ? 5 : intval($config['move_size']);
        
        // 总页数不到二页返回空
        if ($total_page < 2)
        {
            return '';
        }
        
        // 分页内容
        $pages = '<div class="page">';
        
        // 下一页
        $next_page = $current_page + 1;
        // 上一页
        $prev_page = $current_page - 1;
        // 末页
        $last_page = $total_page;
        
        // 上一页、首页
        if ($current_page > 1)
        {
            $pages .= "<a href='{$url_prefix}' class='nextprev'>首页</a>\n";
            $pages .= "<a href='{$url_prefix}&{$page_name}={$prev_page}' class='nextprev'>上一页</a>\n";
        }
        else
        {
            $pages .= "<span class='nextprev'>首页</span>\n";
            $pages .= "<span class='nextprev'>上一页</span>\n";
        }
        
        // 前偏移
        for ($i = $current_page - $move_size; $i < $current_page; $i ++)
        {
            if ($i < 1)
            {
                continue;
            }
            $pages .= "<a href='{$url_prefix}&{$page_name}={$i}'>$i</a>\n";
        }
        // 当前页
        $pages .= "<span class='current'>" . $current_page . "</span>\n";
        
        // 后偏移
        $flag = 0;
        if ($current_page < $total_page)
        {
            for ($i = $current_page + 1; $i <= $total_page; $i ++)
            {
                $pages .= "<a href='{$url_prefix}&{$page_name}={$i}'>$i</a>\n";
                $flag ++;
                if ($flag == $move_size)
                {
                    break;
                }
            }
        }
        
        // 下一页、末页
        if ($current_page != $total_page)
        {
            $pages .= "<a href='{$url_prefix}&{$page_name}={$next_page}' class='nextprev'>下一页</a>\n";
            $pages .= "<a href='{$url_prefix}&{$page_name}={$last_page}'>末页</a>\n";
        }
        else
        {
            $pages .= "<span class='nextprev'>下一页</span>\n";
            $pages .= "<span class='nextprev'>末页</span>\n";
        }
        
        // 增加输入框跳转
        if (!empty($config['input']))
        {
            $pages .= '<input type="text" class="page" onkeydown="javascript:if(event.keyCode==13){ location=\'' . $url_prefix . '&' . $page_name . '=\'+this.value; }" onkeyup="value=value.replace(/[^\d]/g,\'\')" />';
        }
        
        $pages .= "<span>共 {$total_page} 页 / {$total_rs} 条记录</span><span>&nbsp;当前页是：<font color='red'>{$current_page}</font></span>\n";
        $pages .= '</div>';
        
        return $pages;
    }

    public static function page_fy($config, $index_page, $end_page, $next_page, $pre_page)
    {
        // 参数处理
        $url_prefix = empty($config['url_prefix']) ? '' : $config['url_prefix'];
        $current_page = empty($config['current_page']) ? 1 : intval($config['current_page']);
        $page_name = empty($config['page_name']) ? 'page_no' : $config['page_name'];
        $page_size = empty($config['page_size']) ? 0 : intval($config['page_size']);
        $total_rs = empty($config['total_rs']) ? 0 : intval($config['total_rs']);
        $total_page = ceil($total_rs / $page_size);
        $move_size = empty($config['move_size']) ? 5 : intval($config['move_size']);
        
        // 总页数不到二页返回空
        if ($total_page < 2)
        {
            return '';
        }
        
        // 分页内容
        $pages = '<div class="page">';
        
        // 下一页
        // $next_page = $current_page + 1;
        // 上一页
        // $prev_page = $current_page - 1;
        // 末页
        $last_page = $total_page;
        
        // 上一页、首页
        if ($current_page > 1)
        {
            $pages .= "<a href='{$index_page}' class='nextprev'>首页</a>\n";
            $pages .= "<a href='{$pre_page}' class='nextprev'>上一页</a>\n";
        }
        else
        {
            $pages .= "<span class='nextprev'>首页</span>\n";
            $pages .= "<span class='nextprev'>上一页</span>\n";
        }
        
        // 前偏移
        // for( $i = $current_page - $move_size; $i < $current_page; $i++ )
        // {
        // if ($i < 1) {
        // continue;
        // }
        // $pages .= "<a href='newscenter_$i.html'>$i</a>\n";
        // }
        // 当前页
        $pages .= "<span class='current'>" . $current_page . "</span>\n";
        
        // 后偏移
        // $flag = 0;
        // if ( $current_page < $total_page )
        // {
        // for ($i = $current_page + 1; $i < $total_page; $i++)
        // {
        // $pages .= "<a href='newscenter_$i.html'>$i</a>\n";
        // $flag++;
        // if ($flag == $move_size)
        // {
        // break;
        // }
        // }
        // }
        
        // 下一页、末页
        if ($current_page != $total_page)
        {
            $pages .= "<a href='{$next_page}' class='nextprev'>下一页</a>\n";
            $pages .= "<a href='{$end_page}'>末页</a>\n";
        }
        else
        {
            $pages .= "<span class='nextprev'>下一页</span>\n";
            $pages .= "<span class='nextprev'>末页</span>\n";
        }
        
        // 增加输入框跳转
        if (!empty($config['input']))
        {
            $pages .= '<input type="text" class="page" onkeydown="javascript:if(event.keyCode==13){ location=\'' . $url_prefix . '&' . $page_name . '=\'+this.value; }" onkeyup="value=value.replace(/[^\d]/g,\'\')" />';
        }
        
        $pages .= "<span>共 {$total_page} 页 / {$total_rs} 条记录</span><span>&nbsp;当前页是：<font color='red'>{$current_page}</font></span>\n";
        $pages .= '</div>';
        
        return $pages;
    }

    public static function cn_truncate($string, $length = 80, $etc = '...', $count_words = true)
    {
        mb_internal_encoding("UTF-8");
        if ($length == 0) return '';
        if (strlen($string) <= $length) return $string;
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
        if ($count_words)
        {
            $j = 0;
            $wordscut = "";
            for ($i = 0; $i < count($info[0]); $i ++)
            {
                $wordscut .= $info[0][$i];
                if (ord($info[0][$i]) >= 128)
                {
                    $j = $j + 2;
                }
                else
                {
                    $j = $j + 1;
                }
                if ($j >= $length)
                {
                    return $wordscut . $etc;
                }
            }
            return join('', $info[0]);
        }
        return join("", array_slice($info[0], 0, $length)) . $etc;
    
    }

    /**
     * utf8编码模式的中文截取2，单字节截取模式
     * 这里不使用mbstring扩展
     * @return string
     */
    public static function utf8_substr($str, $slen, $startdd = 0)
    {
        return mb_substr($str, $startdd, $slen, 'UTF-8');
    }

    /**
     * 从普通时间返回Linux时间截(strtotime中文处理版)
     * @parem string $dtime
     * @return int
     */
    public static function cn_strtotime($dtime)
    {
        if (!preg_match("/[^0-9]/", $dtime))
        {
            return $dtime;
        }
        $dtime = trim($dtime);
        $dt = Array(1970, 1, 1, 0, 0, 0);
        $dtime = preg_replace("/[\r\n\t]|日|秒/", " ", $dtime);
        $dtime = str_replace("年", "-", $dtime);
        $dtime = str_replace("月", "-", $dtime);
        $dtime = str_replace("时", ":", $dtime);
        $dtime = str_replace("分", ":", $dtime);
        $dtime = trim(preg_replace("/[ ]{1,}/", " ", $dtime));
        $ds = explode(" ", $dtime);
        $ymd = explode("-", $ds[0]);
        if (!isset($ymd[1]))
        {
            $ymd = explode(".", $ds[0]);
        }
        if (isset($ymd[0]))
        {
            $dt[0] = $ymd[0];
        }
        if (isset($ymd[1])) $dt[1] = $ymd[1];
        if (isset($ymd[2])) $dt[2] = $ymd[2];
        if (strlen($dt[0]) == 2) $dt[0] = '20' . $dt[0];
        if (isset($ds[1]))
        {
            $hms = explode(":", $ds[1]);
            if (isset($hms[0])) $dt[3] = $hms[0];
            if (isset($hms[1])) $dt[4] = $hms[1];
            if (isset($hms[2])) $dt[5] = $hms[2];
        }
        foreach ($dt as $k => $v)
        {
            $v = preg_replace("/^0{1,}/", '', trim($v));
            if ($v == '')
            {
                $dt[$k] = 0;
            }
        }
        $mt = mktime($dt[3], $dt[4], $dt[5], $dt[1], $dt[2], $dt[0]);
        if (!empty($mt))
        {
            return $mt;
        }
        else
        {
            return strtotime($dtime);
        }
    }

    /**
     * 发送邮件
     *
     * @param array $to 收件人
     * @param string $subject 邮件标题
     * @param string $body 邮件内容
     * @return bool
     * @author xiaocai
     */
    public static function send_email($to, $subject, $body)
    {
        $send_account = $GLOBALS['config']['send_smtp_mail_account'];
        try
        {
            $smtp = new cls_mail($send_account['host'], $send_account['port'], true, $send_account['user'], $send_account['password']);
            $smtp->debug = $send_account['debug'];
            $result = $smtp->sendmail($to, $send_account['from'], $subject, $body, $send_account['type']);
            
            return $result;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     * 截取HTML字符串长度
     *
     * @param mixed $string 字符串
     * @param mixed $sublen 长度
     * @static
     *
     *
     *
     *
     * @access public
     * @return string
     */
    public static function xml2array($contents, $get_attributes = 0)
    {
        
        if (!$contents) return array();
        
        if (!function_exists('xml_parser_create'))
        {
            // print "'xml_parser_create()' function not found!";
            return array();
        }
        // Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $contents, $xml_values);
        xml_parser_free($parser);
        
        if (!$xml_values) return; // Hmm...
                                  
        // print_r($xml_values);
                                  // Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        
        $current = &$xml_array;
        
        // Go through the tags.
        foreach ($xml_values as $data)
        {
            unset($attributes, $value); // Remove existing values, or there will be trouble
                                        
            // This command will extract these variables into the foreach scope
                                        // tag(string), type(string), level(int), attributes(array).
            extract($data); // We could use the array by itself, but this cooler.
            
            $result = '';
            if ($get_attributes)
            { // The second argument of the function decides this.
              // $result = array();
                $result = '';
                // if(isset($value)) $result = $value;
                if (isset($value)) $result['value'] = $value;
                
                // Set the attributes too.
                if (isset($attributes))
                {
                    foreach ($attributes as $attr => $val)
                    {
                        if ($get_attributes == 1) $result['attr'][$attr] = $val; // Set all the attributes in a array called 'attr'
                    }
                }
            }
            elseif (isset($value))
            {
                $result = $value;
            }
            
            // See tag status and do the needed.
            if ($type == "open")
            { // The starting of the tag "
                $parent[$level - 1] = &$current;
                
                if (!is_array($current) or (!in_array($tag, array_keys($current))))
                { // Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];
                
                }
                else
                { // There was another element with the same tag name
                    if (isset($current[$tag][0]))
                    {
                        array_push($current[$tag], $result);
                    }
                    else
                    {
                        $current[$tag] = array($current[$tag], $result);
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }
            
            }
            elseif ($type == "complete")
            { // Tags that ends in 1 line "
              // See if the key is already taken.
                if (!isset($current[$tag]))
                { // New Key
                    $current[$tag] = $result;
                
                }
                else
                { // If taken, put all things inside a list(array)
                    if ((is_array($current[$tag]) and $get_attributes == 0) or                     // If it is already an array…
                    (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1))
                    {
                        array_push($current[$tag], $result); // …push the new element into that array.
                    }
                    else
                    { // If it is not an array…
                        $current[$tag] = array($current[$tag], $result); // …Make it an array using using the existing value and the new value
                    }
                }
            
            }
            elseif ($type == 'close')
            { // End of tag "
                $current = &$parent[$level - 1];
            }
        }
        return ($xml_array);
    }

    public static function cutstr_html($string, $sublen)
    {
        $string = strip_tags($string);
        $string = preg_replace('/\n/is', '', $string);
        $string = preg_replace('/ |　/is', '', $string);
        $string = preg_replace('/&nbsp;/is', '', $string);
        
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
        if (count($t_string[0]) - 0 > $sublen) $string = join('', array_slice($t_string[0], 0, $sublen)) . "…";
        else $string = join('', array_slice($t_string[0], 0, $sublen));
        
        return $string;
    }

    /**
     * 获取文件后缀名
     *
     * @param mixed $file_name 文件名
     * @static
     *
     *
     *
     *
     * @access public
     * @return string
     */
    public static function get_extension($file_name)
    {
        $ext = explode('.', $file_name);
        $ext = array_pop($ext);
        return strtolower($ext);
    }

    /**
     * 密码强度 0:最弱 1:较弱 2:中等 3:强
     *
     * @param mixed $str 密码字符串
     * @static
     *
     *
     *
     *
     * @access public
     * @return int
     */
    public static function passwd_intensity($str)
    {
        $score = 0;
        if (preg_match("/[0-9]+/", $str))
        {
            $score ++;
        }
        if (preg_match("/[0-9]{3,}/", $str))
        {
            $score ++;
        }
        if (preg_match("/[a-z]+/", $str))
        {
            $score ++;
        }
        if (preg_match("/[a-z]{3,}/", $str))
        {
            $score ++;
        }
        if (preg_match("/[A-Z]+/", $str))
        {
            $score ++;
        }
        if (preg_match("/[A-Z]{3,}/", $str))
        {
            $score ++;
        }
        if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/", $str))
        {
            $score += 2;
        }
        if (preg_match("/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/", $str))
        {
            $score ++;
        }
        if (strlen($str) >= 10)
        {
            $score ++;
        }
        
        if ($score >= 1 && $score <= 3)
        {
            return 1;
        }
        if ($score >= 4 && $score <= 6)
        {
            return 2;
        }
        if ($score >= 7)
        {
            return 3;
        }
        return 0;
    }
    
    // 获取 Url 跳转后的真实地址
    public static function getrealurl($url)
    {
        if (empty($url))
        {
            return $url;
        }
        $header = get_headers($url, 1);
        if (empty($header[0]) || empty($header[1]))
        {
            return $url;
        }
        if (strpos($header[0], '301') || strpos($header[0], '302'))
        {
            if (empty($header['Location']))
            {
                return $url;
            }
            if (is_array($header['Location']))
            {
                return $header['Location'][count($header['Location']) - 1];
            }
            else
            {
                return $header['Location'];
            }
        }
        else
        {
            return $url;
        }
    }
    
    // 解压服务器用 Content-Encoding:gzip 压缩过的数据
    public static function gzdecode($data)
    {
        $flags = ord(substr($data, 3, 1));
        $headerlen = 10;
        $extralen = 0;
        $filenamelen = 0;
        if ($flags & 4)
        {
            $extralen = unpack('v', substr($data, 10, 2));
            $extralen = $extralen[1];
            $headerlen += 2 + $extralen;
        }
        if ($flags & 8)         // Filename
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
        if ($flags & 16)         // Comment
        $headerlen = strpos($data, chr(0), $headerlen) + 1;
        if ($flags & 2)         // CRC at end of file
        $headerlen += 2;
        $unpacked = @gzinflate(substr($data, $headerlen));
        if ($unpacked === FALSE) $unpacked = $data;
        return $unpacked;
    }

    public static function print_r($info)
    {
        echo "<pre>";
        print_r($info);
        echo "</pre>";
    }

    /**
     * 数字金额转换为中文
     * @param string|integer|float $num 目标数字
     * @param boolean $sim 使用小写（默认）
     * @return string
     */
    public static function number2chinese($num, $sim = FALSE)
    {
        if (!is_numeric($num)) return '含有非数字非小数点字符！';
        $char = $sim ? array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九') : array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $unit = $sim ? array('', '十', '百', '千', '', '万', '亿', '兆') : array('', '拾', '佰', '仟', '', '萬', '億', '兆');
        $retval = '';
        
        $num = sprintf("%01.2f", $num);
        
        list ($num, $dec) = explode('.', $num);
        
        // 小数部分
        if ($dec['0'] > 0)
        {
            $retval .= "{$char[$dec['0']]}角";
        }
        if ($dec['1'] > 0)
        {
            $retval .= "{$char[$dec['1']]}分";
        }
        
        // 整数部分
        if ($num > 0)
        {
            $retval = "元" . $retval;
            $f = 1;
            $str = strrev(intval($num));
            for ($i = 0, $c = strlen($str); $i < $c; $i ++)
            {
                if ($str[$i] > 0)
                {
                    $f = 0;
                }
                if ($f == 1 && $str[$i] == 0)
                {
                    $out[$i] = "";
                }
                else
                {
                    $out[$i] = $char[$str[$i]];
                }
                $out[$i] .= $str[$i] != '0' ? $unit[$i % 4] : '';
                if ($i > 1 and $str[$i] + $str[$i - 1] == 0)
                {
                    $out[$i] = '';
                }
                if ($i % 4 == 0)
                {
                    $out[$i] .= $unit[4 + floor($i / 4)];
                }
            }
            $retval = join('', array_reverse($out)) . $retval;
        }
        return $retval;
    }

    /**
     * define_pages
     * 
     * @author itharry <61647649@qq.com> 
     * @created time 2014-07-15
     * @param array $config
     * @static 
     * @return 
     */
    public static function define_pages($config = array())
    {
        // 跳转地址
        $url_prefix   = empty($config['url_prefix']) ? '' : $config['url_prefix'];
        // 当前第几页
        $current_page = empty($config['current_page']) ? 1 : intval($config['current_page']);
        // 页面参数名称
        $page_name    = empty($config['page_name']) ? 'page_no' : $config['page_name'];
        // 每页多少条
        $page_size    = empty($config['page_size']) ? 0 : intval($config['page_size']);
        // 总共条
        $total_rs     = empty($config['total_rs']) ? 0 : intval($config['total_rs']);
        // 总页面
        $total_page   = ceil($total_rs / $page_size);
        // 偏移量
        $move_size = empty($config['move_size']) ? 5 : intval($config['move_size']);
        
        // 首页
        $fisrt_page = 1;
        // 下一页
        $next_page = ($current_page + 1) >= $total_page ? $total_page : ($current_page + 1);
        // 上一页
        $prev_page = ($current_page - 1) <= 0 ? 1 : ($current_page - 1);
        // 末页
        $last_page = $total_page;

        $page = '<div class="page">';

        // 首页
        $page .= "<a href='{$url_prefix}' class='nextprev'>首页</a>\n";
        // 上一页
        $page .= "<a href='{$url_prefix}&{$page_name}={$prev_page}' class='nextprev'>上一页</a>\n";

        // 一次性显示所有的页面, 适合分页面少的情况
        // 前偏移
        for ($i = $current_page - $move_size; $i < $current_page; $i ++)
        {
            if ($i < 1)
            {
                continue;
            }
            $page .= "<a href='{$url_prefix}&{$page_name}={$i}'>$i</a>\n";
        }
        // 当前页
        $page .= "<span class='current'>" . $current_page . "</span>\n";
        // 后偏移
        $flag = 0;
        if ($current_page < $total_page)
        {
            for ($i = $current_page + 1; $i < $total_page; $i ++)
            {
                $page .= "<a href='{$url_prefix}&{$page_name}={$i}'>$i</a>\n";
                $flag ++;
                if ($flag == $move_size)
                {
                    break;
                }
            }
        }

        // 下一页
        $page .= "<a href='{$url_prefix}&{$page_name}={$next_page}' class='nextprev'>下一页</a>\n";
        // 末页
        $page .= "<a href='{$url_prefix}&{$page_name}={$last_page}'>末页</a>\n";
        $page .= "<span>共 {$total_page} 页 / {$total_rs} 条记录</span><span>&nbsp;当前页是：<font color='red'>{$current_page}</font></span>\n";
        $page .= '</div>';
        return $page;
    }


    /**
     * format_bytes function
     *
     * @return void
     * @author zero<512888425@qq.com>
     **/
    public static function format_bytes($size)
    {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB'); 
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024; 
        return round($size, 2).$units[$i]; 
    }

    
    /**
     *  解决CSV导入内容的逗号关键字  
     * 
     *   1.首先按照“ 进行拆分
     *   2.对于得到的字符串数组，对于偶数列的元素，替换逗号为其他字符
     *   3.再合并成新的字符串
     * @param unknown $string
     * @return string
     */
    public static function save_csv($string)
    {
        
        $arr = explode("\"", $string);
        foreach ($arr as $k => &$v)
        {
            if ($k % 2 != 0)
            {
                $v = str_replace(",", "，", $v);
            }
        }
        
        return implode("", $arr);
    }
    
    /**
     * 把csv文件中带，的数字转换成正常数字
     * @param unknown $number
     */
    public static function save_csv_number($number)
    {
        $number =  str_replace("，", "", $number);
        $number =  str_replace(",", "", $number);
        return $number;
    }
    
    public static function deldir($dir)
    {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) 
        {
            if($file!="." && $file!="..") 
            {
                $fullpath = $dir."/".$file;
                if(!is_dir($fullpath)) 
                {
                    unlink($fullpath);
                } 
                else
                {
                    self::deldir($fullpath);
                }
            }
        }

        closedir($dh);
        //删除当前文件夹：
        if(rmdir($dir)) 
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public static function epooll_client_do($data, $func)
    {
        $is_err = cache::get("netgame", "is_error");
        if ($is_err) 
        {
            //if ($func == 'log_login_user') 
            //{
                //db::insert("netgame_platform_log_login_user", $data);
            //}
            //else 
            //{
                //db::insert("netgame_platform_log_login_role", $data);
            //}
            return;
        }
        $server = @stream_socket_client("tcp://127.0.0.1:1988", $errno, $errmsg, 5, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT);
        //$server = @stream_socket_client("tcp://127.0.0.1:1988", $errno, $errmsg, 5);
        if(!$server)
        {
            cache::set("netgame", "is_error", 1);
            cls_sms::send_sms("13712899314", "日志服务器挂了");
            util::put_file(PATH_DATA."/log/epooll_error.log", " --- ".date("Y-m-d H:i:s")." --- ".$errmsg." --- \r\n", FILE_APPEND);
            return false;
        }

        $send_data = array(
            'func'=>$func,
            'data'=>$data,
        );
        $send_data = json_encode($send_data);
        $send_data = pack('N', strlen($send_data)).$send_data;
        //$result = strlen($data) == stream_socket_sendto($server, $data);
        $result = strlen($send_data) == @fwrite($server, $send_data);
        if (!$result) 
        {
            util::put_file(PATH_DATA."/log/epooll_error.log",  " --- ".date("Y-m-d H:i:s")." --- ".json_encode($data)."\r\n", FILE_APPEND);
        }
        return $result;
    }

}
