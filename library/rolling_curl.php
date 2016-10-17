<?php
/**
 * Curl操作类
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

class rolling_curl
{
    /**
     * @var float
     *
     * 同时运行任务数
     * 例如：有8个请求，则会被分成两批，第一批5个请求，第二批3个请求
     * 注意：采集知乎的时候，5个是比较稳定的，7个以上就开始会超时了，多进程就没有这样的问题，因为多进程很少几率会发生并发
     */
    public $window_size = 5;

    /**
     * @var float
     *
     * Timeout is the timeout used for curl_multi_select.
     */
    private $timeout = 10;

    /**
     * @var string|array
     *
     * 应用在每个请求的回调函数
     */
    public $callback;

    /**
     * @var array
     *
     * 设置默认的请求参数
     */
    protected $options = array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        // 注意：TIMEOUT = CONNECTTIMEOUT + 数据获取时间，所以 TIMEOUT 一定要大于 CONNECTTIMEOUT，否则 CONNECTTIMEOUT 设置了就没意义 
        // "Connection timed out after 30001 milliseconds"
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
        // 在多线程处理场景下使用超时选项时，会忽略signals对应的处理函数，但是无耐的是还有小概率的crash情况发生
        CURLOPT_NOSIGNAL => 1,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36",
    );

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var Request[]
     *
     * 请求队列
     */
    private $requests = array();

    /**
     * @var RequestMap[]
     *
     * Maps handles to request indexes
     */
    private $requestMap = array();

    public function __construct()
    {
    }

    /**
     * set timeout
     *
     * @param init $timeout
     * @return
     */
    public function set_timeout($timeout)
    {
        $this->options[CURLOPT_TIMEOUT] = $timeout;
    }

    /**
     * set proxy
     *
     */
    public function set_proxy($proxy)
    {
        $this->options[CURLOPT_PROXY] = $proxy;
    }

    /**
     * set referer
     *
     */
    public function set_referer($referer)
    {
        $this->options[CURLOPT_REFERER] = $referer;
    }

    /**
     * 设置 user_agent
     *
     * @param string $useragent
     * @return void
     */
    public function set_useragent($useragent)
    {
        $this->options[CURLOPT_USERAGENT] = $useragent;
    }

    /**
     * 设置COOKIE
     *
     * @param string $cookie
     * @return void
     */
    public function set_cookie($cookie)
    {
        $this->options[CURLOPT_COOKIE] = $cookie;
    }

    /**
     * 设置COOKIE JAR
     *
     * @param string $cookie_jar
     * @return void
     */
    public function set_cookiejar($cookiejar)
    {
        $this->options[CURLOPT_COOKIEJAR] = $cookiejar;
    }

    /**
     * 设置COOKIE FILE
     *
     * @param string $cookie_file
     * @return void
     */
    public function set_cookiefile($cookiefile)
    {
        $this->options[CURLOPT_COOKIEFILE] = $cookiefile;
    }

    /**
     * 获取内容的时候是不是连header也一起获取
     * 
     * @param mixed $http_raw
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public function set_http_raw($http_raw = false)
    {
        $this->options[CURLOPT_HEADER] = $http_raw;
    }

    /**
     * 设置IP
     *
     * @param string $ip
     * @return void
     */
    public function set_ip($ip)
    {
        $headers = array(
            'CLIENT-IP'=>$ip,
            'X-FORWARDED-FOR'=>$ip,
        );
        $this->headers = $this->headers + $headers;
    }

    /**
     * 设置Headers
     *
     * @param string $headers
     * @return void
     */
    public function set_headers($headers)
    {
        $this->headers = $this->headers + $headers;
    }

    /**
     * 设置Hosts
     *
     * @param string $hosts
     * @return void
     */
    public function set_hosts($hosts)
    {
        $headers = array(
            'Host'=>$hosts,
        );
        $this->headers = $this->headers + $headers;
    }

    /**
     * 设置Gzip
     *
     * @param string $hosts
     * @return void
     */
    public function set_gzip($gzip)
    {
        if ($gzip) 
        {
            $this->options[CURLOPT_ENCODING] = 'gzip';
        }
    }

    public function request($url, $method = "GET", $fields = array(), $headers = array(), $options = array())
    {
        $this->requests[] = array('url'=>$url,'method'=>$method,'fields'=>$fields,'headers'=>$headers,'options'=>$options);
        return true;
    }

    public function get_options($request)
    {
        $options = $this->options;
        $headers = $this->headers;

        if (ini_get('safe_mode') == 'Off' || !ini_get('safe_mode')) 
        {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
            $options[CURLOPT_MAXREDIRS] = 5;
        }

        // 如果是 get 方式，直接拼凑一个 url 出来
        if (strtolower($request['method']) == 'get' && !empty($request['fields'])) 
        {
            $url = $request['url'] . "?" . http_build_query($request['fields']);
        }
        // 如果是 post 方式
        if (strtolower($request['method']) == 'post')
        {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = $request['fields'];
        }

        // append custom options for this specific request
        if ($request['options']) 
        {
            $options = $request['options'] + $options;
        }

        if ($request['headers']) 
        {
            $headers = $request['headers'] + $headers;
        }

        // 随机绑定 hosts，做负载均衡
        //if (self::$hosts) 
        //{
            //$parse_url = parse_url($url);
            //$host = $parse_url['host'];
            //$key = rand(0, count(self::$hosts)-1);
            //$ip = self::$hosts[$key];
            //$url = str_replace($host, $ip, $url);
            //self::$headers = array_merge( array('Host:'.$host), self::$headers );
        //}

        // header 要这样拼凑
        $headers_tmp = array();
        foreach ($headers as $k=>$v) 
        {
            $headers_tmp[] = $k.":".$v;
        }
        $headers = $headers_tmp;

        $options[CURLOPT_URL] = $request['url'];
        $options[CURLOPT_HTTPHEADER] = $headers;

        return $options;
    }

    /**
     * GET 请求
     *
     * @param string $url
     * @param array $headers
     * @param array $options
     * @return bool
     */
    public function get($url, $fields = array(), $headers = array(), $options = array())
    {
        return $this->request($url, 'get', $fields, $headers, $options);
    }

    /**
     * $fields 有三种类型:1、数组；2、http query；3、json
     * 1、array('name'=>'yangzetao') 2、http_build_query(array('name'=>'yangzetao')) 3、json_encode(array('name'=>'yangzetao'))
     * 前两种是普通的post，可以用$_POST方式获取
     * 第三种是post stream( json rpc，其实就是webservice )，虽然是post方式，但是只能用流方式 http://input 后者 $HTTP_RAW_POST_DATA 获取 
     * 
     * @param string $url 
     * @param array $fields 
     * @param array $headers
     * @param array $options
     * @return void
     */
    public function post($url, $fields = array(), $headers = array(), $options = array())
    {
        return $this->request($url, 'post', $fields, $headers, $options);
    }

    /**
     * Execute processing
     *
     * @param int $window_size Max number of simultaneous connections
     * @return string|bool
     */
    public function execute($window_size = null) 
    {
        $count = sizeof($this->requests);
        if ($count == 0) 
        {
            return false;
        }
        // 只有一个请求
        elseif ($count == 1) 
        {
            return $this->single_curl();
        }
        else 
        {
            // 开始 rolling curl，window_size 是最大同时连接数
            return $this->rolling_curl($window_size);
        }
    }

    private function single_curl() 
    {
        $ch = curl_init();
        // 从请求队列里面弹出一个来
        $request = array_shift($this->requests);
        $options = $this->get_options($request);
        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = null;
        if ($output === false)
        {
            $error = curl_error( $ch );
        }
        //$output = substr($output, 10);
        //$output = gzinflate($output);

        // 其实一个请求的时候没是么必要回调，直接返回数据就好了，不过这里算是多一个功能吧，和多请求保持一样的操作
        if ($this->callback)
        {
            if (is_callable($this->callback))
            {
                call_user_func($this->callback, $output, $info, $request, $error);
            }
        }
        else
        {
            return $output;
        }
        return true;
    }

    private function rolling_curl($window_size = null) 
    {
        // 如何设置了最大任务数
        if ($window_size)
            $this->window_size = $window_size;

        // 如果请求数 小于 任务数，设置任务数为请求数
        if (sizeof($this->requests) < $this->window_size)
            $this->window_size = sizeof($this->requests);

        // 如果任务数小于2个，不应该用这个方法的，用上面的single_curl方法就好了
        if ($this->window_size < 2) 
            exit("Window size must be greater than 1");

        // 初始化任务队列
        $master = curl_multi_init();

        // 开始第一批请求
        for ($i = 0; $i < $this->window_size; $i++)
        {
            $ch = curl_init();
            $options = $this->get_options($this->requests[$i]);
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($master, $ch);
            // 添加到请求数组
            $key = (string) $ch;
            $this->requestMap[$key] = $i;
        }

        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM) ;

            // 如果
            if ($execrun != CURLM_OK) { break; }

            // 一旦有一个请求完成，找出来，因为curl底层是select，所以最大受限于1024
            while ($done = curl_multi_info_read($master)) 
            {
                // 从请求中获取信息、内容、错误
                $info = curl_getinfo($done['handle']);
                $output = curl_multi_getcontent($done['handle']);
                $error = curl_error($done['handle']);

                // 如果绑定了回调函数
                $callback = $this->callback;
                if (is_callable($callback)) 
                {
                    $key = (string) $done['handle'];
                    $request = $this->requests[$this->requestMap[$key]];
                    unset($this->requestMap[$key]);
                    call_user_func($callback, $output, $info, $request, $error);
                }

                // 一个请求完了，就加一个进来，一直保证5个任务同时进行
                if ($i < sizeof($this->requests) && isset($this->requests[$i]) && $i < count($this->requests))
                {
                    $ch = curl_init();
                    $options = $this->get_options($this->requests[$i]);
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);

                    // 添加到请求数组
                    $key = (string) $ch;
                    $this->requestMap[$key] = $i;
                    $i++;
                }
                // 把请求已经完成了得 curl handle 删除
                curl_multi_remove_handle($master, $done['handle']);
            }

            // 当没有数据的时候进行堵塞，把 CPU 使用权交出来，避免上面 do 死循环空跑数据导致 CPU 100%
            if ($running)
            {
                curl_multi_select($master, $this->timeout);
            }

        } while ($running);
        // 关闭任务
        curl_multi_close($master);

        // 把请求清空，否则没有重新 new rolling_curl(); 直接再次导入一批url的时候，就会把前面已经执行过的url又执行一轮
        unset($this->requests);
        return true;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        unset($this->window_size, $this->callback, $this->options, $this->headers, $this->requests);
    }
}
