<?php

class log
{
    public static $log_show = false;

    /**
     * 记录日志
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

