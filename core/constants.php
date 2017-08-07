<?php
// +----------------------------------------------------------------------
// | PHPSpider [ A PHP Framework For Crawler ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 https://doc.phpspider.org All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Seatle Yang <seatle@foxmail.com>
// +----------------------------------------------------------------------

//----------------------------------
// PHPSpider公共入口文件
//----------------------------------

//namespace phpspider\core;

// Display errors.
ini_set('display_errors', 'on');
// Reporting all.
error_reporting(E_ALL);

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

// Date.timezone
if (!ini_get('date.timezone')) 
{
    date_default_timezone_set('Asia/Shanghai');
}

//核心库目录
define('CORE', dirname(__FILE__));
define('PATH_ROOT', CORE."/../");
define('PATH_DATA', CORE."/../data");
define('PATH_LIBRARY', CORE."/../library");

//系统配置
//if( file_exists( PATH_ROOT."/config/inc_config.php" ) )
//{
    //require PATH_ROOT."/config/inc_config.php"; 
//}


