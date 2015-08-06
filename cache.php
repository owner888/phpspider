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
    /**
     * 实例数组
     * @var array
     */
    protected static $_instance;

    /**
     * 获取实例
     * @param string $config_name
     * @throws Exception
     */
    public static function get_instance()
    {
        if(!isset(self::$_instance))
        {
            if(extension_loaded('Redis'))
            {
                self::$_instance = new Redis();
            }
            else
            {
                sleep(2);
                exit("extension redis is not installed\n");
            }
            self::$_instance->connect($GLOBALS['config']['redis']['host'], $GLOBALS['config']['redis']['port']);
        }
        return self::$_instance;
    }
}

