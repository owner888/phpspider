<?php
/**
 * 存储类
 * 这里用redis实现
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author seatle<seatle@foxmail.com>
 * @copyright seatle<seatle@foxmail.com>
 * @link http://www.epooll.com/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

class cache
{
    // 多进程下面不能用单例模式
    //protected static $_instance;
    /**
     * 获取实例
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-04-10 22:55
     */
    public static function init()
    {
        if(extension_loaded('Redis'))
        {
            $_instance = new Redis();
        }
        else
        {
            $errmsg = "extension redis is not installed";
            log::add($errmsg, "Error");
            return null;
        }
        // 这里不能用pconnect，会报错：Uncaught exception 'RedisException' with message 'read error on connection'
        $_instance->connect($GLOBALS['config']['redis']['host'], $GLOBALS['config']['redis']['port'], $GLOBALS['config']['redis']['timeout']);

        // 验证
        if ($GLOBALS['config']['redis']['pass'])
        {
            if ( !$_instance->auth($GLOBALS['config']['redis']['pass']) ) 
            {
                $errmsg = "Redis Server authentication failed!!";
                log::add($errmsg, "Error");
                return null;
            }
        }

        // 不序列化的话不能存数组，用php的序列化方式其他语言又不能读取，所以这里自己用json序列化了，性能还比php的序列化好1.4倍
        //$_instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);     // don't serialize data
        //$_instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);      // use built-in serialize/unserialize
        //$_instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY); // use igBinary serialize/unserialize

        $_instance->setOption(Redis::OPT_PREFIX, $GLOBALS['config']['redis']['prefix'] . ":");

        return $_instance;
    }
}


