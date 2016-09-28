<?php
/**
 * 数据库类
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

class db
{
    private static $config = array();
    private static $conn;
    private static $rsid;
    private static $query_count = 0;
    private static $conn_fail = 0;
    private static $worker_pid = 0;

    public static function _init_mysql($config = array())
    {
        if (empty($config)) 
        {
            // 记住不要把原来有的配置信息给强制换成$GLOBALS['config']['db']，否则换数据库会有问题
            self::$config = empty(self::$config) ? $GLOBALS['config']['db'] : self::$config;
        }
        else 
        {
            self::$config = $config;
        }

        if ( !self::$conn ) 
        {
            self::$conn = @mysqli_connect(self::$config['host'], self::$config['user'], self::$config['pass'], self::$config['name'], self::$config['port']);
            if(mysqli_connect_errno())
            {
                self::$conn_fail++;
                $errmsg = 'Mysql Connect failed['.self::$conn_fail.']: ' . mysqli_connect_error();
                echo util::colorize(date("H:i:s") . " {$errmsg}\n\n", 'fail');
                log::add($errmsg, "Error");
                // 连接失败5次，中断进程
                if (self::$conn_fail >= 5) 
                {
                    exit(250);
                }
                self::_init_mysql($config);
            }
            else
            {
                // 连接成功清零
                self::$conn_fail = 0;
                self::$worker_pid = function_exists('posix_getpid') ? posix_getpid() : 0; 
                mysqli_query(self::$conn, " SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary, sql_mode='' ");
            }
        }
        else 
        {
            $curr_pid = function_exists('posix_getpid') ? posix_getpid() : 0;
            // 如果父进程已经生成资源就释放重新生成，因为多进程不能共享连接资源
            if (self::$worker_pid != $curr_pid) 
            {
                self::reset_connect();
            }
        }

    }

    /**
     * 重新设置连接
     * 传空的话就等于关闭数据库再连接
     * 在多进程环境下如果主进程已经调用过了，子进程一定要调用一次 reset_connect，否则会报错：
     * Error while reading greeting packet. PID=19615，这是两个进程互抢一个连接句柄引起的
     * 
     * @param array $config
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-03-29 00:51
     */
    public static function reset_connect($config = array())
    {
        @mysqli_close(self::$conn);
        self::$conn = null;
        self::_init_mysql($config);
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
        self::$conn = self::_init_mysql();
        // $int = $mode ? 1 : 0;
        // return @mysqli_query(self::$conn, "SET autocommit={$int}");
        return mysqli_autocommit(self::$conn, $mode);
    }

    public static function begin_tran()
    {
        // self::$conn = self::_init_mysql( true );
        // return @mysqli_query(self::$conn, 'BEGIN');
        return self::autocommit(false);
    }

    public static function commit()
    {
        return mysqli_commit(self::$conn);
    }


    public static function rollback()
    {
        return mysqli_rollback(self::$conn);
    }

    public static function query($sql)
    {
        $sql = trim($sql);

        // 初始化数据库
        self::_init_mysql();
        self::$rsid = @mysqli_query(self::$conn, $sql);

        if (self::$rsid === false)
        {
            // 不要每次都ping，浪费流量浪费性能，执行出错了才重新连接
            $errno = mysqli_errno(self::$conn);
            if ($errno == 2013 || $errno == 2006) 
            {
                $errmsg = mysqli_error(self::$conn);
                log::add($errmsg, "Error");

                @mysqli_close(self::$conn);
                self::$conn = null;
                return self::query($sql);
            }

            $errmsg = "Query SQL: ".$sql;
            log::add($errmsg, "Warning");
            $errmsg = "Error SQL: ".mysqli_error(self::$conn);
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
            self::$query_count++;
            return self::$rsid;
        }
    }

    public static function fetch($rsid = '')
    {
        $rsid = self::_get_rsid($rsid);
        $row = mysqli_fetch_array($rsid, MYSQLI_ASSOC);
        return $row;
    }

    public static function get_one($sql, $func = '')
    {
        if (!preg_match("/limit/i", $sql))
        {
            $sql = preg_replace("/[,;]$/i", '', trim($sql)) . " limit 1 ";
        }
        $rsid = self::query($sql);
        if ($rsid === false) 
        {
            return;
        }
        $row = self::fetch($rsid);
        self::free($rsid);
        if (!empty($func))
        {
            return call_user_func($func, $row);
        }
        return $row;
    }

    public static function get_all($sql, $func = '')
    {
        $rsid = self::query($sql);
        if ($rsid === false) 
        {
            return;
        }
        while ( $row = self::fetch($rsid) )
        {
            $rows[] = $row;
        }
        self::free($rsid);
        if (!empty($func))
        {
            return call_user_func($func, $rows);
        }
        return empty($rows) ? false : $rows;
    }

    public static function free($rsid)
    {
        return mysqli_free_result($rsid);
    }

    public static function insert_id()
    {
        return mysqli_insert_id(self::$conn);
    }

    public static function affected_rows()
    {
        return mysqli_affected_rows(self::$conn);
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
                return mysqli_insert_id(self::$conn);
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
                return mysqli_affected_rows(self::$conn);
            }
            else 
            {
                return false;
            }
        }
    }

    public static function ping()
    {
        if (!mysqli_ping(self::$conn))
        {
            @mysqli_close(self::$conn);
            self::$conn = null;
            self::_init_mysql();
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





