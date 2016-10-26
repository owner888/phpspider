<?php

/**
 * phpspider - A PHP Framework For Crawler
 *
 * @package  phpspider
 * @author   Seatle Yang <seatle@foxmail.com>
 */

class phpspider
{
    /**
     * 版本号
     * @var string
     */
    const VERSION = '2.2.2';

    /**
     * 爬虫爬取每个网页的时间间隔,0表示不延时，单位：秒
     */
    const INTERVAL = 0;

    /**
     * 爬虫爬取每个网页的超时时间，单位：秒 
     */
    const TIMEOUT = 5;

    /**
     * 爬取失败次数，不想失败重新爬取则设置为0 
     */
    const MAX_TRY = 0;

    /**
     * 抽取规则的类型：xpath、jsonpath、regex 
     */
    const FIELDS_SELECTOR_TYPE = 'xpath';

    /**
     * 爬虫爬取网页所使用的浏览器类型：android，ios，pc，mobile
     */
    const AGENT_ANDROID = "Mozilla/5.0 (Linux; U; Android 6.0.1;zh_cn; Le X820 Build/FEXCNFN5801507014S) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/49.0.0.0 Mobile Safari/537.36 EUI Browser/5.8.015S";
    const AGENT_IOS = "Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_3 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13G34 Safari/601.1";
    const AGENT_PC = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36";
    const AGENT_MOBILE = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36";

    /**
     * pid文件的路径及名称
     * @var string
     */
    public static $pid_file = '';

    /**
     * 日志目录，默认在data根目录下
     * @var mixed
     */
    public static $log_file = '';

    /**
     * 运行 status 命令时用于保存结果的文件名
     * @var string
     */
    public static $statistics_file = '';

    /**
     * 主任务进程ID 
     */
    public static $master_pid = 0;

    /**
     * 所有任务进程ID 
     */
    //public static $taskpids = array();

    /**
     * 当前任务ID 
     */
    public static $taskid = 1;

    /**
     * 当前任务进程ID 
     */
    public static $taskpid = 1;

    /**
     * 并发任务数
     */
    public static $tasknum = 1;

    /**
     * 任务主进程 
     */
    public static $taskmaster = true;

    /**
     * 任务主进程状态 
     */
    public static $taskmaster_status = false;

    /**
     * 是否保存爬虫运行状态 
     */
    public static $save_running_state = false;

    /**
     * 是否清空上次爬虫运行状态 
     */
    public static $clean_last_state = false;

    /**
     * 试运行
     * 试运行状态下，程序持续三分钟或抓取到30条数据后停止
     */
    //public static $test_run = true;

    /**
     * 配置 
     */
    public static $configs = array();

    /**
     * 要抓取的URL队列 
     md5(url) => array(
         'url'         => '',      // 要爬取的URL
         'url_type'    => '',      // 要爬取的URL类型,scan_page、list_page、content_page
         'method'      => 'get',   // 默认为"GET"请求, 也支持"POST"请求
         'headers'     => array(), // 此url的Headers, 可以为空
         'params'      => array(), // 发送请求时需添加的参数, 可以为空
         'context_data'=> '',      // 此url附加的数据, 可以为空
         'proxy'       => false,   // 是否使用代理
         'try_num'     => 0        // 抓取次数
         'max_try'     => 0              // 允许抓取失败次数
     ) 
     */
    public static $collect_queue = array();

    /**
     * 要抓取的URL数组
     * md5($url) => time()
     */
    public static $collect_urls = array();

    /**
     * 已经抓取过的URL数组
     * md5($url) => time()
     */
    public static $collected_urls = array();

    /**
     * 要抓取的URL数量
     */
    public static $collect_urls_num = 0;

    /**
     * 已经抓取的URL数量
     */
    public static $collected_urls_num = 0;

    /**
     * 爬虫开始时间 
     */
    public static $time_start = 0;
    
    /**
     * 当前进程采集成功数 
     */
    public static $collect_succ = 0;

    /**
     * 当前进程采集失败数 
     */
    public static $collect_fail = 0;

    public static $taskid_length = 6;
    public static $pid_length = 6;
    public static $mem_length = 8;
    public static $urls_length = 15;
    public static $speed_length = 6;

    /**
     * 提取到的字段数 
     */
    public static $fields_num = 0;

    /**
     * 采集深度 
     */
    public static $depth_num = 0;

    public static $task_status = array();

    public static $export_type = '';
    public static $export_file = '';
    public static $export_conf = '';
    public static $export_table = '';

    /**
     * 爬虫初始化时调用, 用来指定一些爬取前的操作 
     * 
     * @var mixed
     * @access public
     */
    public $on_start = null;

    /**
     * 切换IP代理后，先前请求网页用到的Cookie会被清除，这里可以再次添加 
     * 
     * @var mixed
     * @access public
     */
    public $on_change_proxy = null;

    public $on_status_code = null;

    /**
     * 判断当前网页是否被反爬虫，需要开发者实现 
     * 
     * @var mixed
     * @access public
     */
    public $is_anti_spider = null;

    /**
     * 在一个网页下载完成之后调用，主要用来对下载的网页进行处理 
     * 
     * @var mixed
     * @access public
     */
    public $on_download_page = null;

    /**
     * 在一个attached_url对应的网页下载完成之后调用. 主要用来对下载的网页进行处理 
     * 
     * @var mixed
     * @access public
     */
    public $on_attached_download_page = null;

    /**
     * URL属于入口页 
     * 在爬取到入口url的内容之后, 添加新的url到待爬队列之前调用 
     * 主要用来发现新的待爬url, 并且能给新发现的url附加数据
     * 
     * @var mixed
     * @access public
     */
    public $on_scan_page = null;
    
    /**
     * URL属于列表页
     * 在爬取到列表页url的内容之后, 添加新的url到待爬队列之前调用 
     * 主要用来发现新的待爬url, 并且能给新发现的url附加数据
     * 
     * @var mixed
     * @access public
     */
    public $on_list_page = null;

    /**
     * URL属于内容页 
     * 在爬取到内容页url的内容之后, 添加新的url到待爬队列之前调用 
     * 主要用来发现新的待爬url, 并且能给新发现的url附加数据
     * 
     * @var mixed
     * @access public
     */
    public $on_content_page = null;

    /**
     * 在抽取到field内容之后调用, 对其中包含的img标签进行回调处理 
     * 
     * @var mixed
     * @access public
     */
    public $on_handle_img = null;

    /**
     * 当一个field的内容被抽取到后进行的回调, 在此回调中可以对网页中抽取的内容作进一步处理 
     * 
     * @var mixed
     * @access public
     */
    public $on_extract_field = null;

    /**
     * 在一个网页的所有field抽取完成之后, 可能需要对field进一步处理, 以发布到自己的网站 
     * 
     * @var mixed
     * @access public
     */
    public $on_extract_page = null;

    /**
     * 如果抓取的页面是一个附件文件，比如图片、视频、二进制文件、apk、ipad、exe 
     * 就不去分析他的内容提取field了，提取field只针对HTML
     * 
     * @var mixed
     * @access public
     */
    public $on_attachment_file = null;

