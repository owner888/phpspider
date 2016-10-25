<?php

/**
 * @package 
 * 
 * @version 2.7.0
 * @copyright 1997-2015 The PHP Group
 * @author seatle <seatle@foxmail.com> 
 * @created time :2015-12-13
 */
class cls_redis
{
    /**
     *  redis链接标识符号
     */
    protected static $redis   = NULL;

    /**
     *  redis配置数组
     */
    protected static $configs = array();
    
    /**
     *  默认redis前缀
     */
    public static $prefix  = "phpspider";

    public static $error  = "";

    public static function init()
    {
        // 获取配置
        $configs = empty(self::$configs) ? self::_get_default_config() : self::$configs;
        if (empty($configs)) 
        {
            self::$error = "You not set a config array for connect";
            return false;
        }

        // 如果当前链接标识符为空，或者ping不同，就close之后重新打开
        //if ( empty(self::$redis) || !self::ping() )
        if ( !self::$redis )
        {
            self::$redis = new Redis();
            if (!self::$redis->connect($configs['host'], $configs['port'], $configs['timeout']))
            {
                self::$error = "Unable to connect to redis server";
                self::$redis = null;
                return false;
            }

            // 验证
            if ($configs['pass'])
            {
                if ( !self::$redis->auth($configs['pass']) ) 
                {
                    self::$error = "Redis Server authentication failed";
                    self::$redis = null;
                    return false;
                }
            }

            $prefix = empty($configs['prefix']) ? self::$prefix : $configs['prefix'];
            self::$redis->setOption(Redis::OPT_PREFIX, $prefix . ":");
            self::$redis->setOption(Redis::OPT_READ_TIMEOUT, -1);
        }

        return self::$redis;
    }

    public static function close()
    {
        self::$redis->close();
        self::$redis = null;
    }

    public static function set_connect($config = array())
    {
        // 先断开原来的连接
        if ( !empty(self::$redis) )
        {
            self::$redis->close();
            self::$redis = null;
        }

        if (!empty($config))
        {
            self::$configs = $config;
        }
        else
        {
            if (empty(self::$configs))
            {
                throw new Exception("You not set a config array for connect!");
            }
        }
    }

    public static function set_connect_default($config = '')
    {
        if (empty($config))
        {
            $config = self::_get_default_config();
        }
        self::set_connect($config);
    }

    /**
    * 获取默认配置
    */
    protected static function _get_default_config()
    {
        if (empty($GLOBALS['config']['redis']))
        {
            return array();
        }
        self::$configs = $GLOBALS['config']['redis'];
        return self::$configs;
    }

