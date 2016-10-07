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
    public static $prefix  = "3kwan";

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
        if ( empty(self::$redis) || !self::ping() )
        {
            // 如果当前已经有链接标识符，但是ping不了，则先关闭
            if ( !empty(self::$redis) )
            {
                self::$redis->close();
            }

            if (!extension_loaded("redis"))
            {
                self::$error = "Unable to load redis extension";
                return false;
            }

            self::$redis = new Redis();
            if (!self::$redis->pconnect($configs['host'], $configs['port'], $configs['timeout']))
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
        }

        return self::$redis;
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
        $redis = self::init();

        if ($redis) 
        {
            if ($expire > 0)
            {
                return $redis->setex($key, $expire, $value);
            }
            else
            {
                return $redis->set($key, $value);
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
        $redis = self::init();

        if ($redis) 
        {
            if ($expire > 0)
            {
                return $redis->setnx($key, $expire, $value);
            }
            else
            {
                return $redis->setnx($key, $value);
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
        $redis = self::init();

        if ( $redis )
        {
            $value = $redis->get($key);
            return $value;
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->del($key);
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
        $redis = self::init();
        $types = array(
            '0' => 'set',
            '1' => 'string',
            '3' => 'list',
        );

        if ($redis)
        {
            return $types[$redis->type($key)];
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
        $redis = self::init();

        if ($redis)
        {
            if (empty($integer)) 
            {
                return $redis->incr($key);
            }
            else 
            {
                return $redis->incrby($key, $integer);
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
        $redis = self::init();

        if ($redis)
        {
            if (empty($integer)) 
            {
                return $redis->decr($key);
            }
            else 
            {
                return $redis->decrby($key, $integer);
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->append($key, $value);
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->substr($key, $start, $end);
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->select($index);
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->dbsize();
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->flushdb();
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->flushall();
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
        $redis = self::init();

        if ($redis)
        {
            if (!$is_bgsave) 
            {
                return $redis->save();
            }
            else 
            {
                return $redis->bgsave();
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->info();
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
        $redis = self::init();

        if ($redis)
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->lastsave();
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->lpush($key, $value);
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
        $redis = self::init();

        if ($redis)
        {
            return $redis->rpush($key, $value);
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
        $redis = self::init();
        if ($redis)
        {
            $value = $redis->lpop($key);
            return $value;
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
        $redis = self::init();
        if ($redis)
        {
            $value = $redis->rpop($key);
            return $value;
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->lSize($key);
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
        $redis = self::init() ;
        if ($redis)
        {
            $value = $redis->lget($key, $index);
            return $value;
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->lRange($key, $start, $end);
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->keys($key);
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->ttl($key);
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->expire($key, $expire);
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
        $redis = self::init();
        if ($redis)
        {
            return $redis->exists($key);
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
    protected static function ping()
    {
        if ( empty (self::$redis) )
        {
            return false;
        }
        return self::$redis->ping() == '+PONG';
    }

    public static function encode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public static function decode($value)
    {
        return json_decode($value, true);
    }
}