    function __construct($configs = array())
    {
        // 先打开以显示验证报错内容
        log::$log_show = true;
        log::$log_file = isset(self::$configs['log_file']) ? self::$configs['log_file'] : PATH_DATA.'/phpspider.log';

        // 彩蛋
        $included_files = get_included_files();
        $content = file_get_contents($included_files[0]);
        if (!preg_match("#/\* Do NOT delete this comment \*/#", $content) || !preg_match("#/\* 不要删除这段注释 \*/#", $content))
        {
            $msg = "未知错误；请参考文档或寻求技术支持。";
            if (util::is_win()) 
            {
                $msg = mb_convert_encoding($msg, "gbk", "utf-8");
            }
            log::error($msg);
            exit;
        }

        self::$configs = $configs;
        self::$configs['name']       = isset(self::$configs['name'])       ? self::$configs['name']       : 'phpspider';
        self::$configs['proxy']      = isset(self::$configs['proxy'])      ? self::$configs['proxy']      : '';
        self::$configs['user_agent'] = isset(self::$configs['user_agent']) ? self::$configs['user_agent'] : self::AGENT_PC;
        self::$configs['interval']   = isset(self::$configs['interval'])   ? self::$configs['interval']   : self::INTERVAL;
        self::$configs['timeout']    = isset(self::$configs['timeout'])    ? self::$configs['timeout']    : self::TIMEOUT;
        self::$configs['max_try']    = isset(self::$configs['max_try'])    ? self::$configs['max_try']    : self::MAX_TRY;
        self::$configs['max_depth']  = isset(self::$configs['max_depth'])  ? self::$configs['max_depth']  : 0;
        self::$configs['max_fields'] = isset(self::$configs['max_fields']) ? self::$configs['max_fields'] : 0;
        self::$configs['export']     = isset(self::$configs['export'])     ? self::$configs['export']     : array();

        // csv、sql、db
        self::$export_type  = isset(self::$configs['export']['type'])  ? self::$configs['export']['type']  : '';
        self::$export_file  = isset(self::$configs['export']['file'])  ? self::$configs['export']['file']  : '';
        self::$export_table = isset(self::$configs['export']['table']) ? self::$configs['export']['table'] : '';

        // 是否设置了并发任务数，并且大于1，而且不是windows环境
        if (isset(self::$configs['tasknum']) && self::$configs['tasknum'] > 1 && !util::is_win()) 
        {
            self::$tasknum = self::$configs['tasknum'];
        }
        // 是否设置了保留运行状态
        if (isset(self::$configs['save_running_state'])) 
        {
            self::$save_running_state = self::$configs['save_running_state'];
        }
        if (isset(self::$configs['clean_last_state'])) 
        {
            self::$clean_last_state = self::$configs['clean_last_state'];
        }

        // 不同项目的采集以采集名称作为前缀区分
        if (isset($GLOBALS['config']['redis']['prefix'])) 
        {
            $GLOBALS['config']['redis']['prefix'] = $GLOBALS['config']['redis']['prefix'].'-'.md5(self::$configs['name']);
        }
    }

    public function add_scan_url($url, $options = array())
    {
        if (!$this->is_scan_page($url))
        {
            log::error("Domain of scan_urls (\"{$url}\") does not match the domains of the domain name\n");
            exit;
        }

        // 投递状态
        $status = false;
        $link = array(
            'url'          => $url,            
            'url_type'     => 'scan_page', 
            'method'       => isset($options['method'])       ? $options['method']       : 'get',             
            'headers'      => isset($options['headers'])      ? $options['headers']      : array(),    
            'params'       => isset($options['params'])       ? $options['params']       : array(),           
            'context_data' => isset($options['context_data']) ? $options['context_data'] : '',                
            'proxy'        => isset($options['proxy'])        ? $options['proxy']        : self::$configs['proxy'],             
            'try_num'      => isset($options['try_num'])      ? $options['try_num']      : 0,                 
            'max_try'      => isset($options['max_try'])      ? $options['max_try']      : self::$configs['max_try'],
            'depth'        => 0,
        );
        $status = $this->queue_lpush($link);
        log::debug(date("H:i:s")." Find scan page: {$url}");
    }

    /**
     * 一般在 on_scan_page 和 on_list_page 回调函数中调用，用来往待爬队列中添加url
     * 两个进程同时调用这个方法，传递相同url的时候，就会出现url重复进入队列
     * 
     * @param mixed $url
     * @param mixed $options
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function add_url($url, $options = array(), $depth = 0)
    {
        // 投递状态
        $status = false;
        $link = array(
            'url'          => $url,            
            'url_type'     => '', 
            'method'       => isset($options['method'])       ? $options['method']       : 'get',             
            'headers'      => isset($options['headers'])      ? $options['headers']      : array(),    
            'params'       => isset($options['params'])       ? $options['params']       : array(),           
            'context_data' => isset($options['context_data']) ? $options['context_data'] : '',                
            'proxy'        => isset($options['proxy'])        ? $options['proxy']        : self::$configs['proxy'],             
            'try_num'      => isset($options['try_num'])      ? $options['try_num']      : 0,                 
            'max_try'      => isset($options['max_try'])      ? $options['max_try']      : self::$configs['max_try'],
            'depth'        => $depth,
        );

        if ($this->is_list_page($url) && !$this->is_collect_url($url))
        {
            log::debug(date("H:i:s")." Find list page: {$url}");
            $link['url_type'] = 'list_page';
            $status = $this->queue_lpush($link);
        }

        if ($this->is_content_page($url) && !$this->is_collect_url($url))
        {
            log::debug(date("H:i:s")." Find content page: {$url}");
            $link['url_type'] = 'content_page';
            $status = $this->queue_lpush($link);
        }

        return $status;
    }

    /**
     * 是否入口页面
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-12 19:06
     */
    public function is_scan_page($url)
    {
        $parse_url = parse_url($url);
        if (empty($parse_url['host']) || !in_array($parse_url['host'], self::$configs['domains'])) 
        {
            return false;
        }
        return true;
    }

