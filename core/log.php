<?php

class log
{
    public static $log_show = false;
    public static $log_file = "data/phpspider.log";

    public static function info($msg)
    {
        $out_sta = $out_end = "";
        $msg = $out_sta.$msg.$out_end."\n";
        self::msg($msg);
    }

    public static function warn($msg)
    {
        $out_sta = $out_end = "";
        if (!util::is_win()) 
        {
            $out_sta = "\033[33m";
            $out_end = "\033[0m";
        }

        $msg = $out_sta.$msg.$out_end."\n";
        self::msg($msg);
    }

    public static function debug($msg)
    {
        $out_sta = $out_end = "";
        if (!util::is_win()) 
        {
            $out_sta = "\033[36m";
            $out_end = "\033[0m";
        }

        $msg = $out_sta.$msg.$out_end."\n";
        self::msg($msg);
    }

    public static function error($msg)
    {
        $out_sta = $out_end = "";
        if (!util::is_win()) 
        {
            $out_sta = "\033[31m";
            $out_end = "\033[0m";
        }

        $msg = $out_sta.$msg.$out_end."\n";
        self::msg($msg);
    }

    public static function msg($msg)
    {
        if(self::$log_show)
        {
            echo $msg;
        }
        file_put_contents(self::$log_file, $msg, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录日志 XXX
     * @param string $msg
     * @param string $log_type  Note|Warning|Error
     * @return void
     */
    public static function add($msg, $log_type = '')
    {
        if ($log_type != '') 
        {
            $msg = date("Y-m-d H:i:s")." [{$log_type}] " . $msg . "\n";
        }
        if(self::$log_show)
        {
            echo $msg;
        }
        //file_put_contents(PATH_DATA."/log/".strtolower($log_type).".log", $msg, FILE_APPEND | LOCK_EX);
        file_put_contents(PATH_DATA."/log/error.log", $msg, FILE_APPEND | LOCK_EX);
    }

}

