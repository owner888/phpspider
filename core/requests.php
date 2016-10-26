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
    //protected static $request = array(
        //'headers' => array()
    //);
    protected static $cookies = array();
    protected static $domain_cookies = array();
    protected static $hosts = array();
    public static $headers = array();
    public static $useragents = array();
    public static $proxies = array();
    public static $url = null;
    public static $domain = null;
    public static $raw = null;
    public static $content = null;
    public static $input_encoding = null;
    public static $output_encoding = null;
    public static $info = array();
    public static $status_code = 0;
    public static $error = null;

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
    public static function add_cookie($key, $value, $domain = '')
    {
        if (empty($key) || empty($value)) 
        {
            return false;
        }
        if (!empty($domain)) 
        {
            self::$domain_cookies[$domain][$key] = $value;
        }
        else 
        {
            self::$cookies[$key] = $value;
        }
        return true;
    }

    public static function add_cookies($cookies, $domain = '')
    {
        $cookies_arr = explode(";", $cookies);
        if (empty($cookies_arr)) 
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
                self::$domain_cookies[$domain][$key] = $value;
            }
            else 
            {
                self::$cookies[$key] = $value;
            }
        }
        return true;
    }

    public static function get_cookie($name, $domain = '')
    {
        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) 
        {
            return '';
        }
        $cookies = empty($domain) ? self::$cookies : self::$domain_cookies[$domain];
        return isset($cookies[$name]) ? $cookies[$name] : '';
    }
    
    public static function get_cookies($domain = '')
    {
        if (!empty($domain) && !isset(self::$domain_cookies[$domain])) 
        {
            return array();
        }
        return empty($domain) ? self::$cookies : self::$domain_cookies[$domain];
    }

    /**
     * 设置随机的user_agent
     *
     * @param string $useragent
     * @return void
     */
    public static function set_useragents($useragents)
    {
        self::$useragents = $useragents;
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

    public static function get_response_body($domain)
    {
        $header = $body = '';
        $http_headers = array();
        // 解析HTTP数据流
        if (!empty(self::$raw)) 
        {
            self::get_response_cookies($domain);
            // body里面可能有 \r\n\r\n，但是第一个一定是HTTP Header，去掉后剩下的就是body
            $array = explode("\r\n\r\n", self::$raw);
            foreach ($array as $k=>$v) 
            {
                // post 方法会有两个http header：HTTP/1.1 100 Continue、HTTP/1.1 200 OK
                if (preg_match("#^HTTP/.*? 100 Continue#", $v)) 
                {
                    unset($array[$k]);
                    continue;
                }
                if (preg_match("#^HTTP/.*? \d+ #", $v)) 
                {
                    $header = $v;
                    unset($array[$k]);
                    $http_headers = self::get_response_headers($v);
                }
            }
            $body = implode("\r\n\r\n", $array);
        }

        // 如果用户没有明确指定输入的页面编码格式(utf-8, gb2312)，通过程序去判断
        if(self::$input_encoding == null)
        {
            // 从头部获取
            preg_match("/charset=([^\s]*)/i", $header, $out);
            $encode = empty($out[1]) ? '' : str_replace(array('"', '\''), '', strtolower(trim($out[1])));
            if (empty($encode)) 
            {
                // 在某些情况下,无法再 response header 中获取 html 的编码格式
                // 则需要根据 html 的文本格式获取
                $encode = self::_get_encode($body);
                $encode = strtolower($encode);
                if($encode == false || $encode == "ascii")
                {
                    $encode = 'gbk';
                }
            }
            self::$input_encoding = $encode;
        }

        // 设置了输出编码的转码，注意: xpath只支持utf-8
        if (self::$output_encoding && self::$input_encoding != self::$output_encoding) 
        {
            // 先将非utf8编码,转化为utf8编码
            $body = mb_convert_encoding($body, self::$output_encoding, self::$input_encoding);
            // 将页面中的指定的编码方式修改为utf8
            $body = preg_replace("/<meta([^>]*)charset=([^>]*)>/is", '<meta charset="UTF-8">', $body);
            // 直接干掉头部，国外很多信息是在头部的
            //$body = self::_remove_head($body);
        }
        return $body;
    }

    public static function get_response_cookies($domain)
    {
        // 解析Cookie并存入 self::$cookies 方便调用
        preg_match_all("/.*?Set\-Cookie: ([^\r\n]*)/i", self::$raw, $matches);
        $cookies = empty($matches[1]) ? array() : $matches[1];

        // 解析到Cookie
        if (!empty($cookies)) 
        {
            $cookies = implode(";", $cookies);
            $cookies = explode(";", $cookies);
            foreach ($cookies as $cookie) 
            {
                $cookie_arr = explode("=", $cookie);
                // 过滤 httponly、secure
                if (count($cookie_arr) < 2) 
                {
                    continue;
                }
                $cookie_name = !empty($cookie_arr[0]) ? trim($cookie_arr[0]) : '';
                if (empty($cookie_name)) 
                {
                    continue;
                }
                // 过滤掉domain路径
                if (in_array(strtolower($cookie_name), array('path', 'domain', 'expires', 'max-age'))) 
                {
                    continue;
                }
                self::$domain_cookies[$domain][trim($cookie_arr[0])] = trim($cookie_arr[1]);
            }
        }
    }

    public static function get_response_headers($html)
    {
        $header_lines = explode("\n", $html);
        if (!empty($header_lines)) 
        {
            foreach ($header_lines as $line) 
            {
                $header_arr = explode(":", $line);
                $key = empty($header_arr[0]) ? '' : trim($header_arr[0]);
                $val = empty($header_arr[1]) ? '' : trim($header_arr[1]);
                if (empty($key) || empty($val)) 
                {
                    continue;
                }
                $headers[$key] = $val;
            }
        }
    }


    /**
     * 获取文件编码
     * @param $string
     * @return string
     */
    private static function _get_encode($string)
    {
        $encode = mb_detect_encoding($string, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
        return strtolower($encode);
    }

    /**
     * 移除页面head区域代码
     * @param $html
     * @return mixed
     */
    private static function _remove_head($html)
    {
        return preg_replace('/<head.+?>.+<\/head>/is', '<head></head>', $html);
    }
    
    /**
     * 简单的判断一下参数是否为一个URL链接
     * @param  string  $str 
     * @return boolean      
     */
    private static function _is_url($url)
    {
        //$pattern = '/^http(s)?:\\/\\/.+/';
        $pattern = "/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/";
        if (preg_match($pattern, $url)) 
        {
            return true;
        }
        return false;
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
        return self::http_client($url, 'get', $fields);
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
        return self::http_client($url, 'POST', $fields);
    }

    public static function put($url, $fields = array())
    {
        self::init ();
        return self::http_client($url, 'PUT', $fields);
    }

    public static function delete($url, $fields = array())
    {
        self::init ();
        return self::http_client($url, 'DELETE', $fields);
    }

    public static function head($url, $fields = array())
    {
        self::init ();
        return self::http_client($url, 'HEAD', $fields);
    }

    public static function options($url, $fields = array())
    {
        self::init ();
        return self::http_client($url, 'OPTIONS', $fields);
    }

    public static function http_client($url, $method = 'GET', $fields)
    {
        $method = strtoupper($method);
        if(!self::_is_url($url))
        {
            self::$error = "You have requested URL ({$url}) is not a valid HTTP address";
            return false;
        }

        // 如果是 get 方式，直接拼凑一个 url 出来
        if ($method == 'GET' && !empty($fields)) 
        {
            $url = $url . (strpos($url,"?")===false ? "?" : "&") . http_build_query($fields);
        }

        $parse_url = parse_url($url);
        if (empty($parse_url) || empty($parse_url['host']) || !in_array($parse_url['scheme'], array('http', 'https'))) 
        {
            self::$error = "No connection adapters were found for '{$url}'";
            return false;
        }
        $scheme = $parse_url['scheme'];
        $domain = $parse_url['host'];

        // 随机绑定 hosts，做负载均衡
        //if (self::$hosts) 
        //{
            //$host = $parse_url['host'];
            //$key = rand(0, count(self::$hosts)-1);
            //$ip = self::$hosts[$key];
            //$url = str_replace($host, $ip, $url);
            //self::$headers['Host'] = $host;
        //}

        curl_setopt( self::$ch, CURLOPT_URL, $url );

        if ($method != 'GET')
        {
            // 如果是 post 方式
            if ($method == 'POST')
            {
                curl_setopt( self::$ch, CURLOPT_POST, true );
            }
            else
            {
                self::$headers['X-HTTP-Method-Override'] = $method;
                curl_setopt( self::$ch, CURLOPT_CUSTOMREQUEST, $method ); 
            }
            curl_setopt( self::$ch, CURLOPT_POSTFIELDS, $fields );
        }

        $cookies = self::get_cookies();
        $domain_cookies = self::get_cookies($domain);
        $cookies =  array_merge($cookies, $domain_cookies);
        // 是否设置了cookie
        if (!empty($cookies)) 
        {
            foreach ($cookies as $key=>$value) 
            {
                $cookie_arr[] = $key."=".$value;
            }
            $cookies = implode("; ", $cookie_arr);
            curl_setopt( self::$ch, CURLOPT_COOKIE, $cookies );
        }

        if (!empty(self::$useragents)) 
        {
            $key = rand(0, count(self::$useragents) - 1);
            self::$headers['User-Agent'] = self::$useragents[$key];
        }
        if (self::$headers)
        {
            $headers = array();
            foreach (self::$headers as $k=>$v) 
            {
                $headers[] = $k.": ".$v;
            }
            curl_setopt( self::$ch, CURLOPT_HTTPHEADER, $headers );
        }

        curl_setopt( self::$ch, CURLOPT_ENCODING, 'gzip' );

        if (self::$proxies)
        {
            if (!empty(self::$proxies[$scheme])) 
            {
                curl_setopt( self::$ch, CURLOPT_PROXY, self::$proxies[$scheme] );
            }
        }

        // header + body，header 里面有 cookie
        curl_setopt( self::$ch, CURLOPT_HEADER, true );

        self::$raw = curl_exec ( self::$ch );
        //var_dump($data);
        self::$info = curl_getinfo( self::$ch );
        self::$status_code = self::$info['http_code'];
        if (self::$raw === false)
        {
            self::$error = ' Curl error: ' . curl_error( self::$ch );
        }

        // 关闭句柄
        curl_close( self::$ch );

        // 请求成功之后才把URL存起来
        self::$url = $url;
        self::$content = self::get_response_body($domain);
        //$data = substr($data, 10);
        //$data = gzinflate($data);
        return self::$content;
    }

}