    /**
     * 是否列表页面
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-12 19:06
     */
    public function is_list_page($url)
    {
        $result = false;
        if (!empty(self::$configs['list_url_regexes'])) 
        {
            foreach (self::$configs['list_url_regexes'] as $regex) 
            {
                if (preg_match("#{$regex}#i", $url))
                {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }
        
    /**
     * 是否内容页面
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-12 19:06
     */
    public function is_content_page($url)
    {
        $result = false;
        if (!empty(self::$configs['content_url_regexes'])) 
        {
            foreach (self::$configs['content_url_regexes'] as $regex) 
            {
                if (preg_match("#{$regex}#i", $url))
                {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    public function start()
    {
        $this->parse_command();

        // 爬虫开始时间
        self::$time_start = time();
        // 当前任务ID
        self::$taskid = 1;
        // 当前任务进程ID
        self::$taskpid = function_exists('posix_getpid') ? posix_getpid() : 1;
        // 当前任务是否主任务
        self::$taskmaster = true;
        self::$collect_succ = 0;
        self::$collect_fail = 0;

        //--------------------------------------------------------------------------------
        // 运行前验证
        //--------------------------------------------------------------------------------

        if (version_compare(PHP_VERSION, '5.3.0', 'lt')) 
        {
            log::error('PHP 5.3+ is required, currently installed version is: ' . phpversion());
            exit;
        }

        if(!function_exists('curl_init'))
        {
            log::error("The curl extension was not found");
            exit;
        }

        // 多任务需要pcntl扩展支持
        if (self::$tasknum > 1) 
        {
            if(!function_exists('pcntl_fork'))
            {
                log::error("Multitasking needs pnctl, the pnctl extension was not found");
                exit;
            }
        }

        if (self::$tasknum > 1 || self::$save_running_state) 
        {
            if (!extension_loaded("redis"))
            {
                log::error("Spider kept running state or multitasking needs Redis support, the redis extension was not found");
                exit;
            }
        }

        // 保存运行状态需要Redis支持
        if (self::$save_running_state && !cls_redis::init()) 
        {
            log::error("Spider kept running state needs Redis support，Error: ".cls_redis::$error."\n\nPlease check the configuration file config/inc_config.php\n");
            exit;
        }

        // 多任务需要Redis支持
        if(self::$tasknum > 1 && !cls_redis::init())
        {
            log::error("Multitasking needs Redis support，Error: ".cls_redis::$error."\n\nPlease check the configuration file config/inc_config.php\n");
            exit;
        }

        // 验证导出
        $this->export_auth();

        // 检查 scan_urls 
        if (empty(self::$configs['scan_urls'])) 
        {
            log::error("No scan url to start\n");
            exit;
        }
        
        // 放这个位置，可以添加入口页面
        if ($this->on_start) 
        {
            call_user_func($this->on_start, $this);
        }

        foreach ( self::$configs['scan_urls'] as $url ) 
        {
            if (!$this->is_scan_page($url))
            {
                log::error("Domain of scan_urls (\"{$url}\") does not match the domains of the domain name\n");
                exit;
            }
        }

        // windows 下没法显示面板，强制显示日志
        if (util::is_win()) 
        {
            self::$configs['name'] = mb_convert_encoding(self::$configs['name'], "gbk", "utf-8");
            log::$log_show = true;
        }
        else 
        {
            log::$log_show = isset(self::$configs['log_show']) ? self::$configs['log_show'] : false;
        }

        if (log::$log_show)
        {
            log::info("\n[ ".self::$configs['name']." Spider ] is started...\n");
            log::warn("PHPSpider Version: ".self::VERSION."\n");
            log::warn("Task Number: ".self::$tasknum."\n");
            log::warn("!Documentation: \nhttps://doc.phpspider.org\n");
        }

        // 多任务和分布式都要清掉，当然分布式只清自己的
        $this->del_task_status();

        //--------------------------------------------------------------------------------
        // 生成多任务
        //--------------------------------------------------------------------------------
        if(self::$tasknum > 1)
        {
            // 不保留运行状态
            if (!self::$save_running_state) 
            {
                // 清空redis里面的数据
                $this->cache_clear();
            }
        }

        // 添加入口URL到队列
        foreach ( self::$configs['scan_urls'] as $url ) 
        {
            $this->add_scan_url($url);
        }

        while( $this->queue_lsize() )
        { 
            // 抓取页面
            $this->collect_page();

            // 多任务下主任务未准备就绪
            if (self::$tasknum > 1 && !self::$taskmaster_status) 
            {
                // 主进程采集到两倍于任务数时，生成子任务一起采集
                if ($this->queue_lsize() > self::$tasknum*2) 
                {
                    // 主任务状态
                    self::$taskmaster_status = true;
                    
                    // fork 子进程前一定要先干掉redis连接fd，不然会存在进程互抢redis fd 问题
                    cls_redis::close();
                    // task进程从2开始，1被master进程所使用
                    for ($i = 2; $i <= self::$tasknum; $i++) 
                    {
                        $this->fork_one_task($i);
                    }
                }
            }

            $this->set_task_status();

            // 每采集成功一次页面，就刷新一次面板
            if (!log::$log_show) 
            {
                $this->display_ui();
            }
        } 

        // 显示最后结果
        log::$log_show = true;

        $spider_time_run = util::time2second(intval(microtime(true) - self::$time_start));
        log::info("Spider finished in {$spider_time_run}\n");

        $count_collected_url = $this->count_collected_url();
        log::info("Total pages: {$count_collected_url} \n\n");

        // 最后:多任务下不保留运行状态，清空redis数据
        // 注意:ctrl+c 就跑不到这里来了，做守护进程的时候弄吧
        if (self::$tasknum > 1 && !self::$save_running_state) 
        {
            $this->cache_clear();
        }
    }

    /**
     * 创建一个子进程
     * @param Worker $worker
     * @throws Exception
     */
    public function fork_one_task($taskid)
    {
        $pid = pcntl_fork();

        // 主进程记录子进程pid
        if($pid > 0)
        {
            // 暂时没用
            //self::$taskpids[$taskid] = $pid;
        }
        // 子进程运行
        elseif(0 === $pid)
        {
            log::warn("Fork children task({$taskid}) successful...\n");

            self::$time_start = microtime(true);
            self::$taskid = $taskid;
            self::$taskpid = posix_getpid();
            self::$taskmaster = false;
            self::$collect_succ = 0;
            self::$collect_fail = 0;

            while( $this->queue_lsize() )
            { 
                // 如果队列中的网页比任务数2倍多，子任务可以采集，否则等待...
                if ($this->queue_lsize() > self::$tasknum*2) 
                {
                    // 抓取页面
                    $this->collect_page();
                }
                else 
                {
                    log::warn("Task(".self::$taskid.") waiting...\n");
                    sleep(1);
                }

                $this->set_task_status();
            } 

            // 这里用0表示正常退出
            exit(0);
        }
        else
        {
            log::error("Fork children task({$i}) fail...\n");
            exit;
        }
    }

    /**
     * 验证导出
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-02 23:37
     */
    public function export_auth()
    {
        // 如果设置了导出选项
        if (!empty(self::$configs['export'])) 
        {
            if (self::$export_type == 'csv') 
            {
                if (empty(self::$export_file)) 
                {
                    log::error("Export data into CSV files need to Set the file path.");
                    exit;
                }
            }
            elseif (self::$export_type == 'sql') 
            {
                if (empty(self::$export_file)) 
                {
                    log::error("Export data into SQL files need to Set the file path.");
                    exit;
                }
            }
            elseif (self::$export_type == 'db') 
            {
                if (!function_exists('mysqli_connect'))
                {
                    log::error("Export data to a database need Mysql support，Error: Unable to load mysqli extension.\n");
                    exit;
                }

                if (empty($GLOBALS['config']['db'])) 
                {
                    log::error("Export data to a database need Mysql support，Error: You not set a config array for connect.\n\nPlease check the configuration file config/inc_config.php");
                    exit;
                }

                $config = $GLOBALS['config']['db'];
                @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
                if(mysqli_connect_errno())
                {
                    log::error("Export data to a database need Mysql support，Error: ".mysqli_connect_error()." \n\nPlease check the configuration file config/inc_config.php");
                    exit;
                }

                if (!db::table_exists(self::$export_table))
                {
                    log::error("Table ".self::$export_table." does not exist\n");
                    exit;
                }
            }
        }
    }

    /**
     * 爬取页面
     * 
     * @param mixed $collect_url    要抓取的链接
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function collect_page() 
    {
        $count_collect_url = $this->count_collect_url();
        log::info(date("H:i:s")." Find pages: {$count_collect_url} \n");

        $queue_lsize = $this->queue_lsize();
        log::info(date("H:i:s")." Waiting for collect pages: {$queue_lsize} \n");

        $count_collected_url = $this->count_collected_url();
        log::info(date("H:i:s")." Collected pages: {$count_collected_url} \n");

        // 先进先出
        $link = $this->queue_rpop();
        $url = $link['url'];

        // 标记为已爬取网页
        $this->set_collected_url($url);

        // 爬取页面开始时间
        $page_time_start = microtime(true);

        $html = $this->request_url($url, $link);

        if (!$html) 
        {
            return false;
        }
        // 当前正在爬取的网页页面的对象
        $page = array(
            'url'     => $url,
            'raw'     => $html,
            'request' => array(
                'url'          => $url,
                'method'       => $link['method'],
                'headers'      => $link['headers'],
                'params'       => $link['params'],
                'context_data' => $link['context_data'],
                'try_num'      => $link['try_num'],
                'max_try'      => $link['max_try'],
                'depth'        => $link['depth'],
            ),
        );
        unset($html);

        //--------------------------------------------------------------------------------
        // 处理回调函数
        //--------------------------------------------------------------------------------

        // 判断当前网页是否被反爬虫了, 需要开发者实现 
        if ($this->is_anti_spider) 
        {
            $is_anti_spider = call_user_func($this->is_anti_spider, $url, $page['raw'], $this);
            // 如果在回调函数里面判断被反爬虫并且返回true
            if ($is_anti_spider) 
            {
                return false;
            }
        }

        // 在一个网页下载完成之后调用. 主要用来对下载的网页进行处理.
        // 比如下载了某个网页，希望向网页的body中添加html标签
        if ($this->on_download_page) 
        {
            $return = call_user_func($this->on_download_page, $page, $this);
            // 针对那些老是忘记return的人
            if (isset($return)) $page = $return;
        }

        // 是否从当前页面分析提取URL
        // 回调函数如果返回false表示不需要再从此网页中发现待爬url
        $is_find_url = true;
        if ($link['url_type'] == 'scan_page') 
        {
            if ($this->on_scan_page) 
            {
                $return = call_user_func($this->on_scan_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        }
        elseif ($link['url_type'] == 'list_page') 
        {
            if ($this->on_list_page) 
            {
                $return = call_user_func($this->on_list_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        }
        elseif ($link['url_type'] == 'content_page') 
        {
            if ($this->on_content_page) 
            {
                $return = call_user_func($this->on_content_page, $page, $page['raw'], $this);
                if (isset($return)) $is_find_url = $return;
            }
        }

        // on_scan_page、on_list_page、on_content_page 返回false表示不需要再从此网页中发现待爬url
        if ($is_find_url) 
        {
            // 如果深度没有超过最大深度，获取下一级URL
            if (self::$configs['max_depth'] == 0 || $link['depth'] < self::$configs['max_depth']) 
            {
                // 分析提取HTML页面中的URL，需要优化 XXX
                $this->get_html_urls($page['raw'], $url, $link['depth'] + 1);
            }
        }

        // 如果是内容页，分析提取HTML页面中的字段
        // 列表页也可以提取数据的，source_type: urlcontext，未实现
        if ($link['url_type'] == 'content_page') 
        {
            $this->get_html_fields($page['raw'], $url, $page);
        }

        // 如果当前深度大于缓存的，更新缓存
        $this->incr_depth_num($link['depth']);

        // 多任务的时候输出爬虫序号
        if (self::$tasknum > 1) 
        {
            log::info("Current task id: ".self::$taskid."\n");
        }

        // 处理页面耗时时间
        $time_run = round(microtime(true) - $page_time_start, 3);
        log::debug(date("H:i:s")." Success process page {$url} in {$time_run} s\n");

        $spider_time_run = util::time2second(intval(microtime(true) - self::$time_start));
        log::info(date("H:i:s")." Spider running in {$spider_time_run}\n");

        // 爬虫爬取每个网页的时间间隔，单位：毫秒
        if (!isset(self::$configs['interval'])) 
        {
            // 默认睡眠100毫秒，太快了会被认为是ddos
            self::$configs['interval'] = 100;
        }
        usleep(self::$configs['interval'] * 1000);
    }

    /**
     * 下载网页，得到网页内容
     * 
     * @param mixed $url
     * @param mixed $options
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function request_url($url, $options = array())
    {
        $time_start = microtime(true);

        //$url = "http://www.qiushibaike.com/article/117568316";

        $link = array(
            'url'          => $url,
            'url_type'     => isset($options['url_type'])     ? $options['url_type']     : '',             
            'method'       => isset($options['method'])       ? $options['method']       : 'get',             
            'headers'      => isset($options['headers'])      ? $options['headers']      : array(),    
            'params'       => isset($options['params'])       ? $options['params']       : array(),           
            'context_data' => isset($options['context_data']) ? $options['context_data'] : '',                
            'proxy'        => isset($options['proxy'])        ? $options['proxy']        : self::$configs['proxy'],             
            'try_num'      => isset($options['try_num'])      ? $options['try_num']      : 0,                 
            'max_try'      => isset($options['max_try'])      ? $options['max_try']      : self::$configs['max_try'],
            'depth'        => isset($options['depth'])        ? $options['depth']        : 0,             
        );

        // 设置了编码就不要让requests去判断了
        if (isset(self::$configs['input_encoding'])) 
        {
            requests::$input_encoding = self::$configs['input_encoding'];
        }
        // 得到的编码如果不是utf-8的要转成utf-8，因为xpath只支持utf-8
        requests::$output_encoding = 'utf-8';
        requests::set_timeout(self::$configs['timeout']);
        requests::set_useragent(self::$configs['user_agent']);
        
        // 是否设置了代理
        if (!empty($link['proxy'])) 
        {
            requests::set_proxies(array('http'=>$link['proxy'], 'https'=>$link['proxy']));
            // 自动切换IP
            requests::add_header('Proxy-Switch-Ip', 'yes');
        }

        // 如何设置了 HTTP Headers
        if (!empty($link['headers'])) 
        {
            foreach ($link['headers'] as $k=>$v) 
            {
                requests::add_header($k, $v);
            }
        }

        $method = strtolower($link['method']);
        $html = requests::$method($url, $link['params']);
        // 此url附加的数据不为空, 比如内容页需要列表页一些数据，拼接到后面去
        if ($html && !empty($link['context_data'])) 
        {
            $html .= $link['context_data'];
        }
        //var_dump($html);exit;

        $http_code = requests::$status_code;

        if ($this->on_status_code) 
        {
            $return = call_user_func($this->on_status_code, $http_code, $url, $html, $this);
            if (isset($return)) 
            {
                $html = $return;
            }
            if (!$html) 
            {
                return false;
            }
        }

        if ($http_code != 200)
        {
            // 如果是301、302跳转，抓取跳转后的网页内容
            if ($http_code == 301 || $http_code == 302) 
            {
                $info = requests::$info;
                if (isset($info['redirect_url'])) 
                {
                    $url = $info['redirect_url'];
                    requests::$input_encoding = null;
                    $html = $this->request_url($url, $options);
                    if ($html && !empty($link['context_data'])) 
                    {
                        $html .= $link['context_data'];
                    }
                }
                else 
                {
                    return false;
                }
            }
            else 
            {
                if ($http_code == 407) 
                {
                    // 扔到队列头部去，继续采集
                    $this->queue_rpush($link);
                    log::error(date("H:i:s")." Failed to download page {$url}\n");
                }
                elseif (in_array($http_code, array('0','502','503','429'))) 
                {
                    // 采集次数加一
                    $link['try_num']++;
                    // 抓取次数 小于 允许抓取失败次数
                    if ( $link['try_num'] <= $link['max_try'] ) 
                    {
                        // 扔到队列头部去，继续采集
                        $this->queue_rpush($link);
                    }
                    log::error(date("H:i:s")." Failed to download page {$url}, retry({$link['try_num']})\n");
                }
                else 
                {
                    log::error(date("H:i:s")." Failed to download page {$url}\n");
                }
                log::error(date("H:i:s")." HTTP CODE: {$http_code}\n");
                self::$collect_fail++;
                return false;
            }
        }

        // 爬取页面耗时时间
        $time_run = round(microtime(true) - $time_start, 3);
        log::debug(date("H:i:s")." Success download page {$url} in {$time_run} s\n");
        self::$collect_succ++;

        return $html;
    }

    /**
     * 分析提取HTML页面中的URL
     * 
     * @param mixed $html           HTML内容
     * @param mixed $collect_url    抓取的URL，用来拼凑完整页面的URL
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_html_urls($html, $collect_url, $depth = 0) 
    { 
        //--------------------------------------------------------------------------------
        // 正则匹配出页面中的URL
        //--------------------------------------------------------------------------------
        preg_match_all('/<a .*?href="(.*?)".*?>/is', $html, $matchs); 
        $urls = !empty($matchs[1]) ? $matchs[1] : array();

        //--------------------------------------------------------------------------------
        // 过滤和拼凑URL
        //--------------------------------------------------------------------------------
        // 去除重复的RUL
        $urls = array_unique($urls);
        foreach ($urls as $k=>$url) 
        {
            $url = trim($url);
            if (empty($url)) 
            {
                continue;
            }

            // 排除JavaScript的连接
            if (strpos($url, "javascript:") !== false) 
            {
                unset($urls[$k]);
                continue;
            }

            $val = $this->fill_url($url, $collect_url);
            if ($val) 
            {
                $urls[$k] = $val;
            }
            else 
            {
                unset($urls[$k]);
            }
        }

        if (empty($urls)) 
        {
            return false;
        }

        //--------------------------------------------------------------------------------
        // 把抓取到的URL放入队列
        //--------------------------------------------------------------------------------
        foreach ($urls as $url) 
        {
            // 把当前页当做找到的url的Referer页
            $options = array(
                'headers' => array(
                    'Referer' => $collect_url,
                )
            );
            $this->add_url($url, $options, $depth);
        }
    }

    /**
     * 清空Redis里面上次爬取的采集数据
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-29 13:00
     */
    public function cache_clear()
    {
        // 删除队列
        cls_redis::del("collect_queue");

        // 删除采集到的field数量
        cls_redis::del("fields_num");
        cls_redis::del("depth_num");

        // 抓取和抓取到数量
        cls_redis::del("collect_urls_num");
        cls_redis::del("collected_urls_num");

        // 删除等待采集网页缓存
        $keys = cls_redis::keys("collect_urls-*"); 
        foreach ($keys as $key) 
        {
            $key = str_replace($GLOBALS['config']['redis']['prefix'].":", "", $key);
            cls_redis::del($key);
        }

        // 删除已经采集网页缓存
        $keys = cls_redis::keys("collected_urls-*"); 
        foreach ($keys as $key) 
        {
            $key = str_replace($GLOBALS['config']['redis']['prefix'].":", "", $key);
            cls_redis::del($key);
        }
    }

    public function set_task_status()
    {
        // 每采集成功一个页面，生成当前进程状态到文件，供主进程使用
        $mem = round(memory_get_usage(true)/(1024*1024),2)."MB";
        $use_time = microtime(true) - self::$time_start; 
        $speed = round((self::$collect_succ + self::$collect_fail) / $use_time, 2)."/s";
        $status = array(
            'id' => self::$taskid,
            'pid' => self::$taskpid,
            'mem' => $mem,
            'collect_succ' => self::$collect_succ,
            'collect_fail' => self::$collect_fail,
            'speed' => $speed,
        );
        $task_status = json_encode($status);

        if (self::$tasknum > 1)
        {
            cls_redis::set("task_status-".self::$taskid, $task_status); 
        }
        else 
        {
            self::$task_status = array($task_status);
        }
    }

    public function get_task_status()
    {
        $task_status = array();
        if (self::$tasknum > 1)
        {
            for ($i = 1; $i <= self::$tasknum; $i++) 
            {
                $key = "task_status-".$i;
                $task_status[] = cls_redis::get($key);
            }
            // redis的keys太慢了
            //$keys = cls_redis::keys("task_status-*"); 
            //foreach ($keys as $key) 
            //{
                //$key = str_replace($GLOBALS['config']['redis']['prefix'].":", "", $key);
                //$task_status[] = cls_redis::get($key);
            //}
        }
        else 
        {
            $task_status = self::$task_status;
        }
        return $task_status;
    }

    public function del_task_status()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            cls_redis::del("lock-depth_num");

            for ($i = 1; $i <= self::$tasknum; $i++) 
            {
                $key = "task_status-".$i;
                cls_redis::del($key);
            }
            // redis的keys太慢了
            //$keys = cls_redis::keys("task_status-*"); 
            //foreach ($keys as $key) 
            //{
                //$key = str_replace($GLOBALS['config']['redis']['prefix'].":", "", $key);
                //cls_redis::del($key);
            //}
        }
    }

    /**
     * 是否待爬取网页
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function is_collect_url($url)
    {
        // 多任务 或者 单任务但是从上次继续执行
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $lock = "lock-collect_urls-".md5($url);
            // 如果不能上锁，说明同时有一个进程带了一样的URL进来判断，而且刚好比这个进程快一丢丢
            // 那么这个进程的URL就可以直接过滤了
            if (!cls_redis::setnx($lock, "lock"))
            {
                return true;
            }
            else 
            {
                // 删除锁然后判断一下这个连接是不是已经在队列里面了
                $exists = cls_redis::exists("collect_urls-".md5($url)); 
                cls_redis::del($lock);
                return $exists;
            }
        }
        else 
        {
            return array_key_exists(md5($url), self::$collect_urls);
        }
    }

    /**
     * 添加发现网页标记
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function set_collect_url($url)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            cls_redis::incr("collect_urls_num"); 
            cls_redis::set("collect_urls-".md5($url), time()); 
        }
        else 
        {
            self::$collect_urls_num++;
            self::$collect_urls[md5($url)] = time();
        }
    }

    /**
     * 删除发现网页标记
     * 暂时没用到
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function del_collect_url($url)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            cls_redis::decr("collect_urls_num"); 
            cls_redis::del("collect_urls-".md5($url)); 
        }
        else 
        {
            self::$collect_urls_num--;
            unset(self::$collect_urls[md5($url)]);
        }
    }

    /**
     * 发现爬取网页数量
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function count_collect_url()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $count = cls_redis::get("collect_urls_num"); 
            //$keys = cls_redis::keys("collect_urls-*"); 
            //$count = count($keys);
        }
        else 
        {
            $count = self::$collect_urls_num;
            //$count = count(self::$collect_urls);
        }
        return $count;
    }

    /**
     * 等待爬取网页数量
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function count_collected_url()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $count = cls_redis::get("collected_urls_num"); 
            //$keys = cls_redis::keys("collected_urls-*"); 
            //$count = count($keys);
        }
        else 
        {
            $count = self::$collected_urls_num;
            //$count = count(self::$collected_urls);
        }
        return $count;
    }

    /**
     * 是否已爬取网页
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function is_collected_url($url)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            return cls_redis::exists("collected_urls-".md5($url)); 
        }
        else 
        {
            return array_key_exists(md5($url), self::$collected_urls);
        }
    }

    /**
     * 添加已爬取网页标记
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function set_collected_url($url)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            cls_redis::incr("collected_urls_num"); 
            cls_redis::set("collected_urls-".md5($url), time()); 
        }
        else 
        {
            self::$collected_urls_num++;
            self::$collected_urls[md5($url)] = time();
        }
    }

    /**
     * 删除已爬取网页标记
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function del_collected_url($url)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            cls_redis::decr("collected_urls_num"); 
            cls_redis::del("collected_urls-".md5($url)); 
        }
        else 
        {
            self::$collected_urls_num--;
            unset(self::$collected_urls[md5($url)]);
        }
    }

    /**
     * 从队列左边插入
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_lpush($link = array())
    {
        if (empty($link) || empty($link['url'])) 
        {
            return false;
        }

        $url = $link['url'];
        // 先标记为待爬取网页，再入爬取队列
        $this->set_collect_url($url);

        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $link = json_encode($link);
            cls_redis::lpush("collect_queue", $link); 
        }
        else 
        {
            array_push(self::$collect_queue, $link);
        }
        return true;
    }

    /**
     * 从队列右边插入
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_rpush($link = array())
    {
        if (empty($link) || empty($link['url'])) 
        {
            return false;
        }

        $url = $link['url'];
        // 先标记为待爬取网页，再入爬取队列
        $this->set_collect_url($url);

        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $link = json_encode($link);
            cls_redis::rpush("collect_queue", $link); 
        }
        else 
        {
            array_unshift(self::$collect_queue, $link);
        }
        return true;
    }

    /**
     * 从队列左边取出
     * 后进先出
     * 可以避免采集内容页有分页的时候采集失败数据拼凑不全
     * 还可以按顺序采集列表页
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_lpop()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $link = cls_redis::lpop("collect_queue"); 
            $link = json_decode($link, true);
        }
        else 
        {
            $link = array_pop(self::$collect_queue); 
        }
        return $link;
    }

    /**
     * 从队列右边取出
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_rpop()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $link = cls_redis::rpop("collect_queue"); 
            $link = json_decode($link, true);
        }
        else 
        {
            $link = array_shift(self::$collect_queue); 
        }
        return $link;
    }

    /**
     * 队列长度
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_lsize()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $lsize = cls_redis::lsize("collect_queue"); 
        }
        else 
        {
            $lsize = count(self::$collect_queue);
        }
        return $lsize;
    }

    /**
     * 采集深度加一
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function incr_depth_num($depth)
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $lock = "lock-depth_num";
            // 一个一个任务执行
            while (cls_redis::setnx($lock, "lock"))
            {
                if (cls_redis::get("depth_num") < $depth) 
                {
                    cls_redis::set("depth_num", $depth); 
                }
                cls_redis::del($lock);
                break;
            }
        }
        else 
        {
            if (self::$depth_num < $depth) 
            {
                self::$depth_num = $depth;
            }
        }
    }

    /**
     * 获得采集深度
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function get_depth_num()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            return cls_redis::get("depth_num"); 
        }
        else 
        {
            return self::$depth_num;
        }
    }

    /**
     * 提取到的field数目加一
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function incr_fields_num()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $fields_num = cls_redis::incr("fields_num"); 
        }
        else 
        {
            self::$fields_num++;
            $fields_num = self::$fields_num;
        }
        return $fields_num;
    }

    /**
     * 提取到的field数目
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function get_fields_num()
    {
        if (self::$tasknum > 1 || self::$save_running_state)
        {
            $fields_num = cls_redis::get("fields_num"); 
        }
        else 
        {
            $fields_num = self::$fields_num;
        }
        return $fields_num;
    }

    /**
     * 获得完整的连接地址
     * 
     * @param mixed $url            要检查的URL
     * @param mixed $collect_url    从那个URL页面得到上面的URL
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function fill_url($url, $collect_url)
    {
        $url = trim($url);
        $collect_url = trim($collect_url);

        $parse_url = @parse_url($collect_url);
        if (empty($parse_url['scheme']) || empty($parse_url['host'])) 
        {
            return false;
        }
        $scheme = $parse_url['scheme'];
        $domain = $parse_url['host'];
        $path = empty($parse_url['path']) ? '' : $parse_url['path'];
        $base_url_path = $domain.$path;
        $base_url_path = preg_replace("/\/([^\/]*)\.(.*)$/","/",$base_url_path);
        $base_url_path = preg_replace("/\/$/",'',$base_url_path);

        $i = $path_step = 0;
        $dstr = $pstr = '';
        $pos = strpos($url,'#');
        // href="#;" 这样的连接也有，日
        if($pos === 0)
        {
            return false;
        }
        elseif($pos > 0)
        {
            // 去掉 #
            $url = substr($url, 0, $pos);
        }

        // 京东变态的都是 //www.jd.com/111.html
        if(substr($url, 0, 2) == '//')
        {
            $url = str_replace("//", "", $url);
        }
        // /1234.html
        elseif($url[0] == '/')
        {
            $url = $domain.$url;
        }
        // ./1234.html、../1234.html 这种类型的
        elseif($url[0] == '.')
        {
            if(!isset($url[2]))
            {
                return false;
            }
            else
            {
                $urls = explode('/',$url);
                foreach($urls as $u)
                {
                    if( $u == '..' )
                    {
                        $path_step++;
                    }
                    // 遇到 .，不知道为什么不直接写$u == '.'，貌似一样的
                    else if( $i < count($urls)-1 )
                    {
                        //$dstr .= $urls[$i].'/';
                    }
                    else
                    {
                        $dstr .= $urls[$i];
                    }
                    $i++;
                }
                $urls = explode('/',$base_url_path);
                if(count($urls) <= $path_step)
                {
                    return false;
                }
                else
                {
                    $pstr = '';
                    for($i=0;$i<count($urls)-$path_step;$i++){ $pstr .= $urls[$i].'/'; }
                    $url = $pstr.$dstr;
                }
            }
        }
        else 
        {
            if( strtolower(substr($url, 0, 7))=='http://' )
            {
                $url = preg_replace('#^http://#i','',$url);
                $scheme = "http";
            }
            else if( strtolower(substr($url, 0, 8))=='https://' )
            {
                $url = preg_replace('#^https://#i','',$url);
                $scheme = "https";
            }
            else
            {
                $url = $base_url_path.'/'.$url;
            }
        }
        $url = $scheme.'://'.$url;

        $parse_url = @parse_url($url);
        $domain = empty($parse_url['host']) ? $domain : $parse_url['host'];
        // 如果host不为空，判断是不是要爬取的域名
        if (!empty($parse_url['host'])) 
        {
            //排除非域名下的url以提高爬取速度
            if (!in_array($parse_url['host'], self::$configs['domains'])) 
            {
                return false;
            }
        }

        return $url;
    }

    /**
     * 分析提取HTML页面中的字段
     * 
     * @param mixed $html
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_html_fields($html, $url, $page) 
    {
        $fields = $this->get_fields(self::$configs['fields'], $html, $url, $page);

        if (!empty($fields)) 
        {
            if ($this->on_extract_page) 
            {
                $return_data = call_user_func($this->on_extract_page, $page, $fields);
                if (!isset($return_data))
                {
                    log::warn("on_extract_page function return value can't be empty\n");
                }
                elseif (!is_array($return_data))
                {
                    log::warn("on_extract_page function return value must be an array\n");
                }
                else 
                {
                    $fields = $return_data;
                }
            }

            if (isset($fields) && is_array($fields)) 
            {
                $fields_num = $this->incr_fields_num();
                if (self::$configs['max_fields'] != 0 && $fields_num > self::$configs['max_fields']) 
                {
                    exit(0);
                }

                $fields_str = json_encode($fields, JSON_UNESCAPED_UNICODE);
                if (util::is_win()) 
                {
                    $fields_str = mb_convert_encoding($fields_str, 'gb2312', 'utf-8');
                }
                log::info(date("H:i:s")." Result[{$fields_num}]: ".$fields_str."\n");

                // 如果设置了导出选项
                if (!empty(self::$configs['export'])) 
                {
                    self::$export_type = isset(self::$configs['export']['type']) ? self::$configs['export']['type'] : '';
                    if (self::$export_type == 'csv') 
                    {
                        util::put_file(self::$export_file, util::format_csv($fields)."\n", FILE_APPEND);
                    }
                    elseif (self::$export_type == 'sql') 
                    {
                        $sql = db::insert(self::$export_table, $fields, true);
                        util::put_file(self::$export_file, $sql.";\n", FILE_APPEND);
                    }
                    elseif (self::$export_type == 'db') 
                    {
                        db::insert(self::$export_table, $fields);
                    }
                }
            }

        }
    }

    /**
     * 根据配置提取HTML代码块中的字段
     * 
     * @param mixed $confs
     * @param mixed $html
     * @param mixed $page
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function get_fields($confs, $html, $url, $page) 
    {
        $fields = array();
        foreach ($confs as $conf) 
        {
            // 当前field抽取到的内容是否是有多项
            $repeated = isset($conf['repeated']) && $conf['repeated'] ? true : false;
            // 当前field抽取到的内容是否必须有值
            $required = isset($conf['required']) && $conf['required'] ? true : false;

            if (empty($conf['name'])) 
            {
                log::error("The field name is null, please check your \"fields\" and add the name of the field\n");
                exit;
            }

            $values = array();
            // 如果定义抽取规则
            if (!empty($conf['selector'])) 
            {
                // 如果这个field是上一个field的附带连接
                if (isset($conf['source_type']) && $conf['source_type']=='attached_url') 
                {
                    // 取出上个field的内容作为连接，内容分页是不进队列直接下载网页的
                    if (!empty($fields[$conf['attached_url']])) 
                    {
                        $collect_url = $this->fill_url($url, $fields[$conf['attached_url']]);
                        log::debug(date("H:i:s")." Find attached content page: {$url}");
                        requests::$input_encoding = null;
                        $html = $this->request_url($collect_url);
                        // 在一个attached_url对应的网页下载完成之后调用. 主要用来对下载的网页进行处理.
                        if ($this->on_attached_download_page) 
                        {
                            $return = call_user_func($this->on_attached_download_page, $html, $this);
                            if (isset($return)) 
                            {
                                $html = $return;
                            }
                        }

                        // 请求获取完分页数据后把连接删除了 
                        unset($fields[$conf['attached_url']]);
                    }
                }

                // 没有设置抽取规则的类型 或者 设置为 xpath
                if (!isset($conf['selector_type']) || $conf['selector_type']=='xpath') 
                {
                    // 返回值一定是多项的
                    $values = $this->get_fields_xpath($html, $conf['selector'], $conf['name']);
                }
                elseif ($conf['selector_type']=='regex') 
                {
                    $values = $this->get_fields_regex($html, $conf['selector'], $conf['name']);
                }

                // field不为空而且存在子配置
                if (!empty($values) && !empty($conf['children'])) 
                {
                    $child_values = array();
                    // 父项抽取到的html作为子项的提取内容
                    foreach ($values as $html) 
                    {
                        // 递归调用本方法，所以多少子项目都支持
                        $child_value = $this->get_fields($conf['children'], $url, $html, $page);
                        if (!empty($child_value)) 
                        {
                            $child_values[] = $child_value;
                        }
                    }
                    // 有子项就存子项的数组，没有就存HTML代码块
                    if (!empty($child_values)) 
                    {
                        $values = $child_values;
                    }
                }
            }

            if (empty($values)) 
            {
                // 如果值为空而且值设置为必须项，跳出foreach循环
                if ($required) 
                {
                    // 清空整个 fields
                    $fields = array();
                    break;
                }
                // 避免内容分页时attached_url拼接时候string + array了
                $fields[$conf['name']] = '';
                //$fields[$conf['name']] = array();
            }
            else 
            {
                // 不重复抽取则只取第一个元素
                $fields[$conf['name']] = $repeated ? $values : $values[0];
            }
        }

        if (!empty($fields)) 
        {
            foreach ($fields as $fieldname => $data) 
            {
                $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.jpeg|\.png]))[\'|\"].*?[\/]?>/i"; 
                // 在抽取到field内容之后调用, 对其中包含的img标签进行回调处理
                if ($this->on_handle_img && preg_match($pattern, $data)) 
                {
                    $return = call_user_func($this->on_handle_img, $fieldname, $data);
                    if (!isset($return))
                    {
                        log::warn("on_handle_img function return value can't be empty\n");
                    }
                    else 
                    {
                        // 有数据才会执行 on_handle_img 方法，所以这里不要被替换没了
                        $data = $return;
                    }
                }

                // 当一个field的内容被抽取到后进行的回调, 在此回调中可以对网页中抽取的内容作进一步处理
                if ($this->on_extract_field) 
                {
                    $return = call_user_func($this->on_extract_field, $fieldname, $data, $page, self::$taskid);
                    if (!isset($return))
                    {
                        log::warn("on_extract_field function return value can't be empty\n");
                    }
                    else 
                    {
                        // 有数据才会执行 on_extract_field 方法，所以这里不要被替换没了
                        $fields[$fieldname] = $return;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * 转换数组值的编码格式
     * @param  array $arr           
     * @param  string $toEncoding   
     * @param  string $fromEncoding 
     * @return array                
     */
    private function _array_convert_encoding($arr, $to_encoding, $from_encoding)
    {
        eval('$arr = '.iconv($from_encoding, $to_encoding.'//IGNORE', var_export($arr,TRUE)).';');
        return $arr;
    }

    /**
     * 采用xpath分析提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_xpath($html, $selector, $fieldname) 
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        //libxml_use_internal_errors(true);
        //$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        //$errors = libxml_get_errors();
        //if (!empty($errors)) 
        //{
            //print_r($errors);
            //exit;
        //}

        $xpath = new DOMXpath($dom);
        $elements = @$xpath->query($selector);
        if ($elements === false)
        {
            log::error("Field(\"{$fieldname}\") the selector in the xpath(\"{$selector}\") syntax errors\n");
            exit;
        }

        $array = array();
        if (!is_null($elements)) 
        {
            foreach ($elements as $element) 
            {
                $nodeName = $element->nodeName;
                $nodeType = $element->nodeType;     // 1.Element 2.Attribute 3.Text
                //$nodeAttr = $element->getAttribute('src');
                //$nodes = util::node_to_array($dom, $element);
                //echo $nodes['@src']."\n";
                // 如果是img标签，直接取src值
                if ($nodeType == 1 && in_array($nodeName, array('img'))) 
                {
                    $content = $element->getAttribute('src');
                }
                // 如果是标签属性，直接取节点值
                elseif ($nodeType == 2 || $nodeType == 3) 
                {
                    $content = $element->nodeValue;
                }
                else 
                {
                    // 保留nodeValue里的html符号，给children二次提取
                    $content = $dom->saveXml($element);
                    //$content = trim($dom->saveHtml($element));
                    $content = preg_replace(array("#^<{$nodeName}.*>#isU","#</{$nodeName}>$#isU"), array('', ''), $content);
                }
                $array[] = trim($content);
            }
        }
        return $array;
    }

    /**
     * 采用正则分析提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_regex($html, $selector, $fieldname) 
    {
        if(@preg_match_all($selector, $html, $out) === false)
        {
            log::error("Field(\"{$fieldname}\") the selector in the regex(\"{$selector}\") syntax errors\n");
            exit;
        }

        $array = array();
        if (!is_null($out[1])) 
        {
            foreach ($out[1] as $v) 
            {
                $array[] = trim($v);
            }
        }
        return $array;
    }

    /**
     * 采用CSS选择器提取字段
     * 
     * @param mixed $html
     * @param mixed $selector
     * @param mixed $fieldname
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_fields_css($html, $selector, $fieldname) 
    {
    }

    public function shell_clear()
    {
        array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));
    }

    /**
     * 展示启动界面，Windows 不会到这里来
     * @return void
     */
    public function display_ui()
    {
        $loadavg = sys_getloadavg();
        foreach ($loadavg as $k=>$v) 
        {
            $loadavg[$k] = round($v, 2);
        }
        $display_str = "\033[1A\n\033[K-----------------------------\033[47;30m PHPSPIDER \033[0m-----------------------------\n\033[0m";
        $display_str .= 'PHPSpider version:' . self::VERSION . "          PHP version:" . PHP_VERSION . "\n";
        $display_str .= 'start time:'. date('Y-m-d H:i:s', self::$time_start).'   run ' . floor((time()-self::$time_start)/(24*60*60)). ' days ' . floor(((time()-self::$time_start)%(24*60*60))/(60*60)) . " hours " . floor(((time()-self::$time_start)%(24*60*60))/60) . " minutes   \n";
        $display_str .= 'spider name: ' . self::$configs['name'] . "\n";
        $display_str .= 'load average: ' . implode(", ", $loadavg) . "\n";
        $display_str .= "document: https://doc.phpspider.org\n";
        $display_str .= "-------------------------------\033[47;30m TASKS \033[0m-------------------------------\n";

        $display_str .= "\033[47;30mtaskid\033[0m". str_pad('', self::$taskid_length+2-strlen('taskid')). 
        "\033[47;30mpid\033[0m". str_pad('', self::$pid_length+2-strlen('pid')). 
        "\033[47;30mmem\033[0m". str_pad('', self::$mem_length+2-strlen('mem')). 
        "\033[47;30mcollect succ\033[0m". str_pad('', self::$urls_length+2-strlen('collect succ')). 
        "\033[47;30mcollect fail\033[0m". str_pad('', self::$urls_length+2-strlen('collect fail')). 
        "\033[47;30mspeed\033[0m". str_pad('', self::$speed_length+2-strlen('speed')). 
        "\n";

        $display_str .= $this->display_process_ui();

        $display_str .= "---------------------------\033[47;30m COLLECT STATUS \033[0m--------------------------\n";

        $display_str .= "\033[47;30mfind pages\033[0m". str_pad('', 16-strlen('find pages')). 
        "\033[47;30mcollected\033[0m". str_pad('', 15-strlen('collected')). 
        "\033[47;30mqueue\033[0m". str_pad('', 14-strlen('queue')). 
        "\033[47;30mfields\033[0m". str_pad('', 14-strlen('fields')). 
        "\033[47;30mdepth\033[0m". str_pad('', 12-strlen('depth')). 
        "\n";

        $collect   = $this->count_collect_url();
        $collected = $this->count_collected_url();
        $queue     = $this->queue_lsize();
        $fields    = $this->get_fields_num();
        $depth     = $this->get_depth_num();
        $display_str .= str_pad($collect, 16);
        $display_str .= str_pad($collected, 15);
        $display_str .= str_pad($queue, 14);
        $display_str .= str_pad($fields, 14);
        $display_str .= str_pad($depth, 12);
        $display_str .= "\n";

        // 清屏
        $this->shell_clear();
        // 返回到第一行,第一列
        //echo "\033[0;0H";
        $display_str .= "---------------------------------------------------------------------\n";
        $display_str .= "Press Ctrl-C to quit. Start success.\n";
        echo $display_str;

        //if(self::$daemonize)
        //{
            //global $argv;
            //$start_file = $argv[0];
            //echo "Input \"php $start_file stop\" to quit. Start success.\n";
        //}
        //else
        //{
            //echo "Press Ctrl-C to quit. Start success.\n";
        //}
    }

    public function display_process_ui()
    {
        // "\033[32;40m [OK] \033[0m"
        $task_status = $this->get_task_status();
        $display_str = '';
        foreach ($task_status as $json) 
        {
            //$json = util::get_file(PATH_DATA."/status/".$i);
            $task = json_decode($json, true);
            if (empty($task)) 
            {
                continue;
            }
            $display_str .= str_pad($task['id'], self::$taskid_length+2).
                str_pad($task['pid'], self::$pid_length+2).
                str_pad($task['mem'], self::$mem_length+2). 
                str_pad($task['collect_succ'], self::$urls_length+2). 
                str_pad($task['collect_fail'], self::$urls_length+2). 
                str_pad($task['speed'], self::$speed_length+2). 
                "\n";
        }

        //echo "\033[9;0H";
        return $display_str;
    }

    public function parse_command()
    {
        // 检查运行命令的参数
        global $argv;
        $start_file = $argv[0]; 
                
        // 命令
        $command = isset($argv[2]) ? trim($argv[1]) : 'start';
        
        // 子命令，目前只支持-d
        $command2 = isset($argv[2]) ? $argv[2] : '';

        //// 检查主进程是否在运行
        //$master_pid = @file_get_contents(self::$pid_file);
        //$master_is_alive = $master_pid && @posix_kill($master_pid, 0);
        //if($master_is_alive)
        //{
            //if($command === 'start')
            //{
                //log::error("PHPSpider[$start_file] is running");
                //exit;
            //}
        //}
        //elseif($command !== 'start')
        //{
            //log::error("PHPSpider[$start_file] not run");
            //exit;
        //}

        // 根据命令做相应处理
        switch($command)
        {
            // 启动 phpspider
            case 'start':
                break;
            // 显示 phpspider 运行状态
            case 'status':
                exit(0);
            case 'stop':
                break;
            // 未知命令
            default :
                 exit("Usage: php yourfile.php {start|stop|status}\n");
        }
    }

    /**
     * 判断是否附件文件
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    //public function is_attachment_file($url)
    //{
        //$mime_types = $GLOBALS['config']['mimetype'];
        //$mime_types_flip = array_flip($mime_types);

        //$pathinfo = pathinfo($url);
        //$fileext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        //$fileinfo = array();
        //// 存在文件后缀并且是配置里面的后缀
        //if (!empty($fileext) && isset($mime_types_flip[$fileext])) 
        //{
            //stream_context_set_default(
                //array(
                    //'http' => array(
                        //'method' => 'HEAD'
                    //)
                //)
            //);
            //// 代理和Cookie以后实现，方法和 file_get_contents 一样 使用 stream_context_create 设置
            //$headers = get_headers($url, 1);
            //if (strpos($headers[0], '302')) 
            //{
                //$url = $headers['Location'];
                //$headers = get_headers($url, 1);
            //}
            ////print_r($headers);
            //$fileinfo = array(
                //'basename' => isset($pathinfo['basename']) ? $pathinfo['basename'] : '',
                //'filename' => isset($pathinfo['filename']) ? $pathinfo['filename'] : '',
                //'fileext' => isset($pathinfo['extension']) ? $pathinfo['extension'] : '',
                //'filesize' => isset($headers['Content-Length']) ? $headers['Content-Length'] : 0,
                //'atime' => isset($headers['Date']) ? strtotime($headers['Date']) : time(),
                //'mtime' => isset($headers['Last-Modified']) ? strtotime($headers['Last-Modified']) : time(),
            //);

            //$mime_type = 'html';
            //$content_type = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';
            //if (!empty($content_type)) 
            //{
                //$mime_type = isset($GLOBALS['config']['mimetype'][$content_type]) ? $GLOBALS['config']['mimetype'][$content_type] : $mime_type;
            //}
            //$mime_types_flip = array_flip($mime_types);
            //// 判断一下是不是文件名被加什么后缀了，比如 http://www.xxxx.com/test.jpg?token=xxxxx
            //if (!isset($mime_types_flip[$fileinfo['fileext']]))
            //{
                //$fileinfo['fileext'] = $mime_type;
                //$fileinfo['basename'] = $fileinfo['filename'].'.'.$mime_type;
            //}
        //}
        //return $fileinfo;
    //}

}

