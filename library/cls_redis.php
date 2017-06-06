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
// PHPSpider Redis操作类文件
//----------------------------------

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
    private static $links = array();
    private static $link_name = 'default';
    
    /**
     *  默认redis前缀
     */
    public static $prefix  = "phpspider";

    public static $error  = "";

    public static function init()
    {
        if (!extension_loaded("redis"))
        {
            self::$error = "The redis extension was not found";
            return false;
        }

        // 获取配置
        $config = self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name];

        // 如果当前链接标识符为空，或者ping不同，就close之后重新打开
        //if ( empty(self::$links[self::$link_name]) || !self::ping() )
        if (empty(self::$links[self::$link_name]))
        {
            self::$links[self::$link_name] = new Redis();
            if (!self::$links[self::$link_name]->connect($config['host'], $config['port'], $config['timeout']))
            {
                self::$error = "Unable to connect to redis server\nPlease check the configuration file config/inc_config.php";
                unset(self::$links[self::$link_name]);
                return false;
            }

            // 验证
            if ($config['pass'])
            {
                if ( !self::$links[self::$link_name]->auth($config['pass']) ) 
                {
                    self::$error = "Redis Server authentication failed\nPlease check the configuration file config/inc_config.php";
                    unset(self::$links[self::$link_name]);
                    return false;
                }
            }

            $prefix = empty($config['prefix']) ? self::$prefix : $config['prefix'];
            self::$links[self::$link_name]->setOption(Redis::OPT_PREFIX, $prefix . ":");
            self::$links[self::$link_name]->setOption(Redis::OPT_READ_TIMEOUT, -1);
            self::$links[self::$link_name]->select($config['db']);
        }

        return self::$links[self::$link_name];
    }

    public static function clear_link()
    {
        if(self::$links) 
        {
            foreach(self::$links as $k=>$v)
            {
                $v->close();
                unset(self::$links[$k]);
            }
        }
    }

    public static function set_connect($link_name, $config = array())
    {
        self::$link_name = $link_name;
        if (!empty($config))
        {
            self::$configs[self::$link_name] = $config;
        }
        else
        {
            if (empty(self::$configs[self::$link_name]))
            {
                throw new Exception("You not set a config array for connect!");
            }
        }
        //print_r(self::$configs);

        //// 先断开原来的连接
        //if ( !empty(self::$links[self::$link_name]) )
        //{
            //self::$links[self::$link_name]->close();
            //self::$links[self::$link_name] = null;
        //}
    }

    public static function set_connect_default()
    {
        $config = self::_get_default_config();
        self::set_connect('default', $config);
    }

    /**
    * 获取默认配置
    */
    protected static function _get_default_config()
    {
        if (empty(self::$configs['default']))
        {
            if (!is_array($GLOBALS['config']['redis']))
            {
                exit('cls_redis.php _get_default_config()' . '没有redis配置');
                // You not set a config array for connect\nPlease check the configuration file config/inc_config.php
            }
            self::$configs['default'] = $GLOBALS['config']['redis'];
        }
        return self::$configs['default'];
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
            if ( self::$links[self::$link_name] )
            {
                if ($expire > 0)
                {
                    return self::$links[self::$link_name]->setex($key, $expire, $value);
                }
                else
                {
                    return self::$links[self::$link_name]->set($key, $value);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::set($key, $value, $expire);
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
            if ( self::$links[self::$link_name] )
            {
                if ($expire > 0)
                {
                    return self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    //self::$links[self::$link_name]->multi();
                    //self::$links[self::$link_name]->setNX($key, $value);
                    //self::$links[self::$link_name]->expire($key, $expire);
                    //self::$links[self::$link_name]->exec();
                    //return true;
                }
                else
                {
                    return self::$links[self::$link_name]->setnx($key, $value);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
                return self::setnx($key, $value, $expire);
            }
        }
        return NULL;
    }

    /**
     * 锁
     * 默认锁1秒
     * 
     * @param mixed $name   锁的标识名
     * @param mixed $value  锁的值,貌似没啥意义
     * @param int $expire   当前锁的最大生存时间(秒)，必须大于0，超过生存时间系统会自动强制释放锁
     * @param int $interval   获取锁失败后挂起再试的时间间隔(微秒)
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-30 23:56
     */
    public static function lock($name, $value = 1, $expire = 5, $interval = 100000)
    {
        if ($name == null) return false;

        self::init();
        try
        {
            if ( self::$links[self::$link_name] )
            {
                $key = "Lock:{$name}";
                while (true)
                {
                    // 因为 setnx 没有 expire 设置，所以还是用set
                    //$result = self::$links[self::$link_name]->setnx($key, $value);
                    $result = self::$links[self::$link_name]->set($key, $value, array('nx', 'ex' => $expire));
                    if ($result != false) 
                    {
                        return true;
                    }

                    usleep($interval);
                }
                return false;
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                // 睡眠100毫秒
                usleep(100000);
                return self::lock($name, $value, $expire, $interval);
            }
        }
        return false;
    }

    public static function unlock($name)
    {
        $key = "Lock:{$name}";
        return self::del($key);
    }

    /**
     * get
     * 
     * @param mixed $key
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-12-13 01:05
     */
    public static function get($key)
    {
        self::init();
        try
        {
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->get($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->del($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                $type = self::$links[self::$link_name]->type($key);
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
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                if (empty($integer)) 
                {
                    return self::$links[self::$link_name]->incr($key);
                }
                else 
                {
                    return self::$links[self::$link_name]->incrby($key, $integer);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                if (empty($integer)) 
                {
                    return self::$links[self::$link_name]->decr($key);
                }
                else 
                {
                    return self::$links[self::$link_name]->decrby($key, $integer);
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->append($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->substr($key, $start, $end);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->select($index);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->dbsize();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->flushdb();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->flushall();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                if (!$is_bgsave) 
                {
                    return self::$links[self::$link_name]->save();
                }
                else 
                {
                    return self::$links[self::$link_name]->bgsave();
                }
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->info();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
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
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lastsave();
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lpush($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->rpush($key, $value);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lpop($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->rpop($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lSize($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lget($key, $index);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->lRange($key, $start, $end);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->keys($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->ttl($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->expire($key, $expire);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
            if ( self::$links[self::$link_name] )
            {
                return self::$links[self::$link_name]->exists($key);
            }
        }
        catch (Exception $e)
        {
            $msg = "PHP Fatal error:  Uncaught exception 'RedisException' with message '".$e->getMessage()."'\n";
            log::warn($msg);
            if ($e->getCode() == 0) 
            {
                self::$links[self::$link_name]->close();
                self::$links[self::$link_name] = null;
                usleep(100000);
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
        //if ( empty (self::$links[self::$link_name]) )
        //{
            //return false;
        //}
        //return self::$links[self::$link_name]->ping() == '+PONG';
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


