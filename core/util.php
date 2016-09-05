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
     * 文件锁
     * 如果没有锁，就加一把锁并且执行逻辑，然后删除锁
     * if (!util::lock('statistics_offer'))
     * {
     *     util::lock('statistics_offer');
     *     ...
     *     util::unlock('statistics_offer');
     * }
     * 否则输出锁存在
     * else
     * {
     *     echo "process has been locked\n";
     * }
     * 
     * @param mixed $lock_name
     * @param int $lock_timeout
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-02-18 14:28
     */
    public static function lock($lock_name, $lock_timeout = 600)
    {
        $lock = util::get_file(PATH_DATA."/lock/{$lock_name}.lock");
        if ($lock) 
        {
            $time = time() - $lock;
            // 还没到10分钟，说明进程还活着
            if ($time < $lock_timeout) 
            {
                return true;
            }
            unlink(PATH_DATA."/lock/{$lock_name}.lock");
        }
        util::put_file(PATH_DATA."/lock/{$lock_name}.lock", time());
        return false;
    }

    public static function unlock($lock_name)
    {
        unlink(PATH_DATA."/lock/{$lock_name}.lock");
    }

    public static function get_days($day_sta, $day_end = true, $range = 86400)
    {
        if ($day_end === true) $day_end = date('Y-m-d');

        return array_map(function ($time) {
            return date('Y-m-d', $time);
        }, range(strtotime($day_sta), strtotime($day_end), $range));
    }

    /**
     * 获取文件行数
     * 
     * @param mixed $filepath
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-03-31 21:54
     */
    public static function get_file_line($filepath)
    {
        $line = 0 ;
        $fp = fopen($filepath , 'r');
        if (!$fp) 
        {
            return 0;
        }
        //获取文件的一行内容，注意：需要php5才支持该函数；
        while( stream_get_line($fp,8192,"\n") ){
            $line++;
        }
        fclose($fp);//关闭文件
        return $line;
    }

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

    // 获得当前使用内存
    public static function memory_get_usage()
    {
        $memory = memory_get_usage();
        return self::format_bytes($memory);
    }
    
    // 获得最高使用内存
    public static function memory_get_peak_usage()
    {
        $memory = memory_get_peak_usage();
        return self::format_bytes($memory);
    }
    
    // 转换大小单位
    public static function format_bytes($size)
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
        return self::format_bytes($mem);
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
    
    /**
     * 汉字转拼单
     *
     * @param mixed $str 汉字
     * @param int $ishead
     * @param int $isclose
     * @static
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
     * 检查路径是否存在,不存在则递归生成路径
     *
     * @param mixed $path 路径
     * @static
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
     * 获取文件后缀名
     *
     * @param mixed $file_name 文件名
     * @static
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
    

}


