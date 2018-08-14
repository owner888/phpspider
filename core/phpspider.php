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
// PHPSpider核心类文件
// ***********
// 泛域名抓取优化版 BY KEN a-site@foxmail.com
// ***********
// * 泛域名设置：domain = array('*')
// * 增加子域名数量限制 $max_sub_num = 100
//----------------------------------

namespace phpspider\core;

require_once __DIR__.'/constants.php';

use Exception;
use phpspider\core\db;
use phpspider\core\log;
use phpspider\core\queue;
use phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\util;

// 启动的时候生成data目录
util::path_exists(PATH_DATA);
util::path_exists(PATH_DATA.'/lock');
util::path_exists(PATH_DATA.'/log');
util::path_exists(PATH_DATA.'/cache');
util::path_exists(PATH_DATA.'/status');

class phpspider
{
    /**
     * 版本号
     * @var string
     */
    const VERSION = '2.1.5';

    /**
     * 爬虫爬取每个网页的时间间隔,0表示不延时, 单位: 毫秒
     */
    const INTERVAL = 100;

    /**
     * 爬虫爬取每个网页的超时时间, 单位: 秒 
     */
    const TIMEOUT = 5;

    /**
     * 爬取失败次数, 不想失败重新爬取则设置为0 
     */
    const MAX_TRY = 0;

    /**
     * 爬虫爬取网页所使用的浏览器类型: pc/Mac、ios、android
     * 默认类型是PC
     */
    const AGENT_PC      = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36';
    const AGENT_IOS     = 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_3_3 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13G34 Safari/601.1';
    const AGENT_ANDROID = 'Mozilla/5.0 (Linux; U; Android 6.0.1;zh_cn; Le X820 Build/FEXCNFN5801507014S) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 Chrome/49.0.0.0 Mobile Safari/537.36 EUI Browser/5.8.015S';

    /**
     * pid文件的路径及名称
     * @var string
     */
    //public static $pid_file = '';

    /**
     * 日志目录, 默认在data根目录下
     * @var mixed
     */
    //public static $log_file = '';

    /**
     * 主任务进程ID 
     */
    //public static $master_pid = 0;

    /**
     * 所有任务进程ID 
     */
    //public static $taskpids = array();

    /**
     * Daemonize.
     *
     * @var bool
     */
    public static $daemonize = false;

    /**
     * 当前进程是否终止 
     */
    public static $terminate = false;

    /**
     * 是否分布式 
     */
    public static $multiserver = false;

    /**
     * 当前服务器ID 
     */
    public static $serverid = 1;

    /**
     * 主任务进程 
     */
    public static $taskmaster = true;

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
     * 生成 
     */
    public static $fork_task_complete = false;

    /**
     * 是否使用Redis 
     */
    public static $use_redis = false;

