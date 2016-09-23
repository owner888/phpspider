<?php
class cls_query
{
    private static $content;
    public static $debug = false;

    public static function init($content)
    {
        self::$content = $content;
    }

    public static function query($query, $attr = "html")
    {
        $nodes = self::get_nodes($query);
        $datas = self::get_datas($nodes, $attr);
        return $datas;
    }

    protected static function is_char($char) {
		return extension_loaded('mbstring') ? mb_eregi('\w', $char) : preg_match('@\w@', $char);
	}

    /**
     * 从xpath中得到节点
     * 
     * @param mixed $xpath
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-08-08 15:52
     */
    private static function get_nodes($query)
    {
        // 把一到多个空格 替换成 一个空格
        // 把 > 和  ~ 符号两边的空格去掉，因为没有用这两个符号，所以这里可以不这么做
        // ul>li.className
        $query = trim(
			preg_replace('@\s+@', ' ',
				preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)
			)
		);

        $nodes = array();
		if (! $query)
        {
			return $nodes;
        }

        $query_arr = explode(" ", $query);
        foreach ($query_arr as $k=>$v) 
        {
            $path = $k == 0 ? $v : $path.' '.$v;
            $node = array("path"=>(string)$path, "name"=>"", "id"=>"", "class"=>"", "other"=>array());
            // 如果存在内容选择器
            if (preg_match('@(.*?)\[(.*?)=[\'|"](.*?)[\'|"]\]@', $v, $matches) && !empty($matches[2]) && !empty($matches[3])) 
            {
                // 把选择器过滤掉 [rel='topic']
                $v = $matches[1];
                $node['other'] = array(
                    'key'=>$matches[2],
                    'val'=>$matches[3],
                );
            }

            // 如果存在 id
            $id_arr = explode("#", $v);
            $class_arr = explode(".", $v);
            if (count($id_arr) === 2) 
            {
                $node['name'] = $id_arr[0];
                $node['id'] = $id_arr[1];
            }
            // 如果存在 class
            elseif (count($class_arr) === 2) 
            {
                $node['name'] = $class_arr[0];
                $node['class'] = $class_arr[1];
            }
            // 如果没有样式
            else 
            {
                $node['name'] = $v;
            }
            $nodes[] = $node;
        }
        //print_r($nodes);
        //exit;
        return $nodes;
    }

    public static function get_datas($nodes, $attr = "html")
    {
        if (empty(self::$content)) 
        {
            return false;
        }

        $node_datas = array();
        $count = count($nodes);
        // 循环所有节点
        foreach ($nodes as $i=>$node) 
        {
            $is_last = $count == $i+1 ? true : false;
            // 第一次
            if ($i == 0) 
            {
                $datas = array();
                $datas = self::get_node_datas($node, self::$content, $attr, $is_last);
                // 如果第一次都取不到数据，直接跳出循环
                if(!$datas)
                {
                    break;
                }
                $node_datas[$nodes[$i]['path']] = $datas;
            }
            else 
            {
                $datas = array();
                // 循环上一个节点的数组
                foreach ($node_datas[$nodes[$i-1]['path']] as $v) 
                {
                    $datas = array_merge( $datas, self::get_node_datas($node, trim($v), $attr, $is_last) );
                }
                $node_datas[$nodes[$i]['path']] = $datas;
                // 删除上一个节点，防止内存溢出，或者缓存到本地，再次使用？！
                unset($node_datas[$nodes[$i-1]['path']]);
            }
        }   
        //print_r($datas);exit;
        // 从数组中弹出最后一个元素
        $node_datas = array_pop($node_datas);
        //print_r($node_datas);
        //exit;
        return $node_datas;
    }

    /**
     * 从节点中获取内容
     * $regex = '@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i';
     * 
     * @param mixed $node
     * @param mixed $content
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2015-08-08 15:52
     */
    private static function get_node_datas($node, $content, $attr = "html", $is_last = false)
    {
        $node_datas = $datas = array();

        if (!empty($node['id'])) 
        {
            if ($node['name']) 
                $regex = '@<'.$node['name'].'[^>]+id\\s*=\\s*["|\']+?'.$node['id'].'\\s*[^>]+?>(.*?)</'.$node['name'].'>@is';
            else 
                $regex = '@id\\s*=\\s*["|\']+?'.$node['id'].'\\s*[^>]+?>(.*?)<@is';
        }
        elseif (!empty($node['class'])) 
        {
            if ($node['name']) 
                $regex = '@<'.$node['name'].'[^>]+class\\s*=\\s*["|\']+?'.$node['class'].'\\s*[^>]+?>(.*?)</'.$node['name'].'>@is';
            else 
                $regex = '@class\\s*=\\s*["|\']+?'.$node['class'].'\\s*[^>]+?>(.*?)<@is';
        }
        else 
        {
            // 这里为是么是*，0次到多次，因为有可能是 <li>
            $regex = '@<'.$node['name'].'[^>]*?>(.*?)</'.$node['name'].'>@is';
        }
        self::log("regex --- " . $regex);;
        preg_match_all($regex, $content, $matches);
        $all_datas = empty($matches[0]) ? array() : $matches[0];
        $html_datas = empty($matches[1]) ? array() : $matches[1];

        // 过滤掉选择器对不上的
        foreach ($all_datas as $i=>$data) 
        {
            // 如果有设置其他选择器，验证一下选择器
            if (!empty($node['other'])) 
            {
                $regex = '@'.$node['other']['key'].'=[\'|"]'.$node['other']['val'].'[\'|"]@is';
                self::log("regex other --- " . $regex);
                // 过滤器对不上的，跳过
                if (!preg_match($regex, $data, $matches)) 
                {
                    continue;
                }
            }
            // 获取节点的html内容
            if ($attr != "html" && $is_last) 
            {
                $regex = '@'.$attr.'=[\'|"](.*?)[\'|"]@is';
                preg_match($regex, $data, $matches); 
                $node_datas[] = empty($matches[1]) ? '' : trim($matches[1]);
            }
            // 获取节点属性名的值
            else 
            {
                $node_datas[] = trim($html_datas[$i]);
            }
        }
        //echo " 11111 ========================================= \n";
        //print_r($node_datas);
        //echo " 22222 ========================================= \n\n\n";
        return $node_datas;
    }

    /**
     * 记录日志
     * @param string $msg
     * @return void
     */
    private static function log($msg)
    {
        $msg = "[".date("Y-m-d H:i:s")."] " . $msg . "\n";
        if (self::$debug) 
        {
            echo $msg;
        }
    }

}

//$xpath = "ul.top-nav-dropdown li";
//$xpath = "i.zg-icon";
//print_r($nodes);
//exit;
// [^>]+ 不是>的字符重复一次到多次, ? 表示不贪婪
// \s 表示空白字符
// * 表示0次或者多次
// + 表示1次或者多次
//
// 后向引用，表示表达式中，从左往右数，第一个左括号对应的括号内的内容。
// \\0 表示整个表达式
// \\1表示第1个表达式
// \\2表示第2个表达式
// $regex = '@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i';
//preg_match_all($regex, $content, $matches);
//print_r($matches);
//exit;

// 用法
//$content = file_get_contents("./test.html");
//$query = "ul#top-nav-profile-dropdown li a";
//$query = "div#zh-profile-following-topic a.link[href='/topic/19550937']";
//cls_query::init($content);
//$list = cls_query::query($query, "href");
//print_r($list);

