<?php

/**
 * phpspider - A PHP Framework For Crawler
 *
 * @package  requests
 * @author   Seatle Yang <seatle@foxmail.com>
 */

class requests
{
    /**
     * 版本号
     * @var string
     */
    const VERSION = '1.10.0';

    protected static $ch = null;
    protected static $timeout = 10;
    protected static $headers = array();
    protected static $proxies = array();
    protected static $cookies = array();
    protected static $hosts = array();
    public static $url = null;
    public static $text = null;
    public static $raw = null;
    public static $encoding = 'utf-8';
    public static $info = array();
    public static $status_code = 0;

    /**
     * set timeout
     *
     * @param init $timeout
     * @return
     */
    public static function set_timeout($timeout)
    {
        self::$timeout = $timeout;
    }

    /**
     * 设置代理
     * 
     * @param mixed $proxies
     * array (
     *    'http': 'socks5://user:pass@host:port',
     *    'https': 'socks5://user:pass@host:port'
     *)
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public static function set_proxies($proxies)
    {
        self::$proxies = $proxies;
    }

    /**
     * 设置Headers
     *
     * @param string $headers
     * @return void
     */
    public static function add_header($key, $value)
    {
        self::$headers[$key] = $value;
    }

    /**
     * 设置COOKIE
     *
     * @param string $cookie
     * @return void
     */
    public function add_cookie($key, $value, $domain = '')
    {
        if (empty($key) || empty($value)) 
        {
            return false;
        }
        if (!empty($domain)) 
        {
            self::$cookies[$domain][$key] = $value;
        }
        else 
        {
            self::$cookies[$key] = $value;
        }
        return true;
    }

    public function add_cookies($cookies, $domain = '')
    {
        $cookies_arr = explode(";", $cookies);
        if (empty($cookie_arr)) 
        {
            return false;
        }
        foreach ($cookies_arr as $cookie) 
        {
            $cookie_arr = explode("=", $cookie);
            $key = $value = "";
            foreach ($cookie_arr as $k=>$v) 
            {
                if ($k == 0) 
                {
                    $key = trim($v);
                }
                else 
                {
                    $value .= trim(str_replace('"', '', $v));
                }
            }

            if (!empty($domain)) 
            {
                self::$cookies[$domain][$key] = $value;
            }
            else 
            {
                self::$cookies[$key] = $value;
            }
        }
        return true;
    }

    public function get_cookie($name, $domain = '')
    {
        if (!empty($domain) && !isset(self::$cookies[$domain])) 
        {
            return false;
        }
        $cookies = empty($domain) ? self::$cookies : self::$cookies[$domain];
        return isset($cookies[$name]) ? $cookies[$name] : '';
    }
    
    public function get_cookies($domain = '')
    {
        if (!empty($domain) && !isset(self::$cookies[$domain])) 
        {
            return false;
        }
        return empty($domain) ? self::$cookies : self::$cookies[$domain];
    }

    /**
     * 设置 user_agent
     *
     * @param string $useragent
     * @return void
     */
    public static function set_useragent($useragent)
    {
        self::$headers['User-Agent'] = $useragent;
    }

    /**
     * set referer
     *
     */
    public static function set_referer($referer)
    {
        self::$headers['Referer'] = $referer;
    }

    /**
     * 设置伪造IP
     *
     * @param string $ip
     * @return void
     */
    public static function set_client_ip($ip)
    {
        self::$headers["CLIENT-IP"] = $ip;
        self::$headers["X-FORWARDED-FOR"] = $ip;
    }

    /**
     * 设置Hosts
     *
     * @param string $hosts
     * @return void
     */
    public static function set_hosts($hosts)
    {
        self::$hosts = $hosts;
    }

