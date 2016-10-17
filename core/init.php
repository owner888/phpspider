<?php

/**
 * phpspider - A PHP Framework For Crawler
 *
 * @package  phpspider
 * @author   Seatle Yang <seatle@foxmail.com>
 */

// 严格开发模式
error_reporting( E_ALL );
//ini_set('display_errors', 1);

// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);
// 内存限制，如果外面设置的内存比 /etc/php/php-cli.ini 大，就不要设置了
if (intval(ini_get("memory_limit")) < 1024) 
{
    ini_set('memory_limit', '1024M');
}

if( PHP_SAPI != 'cli' )
{
    exit("You must run the CLI environment\n");
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

//核心库目录
define('CORE', dirname(__FILE__));
define('PATH_ROOT', CORE."/../");
define('PATH_DATA', CORE."/../data");
define('PATH_LIBRARY', CORE."/../library");

//系统配置
if( file_exists( PATH_ROOT."/config/inc_config.php" ) )
{
    require PATH_ROOT."/config/inc_config.php"; 
}
require CORE.'/log.php';
require CORE.'/requests.php';
require CORE.'/util.php';
require CORE.'/db.php';
require CORE.'/cache.php';

// 启动的时候生成data目录
util::path_exists(PATH_DATA);
util::path_exists(PATH_DATA."/lock");
util::path_exists(PATH_DATA."/log");
util::path_exists(PATH_DATA."/cache");
util::path_exists(PATH_DATA."/status");

require CORE."/worker.php"; 
require CORE."/phpspider.php"; 

/**
 * 自动加载类库处理
 * @return void
 */
function __autoload( $classname )
{
    $classname = preg_replace("/[^0-9a-z_]/i", '', $classname);
    if( class_exists ( $classname ) ) {
        return true;
    }
    $classfile = $classname.'.php';
    try
    {
        if ( file_exists ( PATH_LIBRARY.'/'.$classfile ) )
        {
            require PATH_LIBRARY.'/'.$classfile;
        }
        else
        {
            throw new Exception ( 'Error: Cannot find the '.$classname );
        }
    }
    catch ( Exception $e )
    {
        log::error($e->getMessage().'|'.$classname);
        exit();
    }
}