    /**
     * 是否保存爬虫运行状态 
     */
    public static $save_running_state = false;

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
         'max_try'     => 0        // 允许抓取失败次数
     ) 
     */
    public static $collect_queue = array();

    /**
     * 要抓取的URL数组
     * md5($url) => time()
     */
    public static $collect_urls = array();

    /**
     * 要抓取的URL数量
     */
    public static $collect_urls_num = 0;

    /**
     * 已经抓取的URL数量
     */
    public static $collected_urls_num = 0;

    /**
     * 当前进程采集成功数 
     */
    public static $collect_succ = 0;

    /**
     * 当前进程采集失败数 
     */
    public static $collect_fail = 0;

    /**
     * 提取到的字段数 
     */
    public static $fields_num = 0;

    /**
     * 【KEN】提取到的页面数按域名计数容器 结构为 domain => number
     */
    public static $pages_num = array();

    /**
     * 【KEN】单域名允许抓取的最大页面数,0为不限制
     */
    public static $max_pages = 0;

    /**
     * 【KEN】花费的抓取时长计数容器 结构为 domain => number
     */
    public static $duration = array();

    /**
     * 【KEN】单域名允许抓取的最大时长，单位秒,0为不限制
     */
    public static $max_duration = 0;

    /**
     * 【KEN】单域名最大子域名发现数量 防止掉进蜘蛛池，推荐值：3000（多数大型网站上限）
     */
    public static $max_sub_num = 3000; //建议值 3000

    /**
     * 【KEN】子进程未获取任务，超时退出前，等待计时器
     */

    public static $stand_by_time = 0;

    /**
     * 【KEN】子进程未获取任务，超时退出前，最大等待时长/秒，全部任务束后，子进程将会等待的时间，以便有缓冲时间，获得新的任务
     */
    public static $max_stand_by_time = 60; //建议值 60

    /**
     * 【KEN】每个主机并发上限，降低对方网站流量压力和减少被阻挡概率，建议值 6 ，须与 queue_order = rand 一起使用
     */
    public static $max_task_per_host     = 0; //0值和非0值会使用不同类型的队列缓存库，从0改为非0值或从非0值改为0需清空队列缓存库再运行，否则任务无法添加
    public static $task_per_host_counter = array(); //计数容器

    /**
     * 采集深度
     */
    public static $depth_num = 0;

    /**
     * 爬虫开始时间 
     */
    public static $time_start = 0;

    /**
     * 任务状态
     */
    public static $task_status = array();

    // 导出类型配置
    public static $export_type  = '';
    public static $export_file  = '';
    public static $export_conf  = '';
    public static $export_table = '';

    // 数据库配置
    public static $db_config = array();
    // 队列配置
    public static $queue_config = array();

    // 运行面板参数长度
    public static $server_length  = 10;
    public static $tasknum_length = 8;
    public static $taskid_length  = 8;
    public static $pid_length     = 8;
    public static $mem_length     = 8;
    public static $urls_length    = 15;
    public static $speed_length   = 6;

    /**
     * 爬虫初始化时调用, 用来指定一些爬取前的操作 
     * 
     * @var mixed
     * @access public
     */
    public $on_start = null;

    /**
     * URL采集前调用 
     * 比如有时需要根据某个特定的URL，来决定这次的请求是否使用代理 / 或使用哪个代理
     * 
     * @var mixed
     * @access public
     */
    public $on_before_download_page = null;

    /**
     * 网页状态码回调 
     * 
     * @var mixed
     * @access public
     */
    public $on_status_code = null;

    /**
     * 判断当前网页是否被反爬虫, 需要开发者实现 
     * 
     * @var mixed
     * @access public
     */
    public $is_anti_spider = null;

    /**
     * 在一个网页下载完成之后调用, 主要用来对下载的网页进行处理 
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
    public $on_download_attached_page = null;

    /**
     * 当前页面抽取到URL 
     * 
     * @var mixed
     * @access public
     */
    public $on_fetch_url = null;

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
     * 如果抓取的页面是一个附件文件, 比如图片、视频、二进制文件、apk、ipad、exe 
     * 就不去分析他的内容提取field了, 提取field只针对HTML
     * 
     * @var mixed
     * @access public
     */
    public $on_attachment_file = null;

    public function __construct($configs = array())
    {
        // 产生时钟云，解决php7下面ctrl+c无法停止bug
        declare(ticks = 1);

        // 先打开以显示验证报错内容
        log::$log_show = true;
        log::$log_file = isset($configs['log_file']) ? $configs['log_file'] : PATH_DATA.'/phpspider.log';
        log::$log_type = isset($configs['log_type']) ? $configs['log_type'] : false;

        // 彩蛋
        $included_files = get_included_files();
        $content = file_get_contents($included_files[0]);
        if (!preg_match("#/\* Do NOT delete this comment \*/#", $content) || !preg_match("#/\* 不要删除这段注释 \*/#", $content))
        {
            $msg = "Unknown error...";
            log::error($msg);
            exit;
        }

        $configs['name']        = isset($configs['name'])        ? $configs['name']        : 'phpspider';
        $configs['proxy']       = isset($configs['proxy'])       ? $configs['proxy']       : false;
        $configs['user_agent']  = isset($configs['user_agent'])  ? $configs['user_agent']  : self::AGENT_PC;
        $configs['client_ip']   = isset($configs['client_ip'])   ? $configs['client_ip']   : array();
        $configs['interval']    = isset($configs['interval'])    ? $configs['interval']    : self::INTERVAL;
        $configs['timeout']     = isset($configs['timeout'])     ? $configs['timeout']     : self::TIMEOUT;
        $configs['max_try']     = isset($configs['max_try'])     ? $configs['max_try']     : self::MAX_TRY;
        $configs['max_depth']   = isset($configs['max_depth'])   ? $configs['max_depth']   : 0;
        $configs['max_fields']  = isset($configs['max_fields'])  ? $configs['max_fields']  : 0;
        $configs['export']      = isset($configs['export'])      ? $configs['export']      : array();
        //新增参数 BY KEN <a-site@foxmail.com>
        $configs['max_pages']         = isset($configs['max_pages']) ? $configs['max_pages'] : self::$max_pages;
        $configs['max_duration']      = isset($configs['max_duration']) ? $configs['max_duration'] : self::$max_duration;
        $configs['max_sub_num']       = isset($configs['max_sub_num']) ? $configs['max_sub_num'] : self::$max_sub_num;
        $configs['max_stand_by_time'] = isset($configs['max_stand_by_time']) ? $configs['max_stand_by_time'] : self::$max_stand_by_time;
        $configs['max_task_per_host'] = isset($configs['max_task_per_host']) ? $configs['max_task_per_host'] : self::$max_task_per_host;
        //启用 host并发上限时，队列参数强制为随机
        if ($configs['max_task_per_host'] > 0)
        {
            $configs['queue_order'] = 'rand';
        }
        else
        {
            $configs['queue_order'] = isset($configs['queue_order']) ? $configs['queue_order'] : 'list';
        }

        // csv、sql、db
        self::$export_type  = isset($configs['export']['type'])  ? $configs['export']['type']  : '';
        self::$export_file  = isset($configs['export']['file'])  ? $configs['export']['file']  : '';
        self::$export_table = isset($configs['export']['table']) ? $configs['export']['table'] : '';
        self::$db_config    = isset($configs['db_config'])       ? $configs['db_config']       : array();
        self::$queue_config = isset($configs['queue_config'])    ? $configs['queue_config']    : array();

        // 是否设置了并发任务数, 并且大于1, 而且不是windows环境
        if (isset($configs['tasknum']) && $configs['tasknum'] > 1 && !util::is_win()) 
        {
            self::$tasknum = $configs['tasknum'];
        }

        // 是否设置了保留运行状态
        if (isset($configs['save_running_state'])) 
        {
            self::$save_running_state = $configs['save_running_state'];
        }

        // 是否分布式
        if (isset($configs['multiserver'])) 
        {
            self::$multiserver = $configs['multiserver'];
        }

        // 当前服务器ID
        if (isset($configs['serverid'])) 
        {
            self::$serverid = $configs['serverid'];
        }

        // 不同项目的采集以采集名称作为前缀区分 缩短 spider name md5长度到4位，减少内存占用
        if (isset(self::$queue_config['prefix']))
        {
            self::$queue_config['prefix'] = self::$queue_config['prefix'].'-'.substr(md5($configs['name']), 0, 4);
        }
	
        self::$configs = $configs;
    }

    public function get_config($name)
    {
        return empty(self::$configs[$name]) ? array() : self::$configs[$name];
    }

    public function add_scan_url($url, $options = array(), $allowed_repeat = true)
    {
        // 投递状态
        $status = false;
        //限制最大子域名数量
        if ( ! empty(self::$configs['max_sub_num']))
        {
            //抓取到的子域名超过指定数量，就丢掉此域名
            $sub_domain_count = $this->sub_domain_count($url);
            if ($sub_domain_count > self::$configs['max_sub_num'])
            {
                log::debug('Task('.self::$taskid.') subdomin = '.$sub_domain_count.' more than '.self::$configs['max_sub_num'].",add_scan_url $url [Skip]");
                return $status;
            }
        }

        $link             = $options;
        $link['url']      = $url;
        $link['url_type'] = 'scan_page';
        $link             = $this->link_uncompress($link);

        if ($this->is_content_page($url))
        {
            $link['url_type'] = 'content_page';
            $status           = $this->queue_lpush($link, $allowed_repeat);
        }
        elseif ($this->is_list_page($url))
        {
            $link['url_type'] = 'list_page';
            $status           = $this->queue_lpush($link, $allowed_repeat);
        }
        else
        {
            $status = $this->queue_lpush($link, $allowed_repeat);
        }

        if ($status)
        {
            if ($link['url_type'] == 'scan_page')
            {
                log::debug("Find scan page: {$url}");
            }
            elseif ($link['url_type'] == 'content_page')
            {
                log::debug("Find content page: {$url}");
            }
            elseif ($link['url_type'] == 'list_page')
            {
                log::debug("Find list page: {$url}");
            }
        }

        return $status;
    }

    /**
     * 一般在 on_scan_page 和 on_list_page 回调函数中调用, 用来往待爬队列中添加url
     * 两个进程同时调用这个方法, 传递相同url的时候, 就会出现url重复进入队列
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
        //限制最大子域名数量
        if ( ! empty(self::$configs['max_sub_num']))
        {
            //抓取超过 max_sub_num 子域名的，就丢掉
            $sub_domain_count = $this->sub_domain_count($url);
            if ($sub_domain_count > self::$configs['max_sub_num'])
            {
                log::debug('Task('.self::$taskid.') subdomin = '.$sub_domain_count.' more than '.self::$configs['max_sub_num'].",add_url $url [Skip]");
                //echo '[on_download_page] ' . $domain . "'s subdomin > 1000 ,Skip!\n";
                return $status;
            }
        }
        $link          = $options;
        $link['url']   = $url;
        $link['depth'] = $depth;
        $link = $this->link_uncompress($link);

        if ($this->is_content_page($url))
        {
            $link['url_type'] = 'content_page';
            $status           = $this->queue_lpush($link);
        }
        elseif ($this->is_list_page($url))
        {
            $link['url_type'] = 'list_page';
            $status           = $this->queue_lpush($link);
        }

        if ($status)
        {
            if ($link['url_type'] == 'scan_page')
            {
                log::debug("Find scan page: {$url}");
            }
            elseif ($link['url_type'] == 'content_page')
            {
                log::debug("Find content page: {$url}");
            }
            elseif ($link['url_type'] == 'list_page')
            {
                log::debug("Find list page: {$url}");
            }
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
        //2018-1-3 通配所有域名
        if ( ! empty($parse_url['host']) and self::$configs['domains'][0] == '*')
        {
            return true;
        }
        //限定域名
        if (empty($parse_url['host']) || ! in_array($parse_url['host'], self::$configs['domains']))
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
        //过滤下载类型文件 20180209
        if (preg_match('/\.(zip|7z|cab|rar|iso|gho|jar|ace|tar|gz|bz2|z|xml|pdf|doc|txt|rtf|snd|xls|xlsx|docx|apk|ipa|flv|midi|mps|pls|pps|ppa|pwz|mp3|mp4|mpeg|mpe|asf|asx|mpg|3gp|mov|m4v|mkv|vob|vod|mod|ogg|rm|rmvb|wmv|avi|dat|exe|wps|js|css|bmp|jpg|png|gif|ico|tiff|jpeg|svg|webp|mpa|mdb|bin)$/iu', $url))
        {
            return false;
        }

        //增加 要排除的列表页特征正则 BY KEN <a-site@foxmail.com>
        if ( ! empty(self::$configs['list_url_regexes_remove']))
        {
            foreach (self::$configs['list_url_regexes_remove'] as $regex)
            {
                if (preg_match("#{$regex}#i", $url))
                {
                    return false;
                }
            }
        }

        //增加无列表页选项，即所有页面都要抓取内容，包含列表页
        if (empty(self::$configs['list_url_regexes']) or self::$configs['list_url_regexes'][0] == 'x')
        {
            return false;
        }

        //增加泛列表页，即所有页面都是列表页，只抓取链接，不抓取内容
        if (self::$configs['list_url_regexes'][0] == '*')
        {
            return true;
        }

        if ( ! empty(self::$configs['list_url_regexes']))
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
        //过滤下载类型文件 20180209
        if (preg_match('/\.(zip|7z|cab|rar|iso|gho|jar|ace|tar|gz|bz2|z|xml|pdf|doc|txt|rtf|snd|xls|xlsx|docx|apk|ipa|flv|midi|mps|pls|pps|ppa|pwz|mp3|mp4|mpeg|mpe|asf|asx|mpg|3gp|mov|m4v|mkv|vob|vod|mod|ogg|rm|rmvb|wmv|avi|dat|exe|wps|js|css|bmp|jpg|png|gif|ico|tiff|jpeg|svg|webp|mpa|mdb|bin)$/iu', $url))
        {
            return false;
        }

        //增加 要排除的内容页特征正则 BY KEN <a-site@foxmail.com>
        if ( ! empty(self::$configs['content_url_regexes_remove']))
        {
            foreach (self::$configs['content_url_regexes_remove'] as $regex)
            {
                if (preg_match("#{$regex}#i", $url))
                {
                    return false;
                }
            }
        }

        //增加泛内容模式，即所有页面都要提取内容
        if (empty(self::$configs['content_url_regexes']) or self::$configs['content_url_regexes'][0] == '*')
        {
            return true;
        }
        //无内容，泛列表模式，即所有页面都不提取内容
        if (self::$configs['content_url_regexes'][0] == 'x')
        {
            return false;
        }

        if ( ! empty(self::$configs['content_url_regexes']))
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

    /**
     * Parse command.
     * php yourfile.php start | stop | status | kill
     *
     * @return void
     */
    public function parse_command()
    {
        // 检查运行命令的参数
        global $argv;
        $start_file = $argv[0]; 

        // 命令
        $command = isset($argv[1]) ? trim($argv[1]) : 'start';

        // 子命令, 目前只支持-d
        $command2 = isset($argv[2]) ? $argv[2] : '';

        // 根据命令做相应处理
        switch($command)
        {
            // 启动 phpspider
        case 'start':
            if ($command2 === '-d') 
            {
                self::$daemonize = true;
            }
            break;
        case 'stop':
            exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}'", $info);
            if (count($info) <= 1)
            {
                echo "PHPSpider[$start_file] not run\n";
            }
            else 
            {
                //echo "PHPSpider[$start_file] is stoping ...\n";
                echo "PHPSpider[$start_file] stop success";
                exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGINT", $info);
            }
            exit;
            break;
        case 'kill':
            exec("ps aux | grep $start_file | grep -v grep | awk '{print $2}' |xargs kill -SIGKILL");
            break;
            // 显示 phpspider 运行状态
        case 'status':
            exit(0);
            // 未知命令
        default :
            exit("Usage: php yourfile.php {start|stop|status|kill}\n");
        }
    }

    /**
     * Signal hander.
     *
     * @param int $signal
     */
    public function signal_handler($signal)
    {
        switch ($signal)
        {
            // Stop.
            case SIGINT:
                log::warn('Program stopping...');
                self::$terminate = true;
                break;
            // Show status.
            case SIGUSR2:
                echo "show status\n";
                break;
        }
    }

    /**
     * Install signal handler.
     *
     * @return void
     */
    public function install_signal()
    {
        if (function_exists('pcntl_signal')) 
        {
            // stop
            // static调用方式
            //pcntl_signal(SIGINT, array(__CLASS__, 'signal_handler'), false);
            pcntl_signal(SIGINT, array(&$this, 'signal_handler'), false);
            // status
            pcntl_signal(SIGUSR2, array(&$this, 'signal_handler'), false);
            // ignore
            pcntl_signal(SIGPIPE, SIG_IGN, false);
        }
    }

    /**
     * Run as deamon mode.
     *
     * @throws Exception
     */
    protected static function daemonize()
    {
        if (!self::$daemonize) 
        {
            return;
        }

        // fork前一定要关闭redis
        queue::clear_link();

        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) 
        {
            throw new Exception('fork fail');
        } 
        elseif ($pid > 0) 
        {
            exit(0);
        }
        if (-1 === posix_setsid()) 
        {
            throw new Exception('setsid fail');
        }
        // Fork again avoid SVR4 system regain the control of terminal.
        $pid = pcntl_fork();
        if (-1 === $pid)
        {
            throw new Exception('fork fail');
        }
        elseif (0 !== $pid)
        {
            exit(0);
        }
    }

    /**
     * 检查是否终止当前进程
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function check_terminate()
    {
        if (!self::$terminate) 
        {
            return false;
        }

        // 删除当前任务状态
        $this->del_task_status(self::$serverid, self::$taskid);

        if (self::$taskmaster) 
        {
            // 检查子进程是否都退出
            while (true)
            {
                $all_stop = true;
                for ($i = 2; $i <= self::$tasknum; $i++) 
                {
                    // 只要一个还活着就说明没有完全退出
                    $task_status = $this->get_task_status(self::$serverid, $i);
                    if ($task_status)
                    {
                        $all_stop = false;
                    }
                }
                if ($all_stop)
                {
                    break;
                }
                else
                {
                    log::warn('Task stop waiting...');
                }
                sleep(1);
            }

            $this->del_server_list(self::$serverid);

            // 显示最后结果
            log::$log_show = true;

            $spider_time_run = util::time2second(intval(microtime(true) - self::$time_start));
            log::note("Spider finished in {$spider_time_run}");

            $get_collected_url_num = $this->get_collected_url_num();
            log::note("Total pages: {$get_collected_url_num} \n");
        }
        exit();
    }

    public function start()
    {
        $this->parse_command();

        // 爬虫开始时间
        self::$time_start = time();
        // 当前任务ID
        self::$taskid = 1;
        // 当前任务进程ID
        self::$taskpid      = function_exists('posix_getpid') ? posix_getpid() : 1;
        self::$collect_succ = 0;
        self::$collect_fail = 0;

        //--------------------------------------------------------------------------------
        // 运行前验证
        //--------------------------------------------------------------------------------

        // 检查PHP版本
        if (version_compare(PHP_VERSION, '5.3.0', 'lt')) 
        {
            log::error('PHP 5.3+ is required, currently installed version is: ' . phpversion());
            exit;
        }

        // 检查CURL扩展
        if(!function_exists('curl_init'))
        {
            log::error('The curl extension was not found');
            exit;
        }

        // 多任务需要pcntl扩展支持
        if (self::$tasknum > 1 && !function_exists('pcntl_fork')) 
        {
            log::error('Multitasking needs pcntl, the pcntl extension was not found');
            exit;
        }

        // 守护进程需要pcntl扩展支持
        if (self::$daemonize && !function_exists('pcntl_fork')) 
        {
            log::error('Daemonize needs pcntl, the pcntl extension was not found');
            exit;
        }

        // 集群、保存运行状态、多任务都需要Redis支持
        if ( self::$multiserver || self::$save_running_state || self::$tasknum > 1 ) 
        {
            self::$use_redis = true;

            queue::set_connect('default', self::$queue_config);
            if (!queue::init()) 
            {
                if ( self::$multiserver ) 
                {
                    log::error('Multiserver needs Redis support, '.queue::$error);
                    exit;
                }

                if ( self::$tasknum > 1 ) 
                {
                    log::error('Multitasking needs Redis support, '.queue::$error);
                    exit;
                }

                if ( self::$save_running_state ) 
                {
                    log::error('Spider kept running state needs Redis support, '.queue::$error);
                    exit;
                }
            }
        }

        // 检查导出
        $this->check_export();

        // 检查缓存
        $this->check_cache();

        // 检查 scan_urls 
        if (empty(self::$configs['scan_urls'])) 
        {
            log::error('No scan url to start');
            exit;
        }

        foreach ( self::$configs['scan_urls'] as $url ) 
        {
            // 只检查配置中的入口URL, 通过 add_scan_url 添加的不检查了.
            if (!$this->is_scan_page($url))
            {
                log::error("Domain of scan_urls (\"{$url}\") does not match the domains of the domain name");
                exit;
            }
        }

        // windows 下没法显示面板, 强制显示日志
        if (util::is_win()) 
        {
            self::$configs['name'] = iconv('UTF-8', 'GB2312//IGNORE', self::$configs['name']);
            log::$log_show         = true;
        }
        // 守护进程下也显示日志
        elseif (self::$daemonize) 
        {
            log::$log_show = true;
        }
        else 
        {
            log::$log_show = isset(self::$configs['log_show']) ? self::$configs['log_show'] : false;
        }

        if (log::$log_show)
        {
            global $argv;
            $start_file = $argv[0]; 

            $header = '';
            if ( ! util::is_win())
            {
                $header .= "\033[33m";
            }

            $header .= "\n[ ".self::$configs['name']." Spider ] is started...\n\n";
            $header .= '  * PHPSpider Version: '.self::VERSION."\n";
            $header .= "  * Documentation: https://doc.phpspider.org\n";
            $header .= '  * Task Number: '.self::$tasknum."\n\n";
            $header .= "Input \"php $start_file stop\" to quit. Start success.\n";
            if ( ! util::is_win())
            {
                $header .= "\033[0m";
            }

            log::note($header);
        }

        // 如果是守护进程，恢复日志状态
        //if (self::$daemonize) 
        //{
            //log::$log_show = isset(self::$configs['log_show']) ? self::$configs['log_show'] : false;
        //}

        // 多任务和分布式都要清掉, 当然分布式只清自己的
        $this->init_redis();

        //--------------------------------------------------------------------------------
        // 生成多任务
        //--------------------------------------------------------------------------------

        // 添加入口URL到队列
        foreach ( self::$configs['scan_urls'] as $url ) 
        {
            // false 表示不允许重复
            $this->add_scan_url($url, null, false);
        }

        // 放这个位置, 可以添加入口页面
        if ($this->on_start) 
        {
            call_user_func($this->on_start, $this);
        }

        if (!self::$daemonize) 
        {
            if (!log::$log_show) 
            {
                // 第一次先清屏
                $this->clear_echo();

                // 先显示一次面板, 然后下面再每次采集成功显示一次
                $this->display_ui();
            }
        }
        else 
        {
            $this->daemonize();
        }

        // 安装信号
        $this->install_signal();

        // 开始采集
        $this->do_collect_page();

        // 从服务器列表中删除当前服务器信息
        $this->del_server_list(self::$serverid);
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
        elseif (0 === $pid)
        {
            log::warn("Fork children task({$taskid}) successful...");

            // 初始化子进程参数
            self::$time_start   = microtime(true);
            self::$taskid       = $taskid;
            self::$taskmaster   = false;
            self::$taskpid      = posix_getpid();
            self::$collect_succ = 0;
            self::$collect_fail = 0;

            queue::set_connect('default', self::$queue_config);
            queue::init();

            //退出前计时，等待1分钟，如果获取不到新任务，再退出
            self::$stand_by_time = 0;
            while (self::$stand_by_time < self::$configs['max_stand_by_time'])
            {
                $this->do_collect_page();
                log::warn('Task('.self::$taskid.') Stand By '.self::$stand_by_time.'/'.self::$configs['max_stand_by_time'].' s');
                self::$stand_by_time++;
                sleep(1);
            }
            $queue_lsize = $this->queue_lsize();
            log::warn('Task('.self::$taskid.') exit : queue_lsize = '.$queue_lsize);
            $this->del_task_status(self::$serverid, $taskid);

            // 这里用0表示子进程正常退出
            exit(0);
        }
        else
        {
            log::error("Fork children task({$taskid}) fail...");
            exit;
        }
    }

    public function do_collect_page() 
    {
        while( $queue_lsize = $this->queue_lsize() )
        { 
            // 如果是主任务
            if (self::$taskmaster) 
            {
                // 多任务下主任务未准备就绪
                if (self::$tasknum > 1 && !self::$fork_task_complete) 
                {
                    // 主进程采集到多于任务数2个时, 生成子任务一起采集
                    if ($queue_lsize > self::$tasknum + 2)
                    {
                        self::$fork_task_complete = true;

                        // fork 子进程前一定要先干掉redis连接fd, 不然会存在进程互抢redis fd 问题
                        queue::clear_link();
                        // task进程从2开始, 1被master进程所使用
                        for ($i = 2; $i <= self::$tasknum; $i++) 
                        {
                            $this->fork_one_task($i);
                        }
                    }
                }
                //在主进程中，保存当前配置到缓存，以使子进程可实时读取动态修改后的配置 20180209
                if (self::$use_redis and ! empty(self::$configs))
                {
                    queue::set('configs_'.self::$configs['name'], json_encode(self::$configs));
                }
                // 抓取页面
                $this->collect_page();
                // 保存任务状态
                $this->set_task_status();

                // 每采集成功一次页面, 就刷新一次面板
                if (!log::$log_show && !self::$daemonize) 
                {
                    $this->display_ui();
                }
            }
            // 如果是子任务
            else 
            {
                // 主进程采集到多于任务数2个时, 子任务可以采集, 否则等待...
                if ($queue_lsize > self::$taskid + 2)
                {
                    //在子进程中，从内存中实时读取当前最新配置，用于适应主进程常驻内存模式，无限循环后的配置变动 20180209
                    if (self::$use_redis and ! empty(self::$configs))
                    {
                        if ($configs_active = queue::get('configs_'.self::$configs['name']))
                        {
                            self::$configs = json_decode($configs_active, true);
                        }
                    }
                    // 抓取页面
                    $this->collect_page();
                    // 保存任务状态
                    $this->set_task_status();
                }
                else 
                {
                    log::warn('Task('.self::$taskid.') waiting...reason: queue_lsize = '.$queue_lsize.' < tasknum  = '.self::$tasknum);
                    sleep(1);
                }
            }

            // 检查进程是否收到关闭信号
            $this->check_terminate();
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
        //减少非必要 queue_lsize 查询 20180214
        if (isset(self::$configs['log_type']) and strstr(self::$configs['log_type'], 'info'))
        {
            $get_collect_url_num = $this->get_collect_url_num();
            log::info('task id: '.self::$taskid." Find pages: {$get_collect_url_num} ");

            $queue_lsize = $this->queue_lsize();
            log::info('task id: '.self::$taskid." Waiting for collect pages: {$queue_lsize} ");

            $get_collected_url_num = $this->get_collected_url_num();
            log::info('task id: '.self::$taskid." Collected pages: {$get_collected_url_num} ");

            // 多任务的时候输出爬虫序号
            if (self::$tasknum > 1)
            {
                log::info('Current task id: '.self::$taskid);
            }
        }
        //顺序提取任务，先进先出(当配置 queue_order = rand ，先进先出无效，都为随机提取任务)
        $link = $this->queue_rpop();

        if (empty($link))
        {
            log::warn('Task('.self::$taskid.') Get Task link Fail...Stand By...');
            return false;
        }
        $link = $this->link_uncompress($link);
        if (empty($link['url']))
        {
            log::warn('Task('.self::$taskid.') Get Task url Fail...Stand By...');
            return false;
        }
        self::$stand_by_time = 0; //接到任务，则超时退出计时重置

        $url = $link['url'];

        //限制单域名最大url数量 20180213
        if (isset(self::$configs['max_pages']) and self::$configs['max_pages'] > 0)
        {
            $domain_pages_num = $this->incr_pages_num($url);
            if ($domain_pages_num > self::$configs['max_pages'])
            {
                log::debug('Task('.self::$taskid.') pages = '.$domain_pages_num.' more than '.self::$configs['max_pages'].", $url [Skip]");
                return false;
            }
        }

        //限制单域名最大花费时长 20180213
        if (isset(self::$configs['max_duration']) and self::$configs['max_duration'] > 0)
        {
            $domain_duration = $this->get_duration_num($url);
            if ($domain_duration > self::$configs['max_duration'])
            {
                log::debug('Task('.self::$taskid.') duration = '.$domain_duration.' more than '.self::$configs['max_duration'].", $url [Skip]");
                return false;
            }
        }

        //当前 host 并发检测 2018-5 BY KEN <a-site@foxmail.com>
        if (self::$configs['max_task_per_host'] > 0)
        {
            $task_per_host = $this->get_task_per_host_num($url);
            if ($task_per_host < self::$configs['max_task_per_host'])
            {
                $task_per_host = $this->incr_task_per_host($url);
            }
            else
            {
                log::warn('Task('.self::$taskid.') task_per_host = '.$task_per_host.' > '.self::$configs['max_task_per_host'].' ; URL: '.$url.' will be retry later...');
                $this->queue_lpush($link); //放回队列
                usleep(100000);
                return false;
            }
        }

        // 已采集页面数量 +1
        $this->incr_collected_url_num($url);

        // 爬取页面开始时间
        $page_time_start = microtime(true);

        // 下载页面前执行
        // 比如有时需要根据某个特定的URL，来决定这次的请求是否使用代理 / 或使用哪个代理
        if ($this->on_before_download_page) 
        {
            $return = call_user_func($this->on_before_download_page, $url, $link, $this);
            if (isset($return)) $link = $return;
        }

        requests::$input_encoding = null;
        $html = $this->request_url($url, $link);

        //记录速度较慢域名花费抓取时间 20180213
        $time_run = round(microtime(true) - $page_time_start);
        if ($time_run > 1)
        {
            $this->incr_duration_num($url, $time_run);
        }

        // 爬完页面开始处理时间
        $page_time_start = microtime(true);
	
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
                'taskid'       => self::$taskid,
            ),
        );
        //printf("memory usage: %.2f M\n", memory_get_usage() / 1024 / 1024 ); 
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
        // 比如下载了某个网页, 希望向网页的body中添加html标签
        if ($this->on_download_page)
        {
            $return = call_user_func($this->on_download_page, $page, $this);
            // 针对那些老是忘记return的人
            if (isset($return))
            {
                $page = $return;
            }
            unset($return);
        }

        // 是否从当前页面分析提取URL
        // 回调函数如果返回false表示不需要再从此网页中发现待爬url
        $is_find_url = true;
        if ($link['url_type'] == 'scan_page')
        {
            if ($this->on_scan_page)
            {
                $return = call_user_func($this->on_scan_page, $page, $page['raw'], $this);
                if (isset($return))
                {
                    $is_find_url = $return;
                }

                unset($return);
            }
        }
        elseif ($link['url_type'] == 'content_page')
        {
            if ($this->on_content_page)
            {
                $return = call_user_func($this->on_content_page, $page, $page['raw'], $this);
                if (isset($return))
                {
                    $is_find_url = $return;
                }
                unset($return);
            }
        }
        elseif ($link['url_type'] == 'list_page')
        {
            if ($this->on_list_page)
            {
                $return = call_user_func($this->on_list_page, $page, $page['raw'], $this);
                if (isset($return))
                {
                    $is_find_url = $return;
                }
                unset($return);
            }
        }

        // on_scan_page、on_list_page、on_content_page 返回false表示不需要再从此网页中发现待爬url
        if ($is_find_url) 
        {
            // 如果深度没有超过最大深度, 获取下一级URL
            if (self::$configs['max_depth'] == 0 || $link['depth'] < self::$configs['max_depth']) 
            {
                // 分析提取HTML页面中的URL
                $this->get_urls($page['raw'], $url, $link['depth'] + 1);
            }
        }

        // 如果是内容页, 分析提取HTML页面中的字段
        // 列表页也可以提取数据的, source_type: urlcontext, 未实现
        if ($link['url_type'] == 'content_page') 
        {
            $this->get_html_fields($page['raw'], $url, $page);
        }

        // 如果当前深度大于缓存的, 更新缓存
        $this->incr_depth_num($link['depth']);

        // 处理页面耗时时间
        $time_run = round(microtime(true) - $page_time_start, 3);
        log::debug('task id: '.self::$taskid." Success process page {$url} in {$time_run} s");

        $spider_time_run = util::time2second(intval(microtime(true) - self::$time_start));
        log::info('task id: '.self::$taskid." Spider running in {$spider_time_run}");

        // 爬虫爬取每个网页的时间间隔, 单位: 毫秒
        if (!isset(self::$configs['interval'])) 
        {
            // 默认睡眠100毫秒, 太快了会被认为是ddos
            self::$configs['interval'] = 100;
        }
        usleep(self::$configs['interval'] * 1000);
    }

    /**
     * 下载网页, 得到网页内容
     * 
     * @param mixed $url
     * @param mixed $link
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function request_url($url, $link = array())
    {
        $time_start = microtime(true);

        //$url = "http://www.qiushibaike.com/article/117568316";

        // 设置了编码就不要让requests去判断了
        if (isset(self::$configs['input_encoding'])) 
        {
            requests::$input_encoding = self::$configs['input_encoding'];
        }
        // 得到的编码如果不是utf-8的要转成utf-8, 因为xpath只支持utf-8
        requests::$output_encoding = 'utf-8';
        requests::set_timeout(self::$configs['timeout']);
        requests::set_useragent(self::$configs['user_agent']);

        // 先删除伪造IP
        requests::del_client_ip();
        // 是否设置了伪造IP
        if (self::$configs['client_ip']) 
        {
            requests::set_client_ip(self::$configs['client_ip']);
        }

        // 先删除代理，免得前一个URL的代理被带过来了
        requests::del_proxy();
        // 是否设置了代理
        if ($link['proxy']) 
        {
            requests::set_proxy($link['proxy']);
        }

        // 如何设置了 HTTP Headers
        if (!empty($link['headers'])) 
        {
            foreach ($link['headers'] as $k=>$v) 
            {
                requests::set_header($k, $v);
            }
        }
        //限制 http 请求模式为 get 或 post
        $method = trim(strtolower($link['method']));
        $method = ($method == 'post') ? 'post' : 'get';
        $params = empty($link['params']) ? array() : $link['params'];
        $html = requests::$method($url, $params);
        // 此url附加的数据不为空, 比如内容页需要列表页一些数据, 拼接到后面去
        if ($html && !empty($link['context_data'])) 
        {
            $html .= $link['context_data'];
        }

        $http_code = requests::$status_code;

        //请求完成 host 的并发计数减 1 2018-5 BY KEN <a-site@foxmail.com>
        if (self::$configs['max_task_per_host'] > 0)
        {
            $this->incr_task_per_host($url, 'decr');
        }

        if ($this->on_status_code)
        {
            $return = call_user_func($this->on_status_code, $http_code, $url, $html, $this);
            if (isset($return)) 
            {
                $html = $return;
            }
            unset($return);
            if ( ! $html)
            {
                return false;
            }
        }

        if ($http_code != 200)
        {
            // 如果是301、302跳转, 抓取跳转后的网页内容
            if ($http_code == 301 || $http_code == 302) 
            {
                $info = requests::$info;
                //if (isset($info['redirect_url'])) 
                if (!empty($info['redirect_url'])) 
                {
                    $url = $info['redirect_url'];
                    requests::$input_encoding = null;
                    $method = empty($link['method']) ? 'get' : strtolower($link['method']);
                    $params = empty($link['params']) ? array() : $link['params'];
                    $html = requests::$method($url, $params);
                    // 有跳转的就直接获取就好，不要调用自己，容易进入死循环
                    //$html = $this->request_url($url, $link);
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
                if ( ! empty(self::$configs['max_try']) and $http_code == 407)
                {
                    // 扔到队列头部去, 继续采集
                    $this->queue_rpush($link);
                    log::error("Failed to download page {$url}");
                    self::$collect_fail++;
                }
                elseif ( ! empty(self::$configs['max_try']) and in_array($http_code, array('0', '502', '503', '429')))
                {
                    // 采集次数加一
                    $link['try_num']++;
                    // 抓取次数 小于 允许抓取失败次数
                    if ( $link['try_num'] <= $link['max_try'] ) 
                    {
                        // 扔到队列头部去, 继续采集
                        $this->queue_rpush($link);
                    }
                    log::error("Failed to download page {$url}, retry({$link['try_num']})");
                }
                else 
                {
                    log::error("Failed to download page {$url}");
                    self::$collect_fail++;
                }
                log::error("HTTP CODE: {$http_code}");
                return false;
            }
        }

        // 爬取页面耗时时间
        $time_run = round(microtime(true) - $time_start, 3);
        log::debug("Success download page {$url} in {$time_run} s");
        self::$collect_succ++;

        return $html;
    }

    /**
     * 分析提取HTML页面中的URL
     * 
     * @param mixed $html           HTML内容
     * @param mixed $collect_url    抓取的URL, 用来拼凑完整页面的URL
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function get_urls($html, $collect_url, $depth = 0) 
    { 
        //--------------------------------------------------------------------------------
        // 正则匹配出页面中的URL
        //--------------------------------------------------------------------------------
        $urls = selector::select($html, '//a/@href');             
        //preg_match_all("/<a.*href=[\"']{0,1}(.*)[\"']{0,1}[> \r\n\t]{1,}/isU", $html, $matchs); 
        //$urls = array();
        //if (!empty($matchs[1])) 
        //{
            //foreach ($matchs[1] as $url) 
            //{
                //$urls[] = str_replace(array("\"", "'",'&amp;'), array("",'','&'), $url);
            //}
        //}

        if (empty($urls)) 
        {
            return false;
        }

        // 如果页面上只有一个url，要把他转为数组，否则下面会报警告
        if (!is_array($urls)) 
        {
            $urls = array($urls);
        }

        foreach ($urls as $key=>$url) 
        {
            //限制最大子域名数量
            if ( ! empty(self::$configs['max_sub_num']))
            {
                //抓取子域名超过超过指定值，就丢掉
                $sub_domain_count = $this->sub_domain_count($url);
                if ($sub_domain_count > self::$configs['max_sub_num'])
                {
                    unset($urls[$key]);
                    log::debug('Task('.self::$taskid.') subdomin = '.$sub_domain_count.' more than '.self::$configs['max_sub_num'].",get_urls $url [Skip]");
                    continue;
                }
            }
            $urls[$key] = str_replace(array('"', "'", '&amp;'), array('', '', '&'), $url);
        }

        //--------------------------------------------------------------------------------
        // 过滤和拼凑URL
        //--------------------------------------------------------------------------------
        // 去除重复的URL
        $urls = array_unique($urls);
        foreach ($urls as $k=>$url) 
        {
            $url = trim($url);
            if (empty($url)) 
            {
                continue;
            }

            $val = $this->fill_url($url, $collect_url);

            //限制单域名最大url数量 20180213
            if ($val and isset(self::$configs['max_pages']) and self::$configs['max_pages'] > 0)
            {
                $domain_pages_num = $this->incr_pages_num($val);
                if ($domain_pages_num > self::$configs['max_pages'])
                {
                    continue;
                }
            }

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
            if ($this->on_fetch_url) 
            {
                $return = call_user_func($this->on_fetch_url, $url, $this);
                $url = isset($return) ? $return : $url;
                unset($return);

                // 如果 on_fetch_url 返回 false，此URL不入队列
                if (!$url) 
                {
                    continue;
                }
            }

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
        $url         = trim($url);
        $collect_url = trim($collect_url);

        // 排除JavaScript的连接
        //if (strpos($url, "javascript:") !== false)
        if (preg_match("@^(mailto|javascript:|#|'|\")@i", $url) || $url == '')
        {
            return false;
        }
        // 排除没有被解析成功的语言标签
        if (substr($url, 0, 3) == '<%=' or substr($url, 0, 1) == '{' or substr($url, 0, 2) == ' {')
        // if(substr($url, 0, 3) == '<%=')
        {
            return false;
        }

        $parse_url = @parse_url($collect_url);
        if (empty($parse_url['scheme']) || empty($parse_url['host'])) 
        {
            return false;
        }
        // 过滤mailto、tel、sms、wechat、sinaweibo、weixin等协议
        if ( ! in_array($parse_url['scheme'], array('http', 'https')))
        {
            return false;
        }
        $scheme        = $parse_url['scheme'];
        $domain        = $parse_url['host'];
        $path          = empty($parse_url['path']) ? '' : $parse_url['path'];
        $base_url_path = $domain.$path;
        $base_url_path = preg_replace("/\/([^\/]*)\.(.*)$/", '/', $base_url_path);
        $base_url_path = preg_replace("/\/$/", '', $base_url_path);
        $i             = $path_step             = 0;
        $dstr          = $pstr          = '';
        $pos           = strpos($url, '#');
        if ($pos > 0)
        {
            // 去掉#和后面的字符串
            $url = substr($url, 0, $pos);
        }

        // 修正url格式为 //www.jd.com/111.html 为正确的http
        if (substr($url, 0, 2) == '//')
        {
            $url = preg_replace('/^\/\//iu', '', $url);
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
                    // 遇到 ., 不知道为什么不直接写$u == '.', 貌似一样的
                    else if( $i < count($urls)-1 )
                    {
                        $dstr .= $urls[$i].'/';
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
                $url    = preg_replace('#^http://#i', '', $url);
                $scheme = 'http';
            }
            else if( strtolower(substr($url, 0, 8))=='https://' )
            {
                $url = preg_replace('#^https://#i','',$url);
                $scheme = "https";
            }
            // 相对路径，像 1111.html 这种
            else
            {
                $arr = explode("/", $base_url_path);
                // 去掉空值
                $arr = array_filter($arr);
                $base_url_path = implode("/", $arr);
                $url = $base_url_path.'/'.$url;
            }
        }
        // 两个 / 或以上的替换成一个 /
        $url = preg_replace('/\/{1,}/i', '/', $url);
        $url = $scheme.'://'.$url;

        $parse_url = @parse_url($url);
        $domain    = empty($parse_url['host']) ? $domain : $parse_url['host'];
        // 如果host不为空, 判断是不是要爬取的域名
        if ( ! empty($parse_url['host']))
        {
            //2018-1-3 通配所有域名
            if (empty(self::$configs['domains']) or self::$configs['domains'][0] == '*')
            {
                return $url;
            }
            //排除非域名下的url以提高爬取速度
            if (!in_array($parse_url['host'], self::$configs['domains'])) 
            {
                return false;
            }
        }

        return $url;
    }

    /**
     * 连接对象压缩
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-05 18:58
     */
    public function link_compress($link)
    {
        if (empty($link['url_type'])) 
        {
            unset($link['url_type']);
        }

        if (empty($link['method']) || strtolower($link['method']) == 'get') 
        {
            unset($link['method']);
        }

        if (empty($link['headers'])) 
        {
            unset($link['headers']);
        }

        if (empty($link['params'])) 
        {
            unset($link['params']);
        }

        if (empty($link['context_data'])) 
        {
            unset($link['context_data']);
        }

        if (empty($link['proxy'])) 
        {
            unset($link['proxy']);
        }

        if (empty($link['try_num'])) 
        {
            unset($link['try_num']);
        }

        if (empty($link['max_try'])) 
        {
            unset($link['max_try']);
        }

        if (empty($link['depth'])) 
        {
            unset($link['depth']);
        }
        //$json = json_encode($link);
        //$json = gzdeflate($json);
        return $link;
    }

    /**
     * 连接对象解压缩
     * 
     * @param mixed $link
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-05 18:58
     */
    public function link_uncompress($link)
    {
        $link = array(
            'url'          => isset($link['url'])          ? $link['url']          : '',             
            'url_type'     => isset($link['url_type'])     ? $link['url_type']     : '',             
            'method'       => isset($link['method'])       ? $link['method']       : 'get',             
            'headers'      => isset($link['headers'])      ? $link['headers']      : array(),    
            'params'       => isset($link['params'])       ? $link['params']       : array(),           
            'context_data' => isset($link['context_data']) ? $link['context_data'] : '',                
            'proxy'        => isset($link['proxy'])        ? $link['proxy']        : self::$configs['proxy'],             
            'try_num'      => isset($link['try_num'])      ? $link['try_num']      : 0,                 
            'max_try'      => isset($link['max_try'])      ? $link['max_try']      : self::$configs['max_try'],
            'depth'        => isset($link['depth'])        ? $link['depth']        : 0,             
        );

        return $link;
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
                $return = call_user_func($this->on_extract_page, $page, $fields);
                if (!isset($return))
                {
                    log::warn("on_extract_page return value can't be empty");
                }
                // 返回false，跳过当前页面，内容不入库
                elseif ($return === false)
                {
                    return false;
                }
                elseif (!is_array($return))
                {
                    log::warn('on_extract_page return value must be an array');
                }
                else 
                {
                    $fields = $return;
                }
            }

            if (isset($fields) && is_array($fields)) 
            {
                $fields_num = $this->incr_fields_num();
                if (self::$configs['max_fields'] != 0 && $fields_num > self::$configs['max_fields']) 
                {
                    exit(0);
                }

                if (version_compare(PHP_VERSION,'5.4.0','<'))
                {
                    $fields_str = json_encode($fields);
                    $fields_str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matchs)
                    {
                        return @iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
                    }, $fields_str);
                }
                else
                {
                    $fields_str = json_encode($fields, JSON_UNESCAPED_UNICODE);
                }

                if (util::is_win()) 
                {
                    $fields_str = mb_convert_encoding($fields_str, 'gb2312', 'utf-8');
                }
                log::info("Result[{$fields_num}]: ".$fields_str);

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

            $values = NULL;
            // 如果定义抽取规则
            if (!empty($conf['selector'])) 
            {
                // 如果这个field是上一个field的附带连接
                if (isset($conf['source_type']) && $conf['source_type']=='attached_url') 
                {
                    // 取出上个field的内容作为连接, 内容分页是不进队列直接下载网页的
                    if (!empty($fields[$conf['attached_url']])) 
                    {
                        $collect_url = $this->fill_url($fields[$conf['attached_url']], $url);
                        log::debug("Find attached content page: {$collect_url}");
                        $link['url'] = $collect_url;
                        $link = $this->link_uncompress($link);
                        requests::$input_encoding = null;
                        $html                     = $this->request_url($collect_url, $link);
                        // 在一个attached_url对应的网页下载完成之后调用. 主要用来对下载的网页进行处理.
                        if ($this->on_download_attached_page) 
                        {
                            $return = call_user_func($this->on_download_attached_page, $html, $this);
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
                    // 如果找不到，返回的是false
                    $values = $this->get_fields_xpath($html, $conf['selector'], $conf['name']);
                }
                elseif ($conf['selector_type']=='css') 
                {
                    $values = $this->get_fields_css($html, $conf['selector'], $conf['name']);
                }
                elseif ($conf['selector_type']=='regex') 
                {
                    $values = $this->get_fields_regex($html, $conf['selector'], $conf['name']);
                }

                // field不为空而且存在子配置
                if (isset($values) && !empty($conf['children'])) 
                {
                    // 如果提取到的结果是字符串，就转为数组，方便下面统一foreach
                    if (!is_array($values)) 
                    {
                        $values = array($values);
                    }
                    $child_values = array();
                    // 父项抽取到的html作为子项的提取内容
                    foreach ($values as $child_html) 
                    {
                        // 递归调用本方法, 所以多少子项目都支持
                        $child_value = $this->get_fields($conf['children'], $child_html, $url, $page);
                        if (!empty($child_value)) 
                        {
                            $child_values[] = $child_value;
                        }
                    }
                    // 有子项就存子项的数组, 没有就存HTML代码块
                    if (!empty($child_values)) 
                    {
                        $values = $child_values;
                    }
                }
            }

            if (!isset($values)) 
            {
                // 如果值为空而且值设置为必须项, 跳出foreach循环
                if ($required) 
                {
                    log::warn("Selector {$conf['name']}[{$conf['selector']}] not found, It's a must");
                    // 清空整个 fields，当前页面就等于略过了
                    $fields = array();
                    break;
                }
                // 避免内容分页时attached_url拼接时候string + array了
                $fields[$conf['name']] = '';
                //$fields[$conf['name']] = array();
            }
            else 
            {
                if (is_array($values)) 
                {
                    if ($repeated) 
                    {
                        $fields[$conf['name']] = $values;
                    }
                    else 
                    {
                        $fields[$conf['name']] = $values[0];
                    }
                }
                else 
                {
                    $fields[$conf['name']] = $values;
                }
                // 不重复抽取则只取第一个元素
                //$fields[$conf['name']] = $repeated ? $values : $values[0];
            }
        }

        if (!empty($fields)) 
        {
            foreach ($fields as $fieldname => $data) 
            {
                $pattern = "/<img\s+.*?src=[\"']{0,1}(.*)[\"']{0,1}[> \r\n\t]{1,}/isu";
                /*$pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.jpeg|\.png]))[\'|\"].*?[\/]?>/i"; */
                // 在抽取到field内容之后调用, 对其中包含的img标签进行回调处理
                if ($this->on_handle_img && preg_match($pattern, $data)) 
                {
                    $return = call_user_func($this->on_handle_img, $fieldname, $data);
                    if (!isset($return))
                    {
                        log::warn("on_handle_img return value can't be empty\n");
                    }
                    else 
                    {
                        // 有数据才会执行 on_handle_img 方法, 所以这里不要被替换没了
                        $data = $return;
                    }
                }

                // 当一个field的内容被抽取到后进行的回调, 在此回调中可以对网页中抽取的内容作进一步处理
                if ($this->on_extract_field) 
                {
                    $return = call_user_func($this->on_extract_field, $fieldname, $data, $page);
                    if (!isset($return))
                    {
                        log::warn("on_extract_field return value can't be empty\n");
                    }
                    else 
                    {
                        // 有数据才会执行 on_extract_field 方法, 所以这里不要被替换没了
                        $fields[$fieldname] = $return;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * 验证导出
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-02 23:37
     */
    public function check_export()
    {
        // 如果设置了导出选项
        if (!empty(self::$configs['export'])) 
        {
            if (self::$export_type == 'csv') 
            {
                if (empty(self::$export_file)) 
                {
                    log::error('Export data into CSV files need to Set the file path.');
                    exit;
                }
            }
            elseif (self::$export_type == 'sql') 
            {
                if (empty(self::$export_file)) 
                {
                    log::error('Export data into SQL files need to Set the file path.');
                    exit;
                }
            }
            elseif (self::$export_type == 'db') 
            {
                if (!function_exists('mysqli_connect'))
                {
                    log::error('Export data to a database need Mysql support, unable to load mysqli extension.');
                    exit;
                }

                if (empty(self::$db_config)) 
                {
                    log::error('Export data to a database need Mysql support, you have not set a config array for connect.');
                    exit;
                }

                $config = self::$db_config;
                @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['name'], $config['port']);
                if(mysqli_connect_errno())
                {
                    log::error('Export data to a database need Mysql support, '.mysqli_connect_error());
                    exit;
                }

                db::set_connect('default', $config);
                db::_init();

                if (!db::table_exists(self::$export_table))
                {
                    log::error('Table '.self::$export_table.' does not exist');
                    exit;
                }
            }
        }
    }

    public function check_cache()
    {
        if ( !self::$use_redis || self::$save_running_state)
        {
            return false;
        }

        // 这个位置要改
        //$keys = queue::keys("*"); 
        //$count = count($keys);
        // 直接检查db，清空的时候整个db清空，所以注意db不要跟其他项目混用
        $count = queue::dbsize();
        if ( $count > 0 ) 
        {
            // After this operation, 4,318 kB of additional disk space will be used.
            // Do you want to continue? [Y/n] 
            //$msg = "发现Redis中有采集数据, 是否继续执行, 不继续则清空Redis数据重新采集\n";
            $msg = "Found that the data of Redis, no continue will empty Redis data start again\n";
            $msg .= 'Do you want to continue? [Y/n]';
            fwrite(STDOUT, $msg);
            $arg = strtolower(trim(fgets(STDIN)));
            $arg = empty($arg) || !in_array($arg, array('Y', 'N', 'y','n')) ? 'y' : strtolower($arg);
            if ($arg == 'n') 
            {
                log::warn('Clear redis data...');
                queue::flushdb();
                // 下面这种性能太差了
                //foreach ($keys as $key) 
                //{
                    //$key = str_replace(self::$queue_config['prefix'].':', '', $key);
                    //queue::del($key);
                //}
            }
        }
    }

    public function init_redis()
    {
        if (!self::$use_redis)
        {
            return false;
        }

        // 添加当前服务器到服务器列表
        $this->add_server_list(self::$serverid, self::$tasknum);

        // 删除当前服务器的任务状态
        // 对于被强制退出的进程有用
        for ($i = 1; $i <= self::$tasknum; $i++) 
        {
            $this->del_task_status(self::$serverid, $i);
        }
    }

    /**
     * 设置任务状态, 主进程和子进程每成功采集一个页面后调用
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-30 23:56
     */
    public function set_task_status()
    {
        // 每采集成功一个页面, 生成当前进程状态到文件, 供主进程使用
        $mem = round(memory_get_usage(true)/(1024*1024),2);
        $use_time = microtime(true) - self::$time_start; 
        $speed = round((self::$collect_succ + self::$collect_fail) / $use_time, 2);
        $status = array(
            'id' => self::$taskid,
            'pid' => self::$taskpid,
            'mem' => $mem,
            'collect_succ' => self::$collect_succ,
            'collect_fail' => self::$collect_fail,
            'speed' => $speed,
        );
        $task_status = json_encode($status);

        if (self::$use_redis)
        {
            $key = 'server-'.self::$serverid.'-task_status-'.self::$taskid;
            queue::set($key, $task_status);
        }
        else 
        {
            self::$task_status = array($task_status);
        }
    }

    /**
     * 删除任务状态
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function del_task_status($serverid, $taskid)
    {
        if (!self::$use_redis)
        {
            return false;
        }
        $key = "server-{$serverid}-task_status-{$taskid}";
        queue::del($key); 
    }

    /**
     * 获得任务状态, 主进程才会调用
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-30 23:56
     */
    public function get_task_status($serverid, $taskid)
    {
        if (!self::$use_redis)
        {
            return false;
        }

        $key = "server-{$serverid}-task_status-{$taskid}";
        $task_status = queue::get($key);
        return $task_status;
    }

    /**
     * 获得任务状态, 主进程才会调用
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-10-30 23:56
     */
    public function get_task_status_list($serverid = 1, $tasknum)
    {
        $task_status = array();
        if (self::$use_redis)
        {
            for ($i = 1; $i <= $tasknum; $i++) 
            {
                $key           = "server-{$serverid}-task_status-".$i;
                $task_status[] = queue::get($key);
            }
        }
        else 
        {
            $task_status = self::$task_status;
        }
        return $task_status;
    }

    /**
     * 添加当前服务器信息到服务器列表
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function add_server_list($serverid, $tasknum)
    {
        if (!self::$use_redis) 
        {
            return false;
        }

        // 更新服务器列表
        $server_list_json = queue::get('server_list');
        $server_list      = array();
        if ( ! $server_list_json)
        {
            $server_list[$serverid] = array(
                'serverid' => $serverid,
                'tasknum' => $tasknum,
                'time' => time(),
            );
        }
        else 
        {
            $server_list            = json_decode($server_list_json, true);
            $server_list[$serverid] = array(
                'serverid' => $serverid,
                'tasknum'  => $tasknum,
                'time'     => time(),
            );
            ksort($server_list);
        }
        queue::set('server_list', json_encode($server_list));
    }

    /**
     * 从服务器列表中删除当前服务器信息
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function del_server_list($serverid)
    {
        if (!self::$use_redis) 
        {
            return false;
        }

        $server_list_json = queue::get('server_list');
        $server_list      = array();
        if ($server_list_json)
        {
            $server_list = json_decode($server_list_json, true);
            if (isset($server_list[$serverid])) 
            {
                unset($server_list[$serverid]);
            }

            // 删除完当前的任务列表如果还存在，就更新一下Redis
            if (!empty($server_list)) 
            {
                ksort($server_list);
                queue::set('server_list', json_encode($server_list));
            }
        }
    }

    /**
     * 获取等待爬取页面数量
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function get_collect_url_num()
    {
        if (self::$use_redis)
        {
            $count = queue::get('collect_urls_num');
        }
        else 
        {
            $count = self::$collect_urls_num;
        }
        return $count;
    }

    /**
     * 获取已经爬取页面数量
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function get_collected_url_num()
    {
        if (self::$use_redis)
        {
            $count = queue::get('collected_urls_num');
        }
        else 
        {
            $count = self::$collected_urls_num;
        }
        return $count;
    }

    /**
     * 已采集页面数量加一
     * 
     * @param mixed $url
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function incr_collected_url_num($url)
    {
        if (self::$use_redis)
        {
            queue::incr('collected_urls_num');
        }
        else 
        {
            self::$collected_urls_num++;
        }
    }

    /**
     * 从队列左边插入
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-23 17:13
     */
    public function queue_lpush($link = array(), $allowed_repeat = false)
    {
        if (empty($link) || empty($link['url'])) 
        {
            return false;
        }

        $url = $link['url'];
        $link = $this->link_compress($link);

        $status = false;
        if (self::$use_redis)
        {
            $key  = 'collect_urls-'.md5($url);
            $lock = 'lock-'.$key;
            // 加锁: 一个进程一个进程轮流处理
            if (queue::lock($lock))
            {
                $exists = queue::exists($key); 
                // 不存在或者当然URL可重复入
                if (!$exists || $allowed_repeat) 
                {
                    // 待爬取网页记录数加一
                    queue::incr('collect_urls_num');
                    // 先标记为待爬取网页
                    queue::set($key, time()); 
                    // 入队列
                    $link = json_encode($link);
                    //根据采集设置为顺序采集还是随机采集，使用列表或集合对象 2018-5 BY KEN <a-site@foxmail.com>
                    if (self::$configs['queue_order'] == 'rand')
                    {
                        queue::sadd('collect_queue', $link);
                    }
                    else
                    {
                        queue::lpush('collect_queue', $link);
                    }
                    $status = true;
                }
                // 解锁
                queue::unlock($lock);
            }
        }
        else 
        {
            $key = md5($url);
            if (!array_key_exists($key, self::$collect_urls))
            {
                self::$collect_urls_num++;
                self::$collect_urls[$key] = time();
                array_push(self::$collect_queue, $link);
                $status = true;
            }
        }
        return $status;
    }

    /**
     * 从队列右边插入
     *
     * @return void
     * @author seatle <seatle@foxmail.com>
     * @created time :2016-09-23 17:13
     */
    public function queue_rpush($link = array(), $allowed_repeat = false)
    {
        if (empty($link) || empty($link['url'])) 
        {
            return false;
        }

        $url = $link['url'];

        $status = false;
        if (self::$use_redis)
        {
            $key  = 'collect_urls-'.md5($url);
            $lock = 'lock-'.$key;
            // 加锁: 一个进程一个进程轮流处理
            if (queue::lock($lock))
            {
                $exists = queue::exists($key);
                // 不存在或者当然URL可重复入
                if ( ! $exists || $allowed_repeat)
                {
                    // 待爬取网页记录数加一
                    queue::incr('collect_urls_num');
                    // 先标记为待爬取网页
                    queue::set($key, time());
                    // 入队列
                    $link = json_encode($link);
                    //根据采集设置为顺序采集还是随机采集，使用列表或集合对象 2018-5 BY KEN <a-site@foxmail.com>
                    if (self::$configs['queue_order'] == 'rand')
                    {
                        queue::sadd('collect_queue', $link); //无序集合
                    }
                    else
                    {
                        queue::rpush('collect_queue', $link); //有序列表
                    }
                    $status = true;
                }
                // 解锁
                queue::unlock($lock);
            }
        }
        else 
        {
            $key = md5($url);
            if (!array_key_exists($key, self::$collect_urls))
            {
                self::$collect_urls_num++;
                self::$collect_urls[$key] = time();
                array_unshift(self::$collect_queue, $link);
                $status = true;
            }
        }
        return $status;
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
        if (self::$use_redis)
        {
            //根据采集设置为顺序采集还是随机采集，使用列表或集合对象
            if (self::$configs['queue_order'] == 'rand')
            {
                $link = queue::spop('collect_queue');
            }
            else
            {
                $link = queue::lpop('collect_queue');
            }
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
        if (self::$use_redis)
        {
            //根据采集设置为顺序采集还是随机采集，使用列表或集合对象
            if (self::$configs['queue_order'] == 'rand')
            {
                $link = queue::spop('collect_queue');
            }
            else
            {
                $link = queue::rpop('collect_queue');
            }
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
        if (self::$use_redis)
        {
            //根据采集设置为顺序采集还是随机采集，使用列表或集合对象
            if (self::$configs['queue_order'] == 'rand')
            {
                $lsize = queue::scard('collect_queue');
            }
            else
            {
                $lsize = queue::lsize('collect_queue');
            }
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
        if (self::$use_redis)
        {
            $lock = 'lock-depth_num';
            // 锁2秒
            if (queue::lock($lock, time(), 2))
            {
                if (queue::get('depth_num') < $depth)
                {
                    queue::set('depth_num', $depth);
                }

                queue::unlock($lock);
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
        if (self::$use_redis)
        {
            $depth_num = queue::get('depth_num');
            return $depth_num ? $depth_num : 0;
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
        if (self::$use_redis)
        {
            $fields_num = queue::incr('fields_num');
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
        if (self::$use_redis)
        {
            $fields_num = queue::get('fields_num');
        }
        else 
        {
            $fields_num = self::$fields_num;
        }
        return $fields_num ? $fields_num : 0;
    }

    /**
     * 提取到的pages数目加一，用于限制单域名采集页数上限
     *
     * @return void
     * @author KEN <a-site@foxmail.com>
     * @created time :2018-05
     */
    public function incr_pages_num($url = '')
    {
        if ( ! empty($url))
        {
            $domain = $this->getRootDomain($url, 'host');
        }
        if (empty($domain))
        {
            $domain = 'all';
        }
        if (self::$use_redis)
        {
            $pages_num[$domain] = queue::incr('pages_num:'.$domain);
        }
        else
        {
            if (empty(self::$pages_num[$domain]))
            {
                self::$pages_num[$domain] = 1;
            }
            else
            {
                self::$pages_num[$domain]++;
            }
            $pages_num[$domain] = self::$pages_num[$domain];
        }
        return $pages_num[$domain];
    }

    /**
     * 超过1秒的慢速采集时间计数，用于限制单域名总采集时间上限
     *
     * @return void
     * @author KEN <a-site@foxmail.com>
     * @created time :2018-05
     */
    public function incr_duration_num($url = '', $time_run = 1)
    {
        if ( ! empty($url))
        {
            $domain = $this->getRootDomain($url);
        }
        if (empty($domain))
        {
            $domain = 'all';
        }
        if (self::$use_redis)
        {
            $duration[$domain] = queue::incr('duration:'.$domain, $time_run);
        }
        else
        {
            if (empty(self::$duration[$domain]))
            {
                self::$duration[$domain] = $time_run;
            }
            else
            {
                self::$duration[$domain] += $time_run;
            }
            $duration[$domain] = self::$duration[$domain];
        }
        return $duration[$domain];
    }

    /**
     * 读取单域名总慢速采集（响应超过1秒）的时间
     *
     * @return void
     * @author KEN <a-site@foxmail.com>
     * @created time :2018-04
     */
    public function get_duration_num($url = '')
    {
        if ( ! empty($url))
        {
            $domain = $this->getRootDomain($url);
        }
        if (empty($domain))
        {
            $domain = 'all';
        }
        if (self::$use_redis)
        {
            $duration[$domain] = queue::get('duration:'.$domain);
        }
        else
        {
            $duration[$domain] =  ! empty(self::$duration[$domain]) ? self::$duration[$domain] : 0;
        }
        return $duration[$domain] ? $duration[$domain] : 0;
    }

    /**
     * 单 host 当前并发计数
     * @return int
     * @author KEN <a-site@foxmail.com>
     * @created time :2018-05-28 16:40
     */
    public function incr_task_per_host($url = '', $type = 'incr')
    {
        if (empty($url))
        {
            return false;
        }
        $domain = $this->getRootDomain($url, 'host');
        if (empty($domain))
        {
            return false;
        }
        if (self::$use_redis)
        {
            if ($type == 'decr')
            {
                $task_per_host_counter[$domain] = queue::decr('task_per_host:'.$domain);
            }
            else
            {
                $task_per_host_counter[$domain] = queue::incr('task_per_host:'.$domain);
            }
        }
        else
        {

            if (empty(self::$task_per_host_counter[$domain]))
            {
                self::$task_per_host_counter[$domain] = 1;
            }
            else
            {
                if ($type == 'decr')
                {
                    self::$task_per_host_counter[$domain]--;
                }
                else
                {
                    self::$task_per_host_counter[$domain]++;
                }
            }
            $task_per_host_counter[$domain] = self::$task_per_host_counter[$domain];
        }
        return $task_per_host_counter[$domain];
    }

    //获取url所属 host 当前并发数量 KEN <a-site@foxmail.com>
    public function get_task_per_host_num($url)
    {
        if (empty($url))
        {
            return 0;
        }
        $domain = $this->getRootDomain($url, 'host');
        if (empty($domain))
        {
            return 0;
        }
        if (self::$use_redis)
        {
            $count = queue::get('task_per_host:'.$domain);
        }
        else
        {
            $count = self::$task_per_host_counter[$domain];
        }
        return $count;
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
        $result = selector::select($html, $selector);
        if (selector::$error) 
        {
            log::error("Field(\"{$fieldname}\") ".selector::$error."\n");
        }
        return $result;
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
        $result = selector::select($html, $selector, 'regex');
        if (selector::$error) 
        {
            log::error("Field(\"{$fieldname}\") ".selector::$error."\n");
        }
        return $result;
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
        $result = selector::select($html, $selector, 'css');
        if (selector::$error) 
        {
            log::error("Field(\"{$fieldname}\") ".selector::$error."\n");
        }
        return $result;
    }

    /**
     * 清空shell输出内容
     * 
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function clear_echo()
    {
        $arr = array(27, 91, 72, 27, 91, 50, 74);
        foreach ($arr as $a) 
        {
            print chr($a);
        }
        //array_map(create_function('$a', 'print chr($a);'), array(27, 91, 72, 27, 91, 50, 74));
    }

    /**
     * 替换shell输出内容
     * 
     * @param mixed $message
     * @param mixed $force_clear_lines
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-11-16 11:06
     */
    public function replace_echo($message, $force_clear_lines = NULL) 
    {
        static $last_lines = 0;

        if(!is_null($force_clear_lines)) 
        {
            $last_lines = $force_clear_lines;
        }

        // 获取终端宽度
        $toss = $status = null;
        $term_width = exec('tput cols', $toss, $status);
        if($status || empty($term_width)) 
        {
            $term_width = 64; // Arbitrary fall-back term width.
        }

        $line_count = 0;
        foreach(explode("\n", $message) as $line) 
        {
            $line_count += count(str_split($line, $term_width));
        }

        // Erasure MAGIC: Clear as many lines as the last output had.
        for($i = 0; $i < $last_lines; $i++) 
        {
            // Return to the beginning of the line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Move cursor Up a line
            echo "\033[1A";
            // Return to the beginning of the line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Return to the beginning of the line
            echo "\r";
            // Can be consolodated into
            // echo "\r\033[K\033[1A\r\033[K\r";
        }

        $last_lines = $line_count;

        echo $message."\n";
    }

    /**
     * 展示启动界面, Windows 不会到这里来
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
        //$display_str = "-----------------------------\033[47;30m PHPSPIDER \033[0m-----------------------------\n\033[0m";
        $run_time_str = util::time2second(time() - self::$time_start, false);
        $display_str .= 'PHPSpider version:'.self::VERSION.'          PHP version:'.PHP_VERSION."\n";
        $display_str .= 'start time:'.date('Y-m-d H:i:s', self::$time_start).'   run '.$run_time_str." \n";

        $display_str .= 'spider name: '.self::$configs['name']."\n";
        if (self::$multiserver)
        {
            $display_str .= 'server id: '.self::$serverid."\n";
        }
        $display_str .= 'task number: '.self::$tasknum."\n";
        $display_str .= 'load average: '.implode(', ', $loadavg)."\n";
        $display_str .= "document: https://doc.phpspider.org\n";

        $display_str .= $this->display_task_ui();

        if (self::$multiserver) 
        {
            $display_str .= $this->display_server_ui();
        }

        $display_str .= $this->display_collect_ui();

        // 清屏
        //$this->clear_echo();
        // 返回到第一行,第一列
        //echo "\033[0;0H";
        $display_str .= "---------------------------------------------------------------------\n";
        $display_str .= 'Press Ctrl-C to quit. Start success.'.date('Y-m-d H:i:s').' - '.round(memory_get_usage() / 1024 / 1024, 2).'MB'."\n";
        if (self::$terminate)
        {
            $display_str .= "\n\033[33mWait for the process exits...\033[0m";
        }
        //echo $display_str;
        $this->replace_echo($display_str);
    }

    public function display_task_ui()
    {
        $display_str = "-------------------------------\033[47;30m TASKS \033[0m-------------------------------\n";

        $display_str .= "\033[47;30mtaskid\033[0m". str_pad('', self::$taskid_length+2-strlen('taskid')). 
            "\033[47;30mtaskpid\033[0m". str_pad('', self::$pid_length+2-strlen('taskpid')). 
            "\033[47;30mmem\033[0m". str_pad('', self::$mem_length+2-strlen('mem')). 
            "\033[47;30mcollect succ\033[0m". str_pad('', self::$urls_length-strlen('collect succ')). 
            "\033[47;30mcollect fail\033[0m". str_pad('', self::$urls_length-strlen('collect fail')). 
            "\033[47;30mspeed\033[0m". str_pad('', self::$speed_length+2-strlen('speed')). 
            "\n";

        // "\033[32;40m [OK] \033[0m"
        $task_status = $this->get_task_status_list(self::$serverid, self::$tasknum);
        foreach ($task_status as $json) 
        {
            $task = json_decode($json, true);
            if (empty($task)) 
            {
                continue;
            }
            $display_str .= str_pad($task['id'], self::$taskid_length + 2).
            str_pad($task['pid'], self::$pid_length + 2).
            str_pad($task['mem'].'MB', self::$mem_length + 2).
            str_pad($task['collect_succ'], self::$urls_length).
            str_pad($task['collect_fail'], self::$urls_length).
            str_pad($task['speed'].'/s', self::$speed_length + 2).
                "\n";
        }
        //echo "\033[9;0H";
        return $display_str;
    }

    public function display_server_ui()
    {
        $display_str = "-------------------------------\033[47;30m SERVER \033[0m------------------------------\n";

        $display_str .= "\033[47;30mserver\033[0m". str_pad('', self::$server_length+2-strlen('serverid')). 
            "\033[47;30mtasknum\033[0m". str_pad('', self::$tasknum_length+2-strlen('tasknum')). 
            "\033[47;30mmem\033[0m". str_pad('', self::$mem_length+2-strlen('mem')). 
            "\033[47;30mcollect succ\033[0m". str_pad('', self::$urls_length-strlen('collect succ')). 
            "\033[47;30mcollect fail\033[0m". str_pad('', self::$urls_length-strlen('collect fail')). 
            "\033[47;30mspeed\033[0m". str_pad('', self::$speed_length+2-strlen('speed')). 
            "\n";

        $server_list_json = queue::get('server_list');
        $server_list      = json_decode($server_list_json, true);
        foreach ($server_list as $server)
        {
            $serverid     = $server['serverid'];
            $tasknum      = $server['tasknum'];
            $mem          = 0;
            $speed        = 0;
            $collect_succ = $collect_fail = 0;
            $task_status  = $this->get_task_status_list($serverid, $tasknum);
            foreach ($task_status as $json)
            {
                $task = json_decode($json, true);
                if (empty($task))
                {
                    continue;
                }
                $mem += $task['mem'];
                $speed += $task['speed'];
                $collect_fail += $task['collect_fail'];
                $collect_succ += $task['collect_succ'];
            }

            $display_str .= str_pad($serverid, self::$server_length).
            str_pad($tasknum, self::$tasknum_length + 2).
            str_pad($mem.'MB', self::$mem_length + 2).
            str_pad($collect_succ, self::$urls_length).
            str_pad($collect_fail, self::$urls_length).
            str_pad($speed.'/s', self::$speed_length + 2).
                "\n";
        }
        return $display_str;
    }

    public function display_collect_ui()
    {
        $display_str = "---------------------------\033[47;30m COLLECT STATUS \033[0m--------------------------\n";

        $display_str .= "\033[47;30mfind pages\033[0m". str_pad('', 16-strlen('find pages')). 
            "\033[47;30mqueue\033[0m". str_pad('', 14-strlen('queue')). 
            "\033[47;30mcollected\033[0m". str_pad('', 15-strlen('collected')). 
            "\033[47;30mfields\033[0m". str_pad('', 15-strlen('fields')). 
            "\033[47;30mdepth\033[0m". str_pad('', 12-strlen('depth')). 
            "\n";

        $collect   = $this->get_collect_url_num();
        $collected = $this->get_collected_url_num();
        $queue     = $this->queue_lsize();
        $fields    = $this->get_fields_num();
        $depth     = $this->get_depth_num();
        $display_str .= str_pad($collect, 16);
        $display_str .= str_pad($queue, 14);
        $display_str .= str_pad($collected, 15);
        $display_str .= str_pad($fields, 15);
        $display_str .= str_pad($depth, 12);
        $display_str .= "\n";
        return $display_str;
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
    //// 代理和Cookie以后实现, 方法和 file_get_contents 一样 使用 stream_context_create 设置
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
    //// 判断一下是不是文件名被加什么后缀了, 比如 http://www.xxxx.com/test.jpg?token=xxxxx
    //if (!isset($mime_types_flip[$fileinfo['fileext']]))
    //{
    //$fileinfo['fileext'] = $mime_type;
    //$fileinfo['basename'] = $fileinfo['filename'].'.'.$mime_type;
    //}
    //}
    //return $fileinfo;
    //}

    //返回当前是否是主进程
    public function is_taskmaster()
    {
        return self::$taskmaster;
    }

    //返回当前是否进程ID
    public function get_task_id()
    {
        return self::$taskid;
    }

    //检测子域名数量
    public function sub_domain_count($url)
    {
        if (empty($url))
        {
            return 0;
        }
        $count  = 0;
        $domain = $this->getRootDomain($url, 'root');
        if (empty($domain))
        {
            return 0;
        }
        $host = $this->getRootDomain($url, 'host');
        if (empty($host))
        {
            return $count;
        }
        if (self::$use_redis)
        {
            $count = queue::get($domain);
            if ( ! empty(self::$configs['max_sub_num']) and $count > self::$configs['max_sub_num'])
            {
                return $count;
            }
            if (strlen($host) > 32)
            {
                $host = md5($host);
            }
            $hostkey = 'sub_d-'.$host;
            $exists  = queue::exists($hostkey);
            if ( ! $exists)
            {
                // 子域名数量加一
                $count = queue::incr($domain);
                queue::set($hostkey, 1);
            }
        }
        return $count;
    }

    //提取url的根域名 host domain subdomain name tld
    public function getRootDomain($url = '', $type = 'root', $domain_check = false)
    {
        if (empty($url))
        {
            return $url;
        }
        $url = trim($url);
        if ( ! preg_match('/^http/i', $url))
        {
            $url = 'http://'.$url;
        }
        //截取限定字符
        $arr = array();
        if (preg_match_all('/(^https?:\/\/[\p{Han}a-zA-Z0-9\-\.\/]+)/iu', $url, $arr))
        {
            $url = $arr['0']['0'];
            unset($arr);
        }
        $url_parse = parse_url(strtolower($url));
        if (empty($url_parse['host']))
        {
            return '';
        }
        //host判断快速返回
        if ($domain_check === false and $type == 'host')
        {
            return $url_parse['host'];
        }

        //结束数组初始化
        $res = array(
            'scheme' => '',
            'host'   => '',
            'path'   => '',
            'name'   => '',
            'domain' => '',
        );

        $urlarr        = explode('.', $url_parse['host']);
        $count         = count($urlarr);
        $res['scheme'] = $url_parse['scheme'];
        $res['host']   = $url_parse['host'];
        if ( ! empty($url_parse['path']))
        {
            $res['path'] = $url_parse['path'];
        }
        #列举域名中固定元素
        $state_domain = array('com', 'edu', 'gov', 'int', 'mil', 'net', 'org', 'biz', 'info', 'pro', 'name', 'coop', 'aero', 'xxx', 'idv', 'mobi', 'cc', 'me', 'jp', 'uk', 'ws', 'eu', 'pw', 'kr', 'io', 'us', 'cn', 'al', 'dz', 'af', 'ar', 'ae', 'aw', 'om', 'az', 'eg', 'et', 'ie', 'ee', 'ad', 'ao', 'ai', 'ag', 'at', 'au', 'mo', 'bb', 'pg', 'bs', 'pk', 'py', 'ps', 'bh', 'pa', 'br', 'by', 'bm', 'bg', 'mp', 'bj', 'be', 'is', 'pr', 'ba', 'pl', 'bo', 'bz', 'bw', 'bt', 'bf', 'bi', 'bv', 'kp', 'gq', 'dk', 'de', 'tl', 'tp', 'tg', 'dm', 'do', 'ru', 'ec', 'er', 'fr', 'fo', 'pf', 'gf', 'tf', 'va', 'ph', 'fj', 'fi', 'cv', 'fk', 'gm', 'cg', 'cd', 'co', 'cr', 'gg', 'gd', 'gl', 'ge', 'cu', 'gp', 'gu', 'gy', 'kz', 'ht', 'nl', 'an', 'hm', 'hn', 'ki', 'dj', 'kg', 'gn', 'gw', 'ca', 'gh', 'ga', 'kh', 'cz', 'zw', 'cm', 'qa', 'ky', 'km', 'ci', 'kw', 'hr', 'ke', 'ck', 'lv', 'ls', 'la', 'lb', 'lt', 'lr', 'ly', 'li', 're', 'lu', 'rw', 'ro', 'mg', 'im', 'mv', 'mt', 'mw', 'my', 'ml', 'mk', 'mh', 'mq', 'yt', 'mu', 'mr', 'um', 'as', 'vi', 'mn', 'ms', 'bd', 'pe', 'fm', 'mm', 'md', 'ma', 'mc', 'mz', 'mx', 'nr', 'np', 'ni', 'ne', 'ng', 'nu', 'no', 'nf', 'na', 'za', 'aq', 'gs', 'pn', 'pt', 'se', 'ch', 'sv', 'yu', 'sl', 'sn', 'cy', 'sc', 'sa', 'cx', 'st', 'sh', 'kn', 'lc', 'sm', 'pm', 'vc', 'lk', 'sk', 'si', 'sj', 'sz', 'sd', 'sr', 'sb', 'so', 'tj', 'tw', 'th', 'tz', 'to', 'tc', 'tt', 'tn', 'tv', 'tr', 'tm', 'tk', 'wf', 'vu', 'gt', 've', 'bn', 'ug', 'ua', 'uy', 'uz', 'es', 'eh', 'gr', 'hk', 'sg', 'nc', 'nz', 'hu', 'sy', 'jm', 'am', 'ac', 'ye', 'iq', 'ir', 'il', 'it', 'in', 'id', 'vg', 'jo', 'vn', 'zm', 'je', 'td', 'gi', 'cl', 'cf', 'yr', 'arpa', 'museum', 'asia', 'ax', 'bl', 'bq', 'cat', 'cw', 'gb', 'jobs', 'mf', 'rs', 'su', 'sx', 'tel', 'travel', 'shop', 'ltd', 'store', 'vip', '网店', '中国', '公司', '网络', 'co.il', 'co.nz', 'co.uk', 'me.uk', 'org.uk', 'com.sb', '在线', '中文网', '移动', 'wang', 'club', 'ren', 'top', 'website', 'cool', 'company', 'city', 'email', 'market', 'software', 'ninja', '我爱你', 'bike', 'today', 'life', 'space', 'pub', 'site', 'help', 'link', 'photo', 'video', 'click', 'pics', 'sexy', 'audio', 'gift', 'tech', '网址', 'online', 'win', 'download', 'party', 'bid', 'loan', 'date', 'trade', 'red', 'blue', 'pink', 'poker', 'green', 'farm', 'zone', 'guru', 'tips', 'land', 'care', 'camp', 'cab', 'cash', 'limo', 'toys', 'tax', 'town', 'fish', 'fund', 'fail', 'house', 'shoes', 'media', 'guide', 'tools', 'solar', 'watch', 'cheap', 'rocks', 'news', 'live', 'lawyer', 'host', 'wiki', 'ink', 'design', 'lol', 'hiphop', 'hosting', 'diet', 'flowers', 'car', 'cars', 'auto', 'mom', 'cq', 'he', 'nm', 'ln', 'jl', 'hl', 'js', 'zj', 'ah', 'jx', 'ha', 'hb', 'gx', 'hi', 'gz', 'yn', 'xz', 'qh', 'nx', 'xj', 'xyz', 'xin', 'science', 'press', 'band', 'engineer', 'social', 'studio', 'work', 'game', 'kim', 'games', 'group', '集团');
        if ($count <= 2)
        {
            #当域名直接根形式不存在host部分直接输出
            $last   = array_pop($urlarr);
            $last_1 = array_pop($urlarr);
            if (in_array($last, $state_domain))
            {
                $res['domain'] = $last_1.'.'.$last;
                $res['name']   = $last_1;
                $res['tld']    = $last;
            }
        }
        elseif ($count > 2)
        {
            $last          = array_pop($urlarr);
            $last_1        = array_pop($urlarr);
            $last_2        = array_pop($urlarr);
            $res['domain'] = $last_1.'.'.$last; //默认为n.com形式
            $res['name']   = $last_2;

            //排除非标准 ltd 域名
            if ( ! in_array($last, $state_domain))
            {
                return false;
            }

            if (in_array($last, $state_domain))
            {
                $res['domain'] = $last_1.'.'.$last; //n.com形式
                $res['name']   = $last_1;
                $res['tld']    = $last;
            }
            //排除顶级根二级后缀
            if ($last_1 !== $last and in_array($last_1, $state_domain) and ! in_array($last, array('com', 'net', 'org', 'edu', 'gov')))
            {
                $res['domain'] = $last_2.'.'.$last_1.'.'.$last; //n.n.com形式
                $res['name']   = $last_2;
                $res['tld']    = $last_1.'.'.$last;
            }
            //限定cn顶级根二级后缀为'com', 'net', 'org', 'edu', 'gov'
            if (in_array($last, array('cn')) and $last_1 !== $last and strlen($last_1) > 2 and ! in_array($last_1, array('com', 'net', 'org', 'edu', 'gov')))
            {
                $res['domain'] = $last_1.'.'.$last; //n.n.cn形式
                $res['name']   = $last_1;
                $res['tld']    = $last;
            }
        }

        //检测和验证返回的是不是域名格式
        if ( ! empty($res['domain']) and preg_match('/^([\p{Han}a-zA-Z0-9])+([\p{Han}a-zA-Z0-9\-])*\.[a-zA-Z\.\p{Han}]+$/iu', $res['domain']))
        {
            if ($type == 'arr')
            {
                return $res;
            }
            elseif ($type == 'host')
            {
                return $res['host'];
            }
            elseif ($type == 'tld')
            {
                return $res['tld'];
            }
            elseif ($type == 'subdomain')
            {
                return $res['name'];
            }
            else
            {
                return $res['domain'];
            }
        }
        else
        {
            return '';
        }
    }

}