    /**
     * 初始化 CURL
     *
     */
    public static function init()
    {
        if (!is_resource ( self::$ch ))
        {
            self::$ch = curl_init ();
            curl_setopt( self::$ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( self::$ch, CURLOPT_CONNECTTIMEOUT, self::$timeout );
            curl_setopt( self::$ch, CURLOPT_HEADER, false );
            curl_setopt( self::$ch, CURLOPT_USERAGENT, "phpspider-requests/".self::VERSION );
            curl_setopt( self::$ch, CURLOPT_TIMEOUT, self::$timeout + 5);
            // 在多线程处理场景下使用超时选项时，会忽略signals对应的处理函数，但是无耐的是还有小概率的crash情况发生
            curl_setopt( self::$ch, CURLOPT_NOSIGNAL, true);
        }
        return self::$ch;
    }

    /**
     * get
     *
     *
     */
    public static function get($url, $fields = array())
    {
        self::init ();
        return self::http_request($url, 'get', $fields);
    }

    /**
     * $fields 有三种类型:1、数组；2、http query；3、json
     * 1、array('name'=>'yangzetao') 2、http_build_query(array('name'=>'yangzetao')) 3、json_encode(array('name'=>'yangzetao'))
     * 前两种是普通的post，可以用$_POST方式获取
     * 第三种是post stream( json rpc，其实就是webservice )，虽然是post方式，但是只能用流方式 http://input 后者 $HTTP_RAW_POST_DATA 获取 
     * 
     * @param mixed $url 
     * @param array $fields 
     * @param mixed $proxies 
     * @static
     * @access public
     * @return void
     */
    public static function post($url, $fields = array())
    {
        self::init ();
        return self::http_request($url, 'post', $fields);
    }

    public static function put($url, $fields = array())
    {
    }

    public static function delete($url, $fields = array())
    {
    }

    public static function head($url, $fields = array())
    {
    }

    public static function options($url, $fields = array())
    {
    }

    public static function http_request($url, $type = 'get', $fields)
    {
        // 如果是 get 方式，直接拼凑一个 url 出来
        if (strtolower($type) == 'get' && !empty($fields)) 
        {
            self::$url = $url . (strpos($url,"?")===false ? "?" : "&") . http_build_query($fields);
        }
        $parse_url = parse_url($url);
        if (empty($parse_url) || !in_array($parse_url['scheme'], array('http', 'https'))) 
        {
            exit("No connection adapters were found for '{$url}'\n");
        }
        $scheme = $parse_url['scheme'];

        // 随机绑定 hosts，做负载均衡
        //if (self::$hosts) 
        //{
            //$host = $parse_url['host'];
            //$key = rand(0, count(self::$hosts)-1);
            //$ip = self::$hosts[$key];
            //$url = str_replace($host, $ip, $url);
            //self::$headers['Host'] = $host;
        //}

        curl_setopt( self::$ch, CURLOPT_URL, self::$url );

        // 如果是 post 方式
        if (strtolower($type) == 'post')
        {
            curl_setopt( self::$ch, CURLOPT_POST, true );
            curl_setopt( self::$ch, CURLOPT_POSTFIELDS, $fields );
        }
        if (self::$cookies)
        {
            curl_setopt( self::$ch, CURLOPT_COOKIE, self::$cookies );
        }
        if (self::$headers)
        {
            curl_setopt( self::$ch, CURLOPT_HTTPHEADER, self::$headers );
        }

        //curl_setopt( self::$ch, CURLOPT_ENCODING, 'gzip' );

        if (self::$proxies)
        {
            if (!empty(self::$proxies[$scheme])) 
            {
                curl_setopt( self::$ch, CURLOPT_PROXY, self::$proxies[$scheme] );
            }
        }

        // 为了取cookie
        curl_setopt( self::$ch, CURLOPT_HEADER, true );

        $data = curl_exec ( self::$ch );
        //var_dump($data);
        self::$info = curl_getinfo(self::$ch);
        self::$status_code = self::$info['http_code'];
        if ($data === false)
        {
            //echo date("Y-m-d H:i:s"), ' Curl error: ' . curl_error( self::$ch ), "\n";
        }

        // 关闭句柄
        curl_close( self::$ch );
        //$data = substr($data, 10);
        //$data = gzinflate($data);
        return $data;
    }

}