    /**
     * set
     * 
     * @param mixed $key    键
     * @param mixed $value  值
     * @param int $expire   过期时间，单位：秒
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function set($key, $value, $expire = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if ($expire > 0)
                {
                    return self::$redis->setex($key, $expire, $value);
                }
                else
                {
                    return self::$redis->set($key, $value);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::set($key, $expire, $value);
            }
        }
        return NULL;
    }


    /**
     * set
     * 
     * @param mixed $key    键
     * @param mixed $value  值
     * @param int $expire   过期时间，单位：秒
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function setnx($key, $value, $expire = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if ($expire > 0)
                {
                    return self::$redis->setnx($key, $expire, $value);
                }
                else
                {
                    return self::$redis->setnx($key, $value);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::setnx($key, $expire, $value);
            }
        }
        return NULL;
    }

    /**
     * get
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function get( $key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->get($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::get($key);
            }
        }
        return NULL;
    }

    /**
     * del 删除数据
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function del($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->del($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::del($key);
            }
        }
        return NULL;
    }

    /**
     * type 返回值的类型
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function type($key)
    {
        self::init();

        $types = array(
            '0' => 'set',
            '1' => 'string',
            '3' => 'list',
        );

        try
        {
            if ( self::$redis )
            {
                $type = self::$redis->type($key);
                if (isset($types[$type])) 
                {
                    return $types[$type];
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::type($key);
            }
        }
        return NULL;
    }

    /**
     * incr 名称为key的string增加integer, integer为0则增1
     * 
     * @param mixed $key
     * @param int $integer
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function incr($key, $integer = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if (empty($integer)) 
                {
                    return self::$redis->incr($key);
                }
                else 
                {
                    return self::$redis->incrby($key, $integer);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::incr($key, $integer);
            }
        }
        return NULL;
    }

    /**
     * decr 名称为key的string减少integer, integer为0则减1
     * 
     * @param mixed $key
     * @param int $integer
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function decr($key, $integer = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if (empty($integer)) 
                {
                    return self::$redis->decr($key);
                }
                else 
                {
                    return self::$redis->decrby($key, $integer);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::decr($key, $integer);
            }
        }
        return NULL;
    }

    /**
     * append 名称为key的string的值附加value
     * 
     * @param mixed $key
     * @param mixed $value
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function append($key, $value)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->append($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::append($key, $value);
            }
        }
        return NULL;
    }

    /**
     * substr 返回名称为key的string的value的子串
     * 
     * @param mixed $key
     * @param mixed $start
     * @param mixed $end
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function substr($key, $start, $end)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->substr($key, $start, $end);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::substr($key, $start, $end);
            }
        }
        return NULL;
    }

    /**
     * select 按索引查询
     * 
     * @param mixed $index
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function select($index)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->select($index);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::select($index);
            }
        }
        return NULL;
    }

    /**
     * dbsize 返回当前数据库中key的数目
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function dbsize()
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->dbsize();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::dbsize();
            }
        }
        return NULL;
    }

    /**
     * flushdb 删除当前选择数据库中的所有key
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function flushdb()
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->flushdb();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::flushdb();
            }
        }
        return NULL;
    }

    /**
     * flushall 删除所有数据库中的所有key
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function flushall()
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->flushall();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::flushall();
            }
        }
        return NULL;
    }

    /**
     * save 将数据保存到磁盘
     * 
     * @param mixed $is_bgsave 将数据异步保存到磁盘
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function save($is_bgsave = false)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if (!$is_bgsave) 
                {
                    return self::$redis->save();
                }
                else 
                {
                    return self::$redis->bgsave();
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::save($is_bgsave);
            }
        }
        return NULL;
    }

    /**
     * info 提供服务器的信息和统计
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function info()
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->info();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::info();
            }
        }
        return NULL;
    }

    /**
     * slowlog 慢查询日志
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function slowlog($command = 'get', $len = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                if (!empty($len)) 
                {
                    return $redis->slowlog($command, $len);
                }
                else 
                {
                    return $redis->slowlog($command);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::slowlog($command, $len);
            }
        }
        return NULL;
    }

    /**
     * lastsave 返回上次成功将数据保存到磁盘的Unix时戳
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-18 11:28
     */
    public static function lastsave()
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lastsave();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lastsave();
            }
        }
        return NULL;
    }

    /**
     * lpush 将数据从左边压入
     * 
     * @param mixed $key
     * @param mixed $value
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function lpush($key, $value)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lpush($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lpush($key, $value);
            }
        }
        return NULL;
    }

    /**
     * rpush 将数据从右边压入
     * 
     * @param mixed $key
     * @param mixed $value
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function rpush($key, $value)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->rpush($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::rpush($key, $value);
            }
        }
        return NULL;
    }

    /**
     * lpop 从左边弹出数据, 并删除数据
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function lpop($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lpop($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lpop($key);
            }
        }
        return NULL;
    }

    /**
     * rpop 从右边弹出数据, 并删除数据
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function rpop($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->rpop($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::rpop($key);
            }
        }
        return NULL;
    }

    /**
     * lsize 队列长度，同llen
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function lsize($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lSize($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lsize($key);
            }
        }
        return NULL;
    }

    /**
     * lget 获取数据
     * 
     * @param mixed $key
     * @param int $index
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function lget($key, $index = 0)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lget($key, $index);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lget($key, $index);
            }
        }
        return NULL;
    }

    /**
     * lRange 获取范围数据
     * 
     * @param mixed $key
     * @param mixed $start
     * @param mixed $end
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function lrange($key, $start, $end)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->lRange($key, $start, $end);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::lrange($key, $start, $end);
            }
        }
        return NULL;
    }

    /**
     * rlist 从右边弹出 $length 长度数据，并删除数据
     * 
     * @param mixed $key
     * @param mixed $length
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function rlist($key, $length)
    {
        $queue_length = self::lsize($key);
        // 如果队列中有数据
        if ($queue_length > 0)
        {
            $list = array();
            $count = ($queue_length >= $length) ? $length : $queue_length;
            for ($i = 0; $i < $count; $i++) 
            {
                $data = self::rpop($key);
                if ($data === false)
                {
                    continue;
                }

                $list[] = $data;
            }
            return $list;
        }
        else
        {
            // 没有数据返回NULL
            return NULL;
        }
    }

    /**
     * keys
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     * 查找符合给定模式的key。
     * KEYS *命中数据库中所有key。
     * KEYS h?llo命中hello， hallo and hxllo等。
     * KEYS h*llo命中hllo和heeeeello等。
     * KEYS h[ae]llo命中hello和hallo，但不命中hillo。
     * 特殊符号用"\"隔开
     * 因为这个类加了OPT_PREFIX前缀，所以并不能真的列出redis所有的key，需要的话，要把前缀去掉
     */
    public static function keys($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->keys($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::keys($key);
            }
        }
        return NULL;
    }

    /**
     * ttl 返回某个KEY的过期时间 
     * 正数：剩余多少秒
     * -1：永不超时
     * -2：key不存在
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function ttl($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->ttl($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::ttl($key);
            }
        }
        return NULL;
    }

    /**
     * expire 为某个key设置过期时间,同setTimeout
     * 
     * @param mixed $key
     * @param mixed $expire
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function expire($key, $expire)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->expire($key, $expire);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::expire($key, $expire);
            }
        }
        return NULL;
    }

    /**
     * exists key值是否存在
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function exists($key)
    {
        self::init();
        try
        {
            if ( self::$redis )
            {
                return self::$redis->exists($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$redis->close();
                self::$redis = null;
                sleep(1);
                return self::exists($key);
            }
        }
        return false;
    }

    /**
     * ping 检查当前redis是否存在且是否可以连接上
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    //protected static function ping()
    //{
        //if ( empty (self::$redis) )
        //{
            //return false;
        //}
        //return self::$redis->ping() == '+PONG';
    //}

    public static function encode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function decode($value)
    {
        return json_decode($value, true);
    }
}


