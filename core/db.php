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
// PHPSpider数据库类文件
//----------------------------------

namespace phpspider\core;

class db
{
    private static $configs = array();
    private static $rsid;
    private static $links = array();
    private static $link_name = 'default';
    private static $autocommiting = false;

    public static function _init()
    {
        // 获取配置
        $config = self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name];

        // 创建连接
        if (empty(self::$links[self::$link_name]) || empty(self::$links[self::$link_name]['conn']))
        {
            // 第一次连接，初始化fail和pid
            if (empty(self::$links[self::$link_name])) 
            {
                self::$links[self::$link_name]['fail'] = 0;
                self::$links[self::$link_name]['pid'] = function_exists('posix_getpid') ? posix_getpid() : 0; 
                //echo "progress[".self::$links[self::$link_name]['pid']."] create db connect[".self::$link_name."]\n";
            }
            self::$links[self::$link_name]['conn'] = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
            if(mysqli_connect_errno())
            {
                self::$links[self::$link_name]['fail']++;
                $errmsg = 'Mysql Connect failed['.self::$links[self::$link_name]['fail'].']: ' . mysqli_connect_error();
                echo util::colorize(date("H:i:s") . " {$errmsg}\n\n", 'fail');
                log::add($errmsg, "Error");
                // 连接失败5次，中断进程
                if (self::$links[self::$link_name]['fail'] >= 5) 
                {
                    exit(250);
                }
                self::_init($config);
            }
            else
            {
                mysqli_query(self::$links[self::$link_name]['conn'], " SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary, sql_mode='' ");
            }
        }
        else 
        {
            $curr_pid = function_exists('posix_getpid') ? posix_getpid() : 0;
            // 如果父进程已经生成资源就释放重新生成，因为多进程不能共享连接资源
            if (self::$links[self::$link_name]['pid'] != $curr_pid) 
            {
                self::clear_link();
            }
        }
    }

    /**
     * 重新设置连接
     * 传空的话就等于关闭数据库再连接
     * 在多进程环境下如果主进程已经调用过了，子进程一定要调用一次 clear_link，否则会报错：
     * Error while reading greeting packet. PID=19615，这是两个进程互抢一个连接句柄引起的
     * 
     * @param array $config
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-03-29 00:51
     */
    public static function clear_link()
    {
        if(self::$links) 
        {
            foreach(self::$links as $k=>$v)
            {
                @mysqli_close($v['conn']);
                unset(self::$links[$k]);
            }
        }
        // 注意，只会连接最后一个，不过貌似也够用了啊
        self::_init();
    }

    /**
     * 改变链接为指定配置的链接(如果不同时使用多个数据库，不会涉及这个操作)
     * @parem  $link_name 链接标识名
     * @parem  $config 多次使用时， 这个数组只需传递一次
     *         config 格式与 $GLOBALS['config']['db'] 一致
     * @return void
     */
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
    }
    
    
    /**
     * 还原为默认连接(如果不同时使用多个数据库，不会涉及这个操作)
     * @parem $config 指定配置（默认使用inc_config.php的配置）
     * @return void
     */
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
            if (!is_array($GLOBALS['config']['db']))
            {
                exit('db.php _get_default_config()' . '没有mysql配置');
            }
            self::$configs['default'] = $GLOBALS['config']['db'];
        }
        return self::$configs['default'];
    }

    /**
     * 返回查询游标
     * @return rsid
     */
    protected static function _get_rsid($rsid = '')
    {
        return $rsid == '' ? self::$rsid : $rsid;
    }

    public static function autocommit($mode = false)
    {
        if ( self::$autocommiting ) 
        {
            return true;
        }

        self::$autocommiting = true;

        self::_init();
        return mysqli_autocommit(self::$links[self::$link_name]['conn'], $mode);
    }

    public static function begin_tran()
    {
        return self::autocommit(false);
    }

    public static function commit()
    {
        mysqli_commit(self::$links[self::$link_name]['conn']);
        self::autocommit(true);
        return true;
    }


    public static function rollback()
    {
        mysqli_rollback(self::$links[self::$link_name]['conn']);
        self::autocommit(true);
        return true;
    }

    public static function query($sql)
    {
        $sql = trim($sql);

        // 初始化数据库
        self::_init();
        self::$rsid = @mysqli_query(self::$links[self::$link_name]['conn'], $sql);

        if (self::$rsid === false)
        {
            // 不要每次都ping，浪费流量浪费性能，执行出错了才重新连接
            $errno = mysqli_errno(self::$links[self::$link_name]['conn']);
            if ($errno == 2013 || $errno == 2006) 
            {
                $errmsg = mysqli_error(self::$links[self::$link_name]['conn']);
                log::add($errmsg, "Error");

                @mysqli_close(self::$links[self::$link_name]['conn']);
                self::$links[self::$link_name]['conn'] = null;
                return self::query($sql);
            }

            $errmsg = "Query SQL: ".$sql;
            log::add($errmsg, "Warning");
            $errmsg = "Error SQL: ".mysqli_error(self::$links[self::$link_name]['conn']);
            log::add($errmsg, "Warning");

            $backtrace = debug_backtrace();
            array_shift($backtrace);
            $narr = array('class', 'type', 'function', 'file', 'line');
            $err = "debug_backtrace：\n";
            foreach($backtrace as $i => $l)
            {
                foreach($narr as $k)
                {
                    if( !isset($l[$k]) ) 
                    {
                        $l[$k] = '';
                    }
                }
                $err .= "[$i] in function {$l['class']}{$l['type']}{$l['function']} ";
                if($l['file']) $err .= " in {$l['file']} ";
                if($l['line']) $err .= " on line {$l['line']} ";
                $err .= "\n";
            }
            log::add($err);

            return false;
        }
        else
        {
            return self::$rsid;
        }
    }

    public static function fetch($rsid = '')
    {
        $rsid = self::_get_rsid($rsid);
        $row = mysqli_fetch_array($rsid, MYSQLI_ASSOC);
        return $row;
    }

    public static function get_one($sql)
    {
        if (!preg_match("/limit/i", $sql))
        {
            $sql = preg_replace("/[,;]$/i", '', trim($sql)) . " limit 1 ";
        }
        $rsid = self::query($sql);
        if ($rsid === false) 
        {
            return array();
        }
        $row = self::fetch($rsid);
        self::free($rsid);
        return $row;
    }

    public static function get_all($sql)
    {
        $rsid = self::query($sql);
        if ($rsid === false) 
        {
            return array();
        }
        while ( $row = self::fetch($rsid) )
        {
            $rows[] = $row;
        }
        self::free($rsid);
        return empty($rows) ? false : $rows;
    }

    public static function free($rsid)
    {
        return mysqli_free_result($rsid);
    }

    public static function insert_id()
    {
        return mysqli_insert_id(self::$links[self::$link_name]['conn']);
    }

    public static function affected_rows()
    {
        return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
    }

    public static function insert($table = '', $data = null, $return_sql = false)
    {
        $items_sql = $values_sql = "";
        foreach ($data as $k => $v)
        {
            $v = stripslashes($v);
            $v = addslashes($v);
            $items_sql .= "`$k`,";
            $values_sql .= "\"$v\",";
        }
        $sql = "Insert Ignore Into `{$table}` (" . substr($items_sql, 0, -1) . ") Values (" . substr($values_sql, 0, -1) . ")";
        if ($return_sql) 
        {
            return $sql;
        }
        else 
        {
            if (self::query($sql))
            {
                return mysqli_insert_id(self::$links[self::$link_name]['conn']);
            }
            else 
            {
                return false;
            }
        }
    }

    public static function insert_batch($table = '', $set = NULL, $return_sql = FALSE) 
    {
        if (empty($table) || empty($set)) 
        {
            return false;
        }
        $set = self::strsafe($set);
        $fields = self::get_fields($table);

        $keys_sql = $vals_sql = array();
        foreach ($set as $i=>$val) 
        {
            ksort($val);
            $vals = array();
            foreach ($val as $k => $v)
            {
                // 过滤掉数据库没有的字段
                if (!in_array($k, $fields)) 
                {
                    continue;
                }
                // 如果是第一个数组，把key当做插入条件
                if ($i == 0 && $k == 0) 
                {
                    $keys_sql[] = "`$k`";
                }
                $vals[] = "\"$v\"";
            }
            $vals_sql[] = implode(",", $vals);
        }

        $sql = "Insert Ignore Into `{$table}`(".implode(", ", $keys_sql).") Values (".implode("), (", $vals_sql).")";

        if ($return_sql) return $sql;
        
        $rt = self::query($sql);
        $insert_id = self::insert_id();
        $return = empty($insert_id) ? $rt : $insert_id;
        return $return;
    }

    public static function update_batch($table = '', $set = NULL, $index = NULL, $where = NULL, $return_sql = FALSE) 
    {
        if (empty($table) || is_null($set) || is_null($index)) 
        {
            // 不要用exit，会中断程序
            return false;
        }
        $set = self::strsafe($set);
        $fields = self::get_fields($table);

        $ids = array();
        foreach ($set as $val)
		{
            ksort($val);
            // 去重，其实不去也可以，因为相同的when只会执行第一个，后面的就直接跳过不执行了
            $key = md5($val[$index]);
			$ids[$key] = $val[$index];

			foreach (array_keys($val) as $field)
			{
				if ($field != $index)
				{
					$final[$field][$key] =  'When `'.$index.'` = "'.$val[$index].'" Then "'.$val[$field].'"';
				}
			}
		}
        //$ids = array_values($ids);

        // 如果不是数组而且不为空，就转数组
        if (!is_array($where) && !empty($where))
        {
            $where = array($where);
        }
        $where[] = $index.' In ("'.implode('","', $ids).'")';
        $where = empty($where) ? "" : " Where ".implode(" And ", $where);

		$sql = "Update `".$table."` Set ";
		$cases = '';

		foreach ($final as $k => $v)
		{
            // 过滤掉数据库没有的字段
            if (!in_array($k, $fields)) 
            {
                continue;
            }
			$cases .= '`'.$k.'` = Case '."\n";
			foreach ($v as $row)
			{
				$cases .= $row."\n";
			}

			$cases .= 'Else `'.$k.'` End, ';
		}

		$sql .= substr($cases, 0, -2);

        // 其实不带 Where In ($index) 的条件也可以的
		$sql .= $where;

        if ($return_sql) return $sql;
        
        $rt = self::query($sql);
        $insert_id = self::affected_rows();
        $return = empty($affected_rows) ? $rt : $affected_rows;
        return $return;
    }

    public static function update($table = '', $data = array(), $where = null, $return_sql = false)
    {
        $sql = "UPDATE `{$table}` SET ";
        foreach ($data as $k => $v)
        {
            $v = stripslashes($v);
            $v = addslashes($v);
            $sql .= "`{$k}` = \"{$v}\",";
        }
        if (!is_array($where))
        {
            $where = array($where);
        }
        // 删除空字段,不然array("")会成为WHERE
        foreach ($where as $k => $v)
        {
            if (empty($v))
            {
                unset($where[$k]);
            }
        }
        $where = empty($where) ? "" : " Where " . implode(" And ", $where);
        $sql = substr($sql, 0, -1) . $where;
        if ($return_sql) 
        {
            return $sql;
        }
        else 
        {
            if (self::query($sql))
            {
                return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
            }
            else 
            {
                return false;
            }
        }
    }

    public static function delete($table = '', $where = null, $return_sql = false)
    {
        // 小心全部被删除了
        if (empty($where)) 
        {
            return false;
        }
        $where = 'Where ' . (!is_array($where) ? $where : implode(' And ', $where));
        $sql = "Delete From `{$table}` {$where}";
        if ($return_sql) 
        {
            return $sql;
        }
        else 
        {
            if (self::query($sql))
            {
                return mysqli_affected_rows(self::$links[self::$link_name]['conn']);
            }
            else 
            {
                return false;
            }
        }
    }

    public static function ping()
    {
        if (!mysqli_ping(self::$links[self::$link_name]['conn']))
        {
            @mysqli_close(self::$links[self::$link_name]['conn']);
            self::$links[self::$link_name]['conn'] = null;
            self::_init();
        }
    }

    public static function strsafe($array)
    {
        $arrays = array();
        if(is_array($array)===true)
        {
            foreach ($array as $key => $val)
            {                
                if(is_array($val)===true)
                {
                    $arrays[$key] = self::strsafe($val);
                }
                else 
                {
                    //先去掉转义，避免下面重复转义了
                    $val = stripslashes($val);
                    //进行转义
                    $val = addslashes($val);
                    //处理addslashes没法处理的 _ % 字符
                    //$val = strtr($val, array('_'=>'\_', '%'=>'\%'));
                    $arrays[$key] = $val;
                }
            }
            return $arrays;
        }
        else 
        {
            $array = stripslashes($array);
            $array = addslashes($array);
            //$array = strtr($array, array('_'=>'\_', '%'=>'\%'));
            return $array;
        }
    }

    // 这个是给insert、update、insert_batch、update_batch用的
    public static function get_fields($table)
    {
        // $sql = "SHOW COLUMNS FROM $table"; //和下面的语句效果一样
        $rows = self::get_all("Desc `{$table}`");
        $fields = array();
        foreach ($rows as $k => $v)
        {
            // 过滤自增主键
            // if ($v['Key'] != 'PRI')
            if ($v['Extra'] != 'auto_increment')
            {
                $fields[] = $v['Field'];
            }
        }
        return $fields;
    }

    public static function table_exists($table_name)
    {
        $sql = "SHOW TABLES LIKE '" . $table_name . "'";
        $rsid = self::query($sql);
        $table = self::fetch($rsid);
        if (empty($table)) 
        {
            return false;
        }
        return true;
    }
}






