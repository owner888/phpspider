<?php
/**
 * Worker多进程操作类
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

class cls_curl
{
    protected static $timeout = 10;
    protected static $ch = null;
    protected static $proxy = null;
    protected static $useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
    protected static $cookie = null;
    protected static $cookie_jar = null;
    protected static $cookie_file = null;
    protected static $referer = null;
    protected static $ip = null;
    protected static $headers = array();
    protected static $hosts = array();
    protected static $gzip = false;

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
     * set proxy
     *
     */
    public static function set_proxy($proxy)
    {
        self::$proxy = $proxy;
    }
    
    /**
     * set referer
     *
     */
    public static function set_referer($referer)
    {
        self::$referer = $referer;
    }

    /**
     * 设置 user_agent
     *
     * @param string $useragent
     * @return void
     */
    public static function set_useragent($useragent)
    {
        self::$useragent = $useragent;
    }

    /**
     * 设置COOKIE
     *
     * @param string $cookie
     * @return void
     */
    public static function set_cookie($cookie)
    {
        self::$cookie = $cookie;
    }

    /**
     * 设置COOKIE JAR
     *
     * @param string $cookie_jar
     * @return void
     */
    public static function set_cookie_jar($cookie_jar)
    {
        self::$cookie_jar = $cookie_jar;
    }

    /**
     * 设置COOKIE FILE
     *
     * @param string $cookie_file
     * @return void
     */
    public static function set_cookie_file($cookie_file)
    {
        self::$cookie_file = $cookie_file;
    }

    /**
     * 设置IP
     *
     * @param string $ip
     * @return void
     */
    public static function set_ip($ip)
    {
        self::$ip = $ip;
    }

    /**
     * 设置Headers
     *
     * @param string $headers
     * @return void
     */
    public static function set_headers($headers)
    {
        self::$headers = $headers;
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
     * 设置Gzip
     *
     * @param string $hosts
     * @return void
     */
    public static function set_gzip($gzip)
    {
        self::$gzip = $gzip;
    }

    /**
     * 初始化 CURL
     *
     */
    public static function init()
    {
        //if (empty ( self::$ch ))
        if (!is_resource ( self::$ch ))
        {
            self::$ch = curl_init ();
            curl_setopt( self::$ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( self::$ch, CURLOPT_CONNECTTIMEOUT, self::$timeout );
            curl_setopt( self::$ch, CURLOPT_HEADER, false );
            curl_setopt( self::$ch, CURLOPT_USERAGENT, self::$useragent );
            curl_setopt( self::$ch, CURLOPT_TIMEOUT, self::$timeout + 5);
        }
        return self::$ch;
    }

    /**
     * get
     *
     *
     */
    public static function get($url, $fields = array(), $proxy = false)
    {
        self::init ();
        return self::http_request($url, 'get', $fields, $proxy);
    }

    /**
     * $fields 有三种类型:1、数组；2、http query；3、json
     * 1、array('name'=>'yangzetao') 2、http_build_query(array('name'=>'yangzetao')) 3、json_encode(array('name'=>'yangzetao'))
     * 前两种是普通的post，可以用$_POST方式获取
     * 第三种是post stream( json rpc，其实就是webservice )，虽然是post方式，但是只能用流方式 http://input 后者 $HTTP_RAW_POST_DATA 获取 
     * 
     * @param mixed $url 
     * @param array $fields 
     * @param mixed $proxy 
     * @static
     * @access public
     * @return void
     */
    public static function post($url, $fields = array(), $proxy = false)
    {
        self::init ();
        return self::http_request($url, 'post', $fields, $proxy);
    }

    public static function http_request($url, $type = 'get', $fields, $proxy = false)
    {
        // 如果是 get 方式，直接拼凑一个 url 出来
        if (strtolower($type) == 'get' && !empty($fields)) 
        {
            $url = $url . "?" . http_build_query($fields);
        }

        // 随机绑定 hosts，做负载均衡
        if (self::$hosts) 
        {
            $parse_url = parse_url($url);
            $host = $parse_url['host'];
            $key = rand(0, count(self::$hosts)-1);
            $ip = self::$hosts[$key];
            $url = str_replace($host, $ip, $url);
            self::$headers = array_merge( array('Host:'.$host), self::$headers );
        }
        curl_setopt( self::$ch, CURLOPT_URL, $url );
        // 如果是 post 方式
        if (strtolower($type) == 'post')
        {
            curl_setopt( self::$ch, CURLOPT_POST, true );
            curl_setopt( self::$ch, CURLOPT_POSTFIELDS, $fields );
        }
        if (self::$useragent)
        {
            curl_setopt( self::$ch, CURLOPT_USERAGENT, self::$useragent );
        }
        if (self::$cookie)
        {
            curl_setopt( self::$ch, CURLOPT_COOKIE, self::$cookie );
        }
        if (self::$cookie_jar)
        {
            curl_setopt( self::$ch, CURLOPT_COOKIEJAR, self::$cookie_jar );
        }
        if (self::$cookie_file)
        {
            curl_setopt( self::$ch, CURLOPT_COOKIEFILE, self::$cookie_file );
        }
        if (self::$referer)
        {
            curl_setopt( self::$ch, CURLOPT_REFERER, self::$referer );
        }
        if (self::$ip)
        {
            self::$headers = array_merge( array('CLIENT-IP:'.self::$ip, 'X-FORWARDED-FOR:'.self::$ip), self::$headers );
        }
        if (self::$headers)
        {
            curl_setopt( self::$ch, CURLOPT_HTTPHEADER, self::$headers );
        }
        if (self::$gzip)
        {
            curl_setopt( self::$ch, CURLOPT_ENCODING, 'gzip' );
        }
        if ($proxy)
        {
            curl_setopt( self::$ch, CURLOPT_PROXY, $url );
            curl_setopt( self::$ch, CURLOPT_USERAGENT, $url );
        }
        $data = curl_exec ( self::$ch );
        if ($data === false)
        {
            echo 'Curl error: ' . curl_error( self::$ch );
        }
        // 关闭句柄
        curl_close( self::$ch );

        //$data = substr($data, 10);
        //$data = gzinflate($data);
        return $data;
    }


}

