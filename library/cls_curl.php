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
    protected static $useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
    protected static $http_raw = false;
    protected static $cookie = null;
    protected static $cookie_jar = null;
    protected static $cookie_file = null;
    protected static $referer = null;
    protected static $ip = null;
    protected static $proxy = null;
    protected static $headers = array();
    protected static $hosts = array();
    protected static $gzip = false;
    protected static $info = array();

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
     * @param mixed $proxy
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
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
     * 获取内容的时候是不是连header也一起获取
     * 
     * @param mixed $http_raw
     * @return void
     * @author seatle <seatle@foxmail.com> 
     * @created time :2016-09-18 10:17
     */
    public static function set_http_raw($http_raw)
    {
        self::$http_raw = $http_raw;
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
     * @param mixed $proxy 
     * @static
     * @access public
     * @return void
     */
    public static function post($url, $fields = array())
    {
        self::init ();
        return self::http_request($url, 'post', $fields);
    }

    public static function http_request($url, $type = 'get', $fields)
    {
        // 如果是 get 方式，直接拼凑一个 url 出来
        if (strtolower($type) == 'get' && !empty($fields)) 
        {
            $url = $url . (strpos($url,"?")===false ? "?" : "&") . http_build_query($fields);
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
        if (self::$proxy)
        {
            curl_setopt( self::$ch, CURLOPT_PROXY, self::$proxy );
        }
        if (self::$http_raw)
        {
            curl_setopt( self::$ch, CURLOPT_HEADER, true );
        }

        $data = curl_exec ( self::$ch );
        self::$info = curl_getinfo(self::$ch);
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

    public static function get_info()
    {
        return self::$info;
    }

    public static function get_http_code()
    {
        return self::$info['http_code'];
    }
}

function classic_curl($urls, $delay) 
{
    $queue = curl_multi_init();
    $map = array();

    foreach ($urls as $url) 
    {
        // create cURL resources
        $ch = curl_init();

        // 设置 URL 和 其他参数
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);

        // 把当前 curl resources 加入到 curl_multi_init 队列
        curl_multi_add_handle($queue, $ch);
        $map[$url] = $ch;
    }

    $active = null;

    // execute the handles
    do {
        $mrc = curl_multi_exec($queue, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active > 0 && $mrc == CURLM_OK) {
        while (curl_multi_exec($queue, $active) === CURLM_CALL_MULTI_PERFORM);
        // 这里 curl_multi_select 一直返回 -1，所以这里就死循环了，CPU就100%了
        if (curl_multi_select($queue, 0.5) != -1) 
        {
            do {
                $mrc = curl_multi_exec($queue, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }

    $responses = array();
    foreach ($map as $url=>$ch) {
        //$responses[$url] = callback(curl_multi_getcontent($ch), $delay);
        $responses[$url] = callback(curl_multi_getcontent($ch), $delay, $url);
        curl_multi_remove_handle($queue, $ch);
        curl_close($ch);
    }

    curl_multi_close($queue);
    return $responses;
}

function rolling_curl($urls, $delay) 
{
    $queue = curl_multi_init();
    $map = array();

    foreach ($urls as $url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        $cookie = '_za=36643642-e546-4d60-a771-8af8dcfbd001; q_c1=a57a2b9f10964f909b8d8969febf3ab2|1437705596000|1437705596000; _xsrf=f0304fba4e44e1d008ec308d59bab029; cap_id="YWY1YmRmODlmZGVmNDc3MWJlZGFkZDg3M2E0M2Q5YjM=|1437705596|963518c454bb6f10d96775021c098c84e1e46f5a"; z_c0="QUFCQVgtRWZBQUFYQUFBQVlRSlZUVjR6NEZVUTgtRkdjTVc5UDMwZXRJZFdWZ2JaOWctNVhnPT0=|1438164574|aed6ef3707f246a7b64da4f1e8c089395d77ff2b"; __utma=51854390.1105113342.1437990174.1438160686.1438164116.10; __utmc=51854390; __utmz=51854390.1438134939.8.5.utmcsr=zhihu.com|utmccn=(referral)|utmcmd=referral|utmcct=/people/yangzetao; __utmv=51854390.100-1|2=registration_date=20131030=1^3=entry_date=20131030=1';
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        $useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36';
        curl_setopt( $ch, CURLOPT_USERAGENT, $useragent );
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

        curl_multi_add_handle($queue, $ch);
        $map[(string) $ch] = $url;
    }

    $responses = array();
    do {
        while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;

        if ($code != CURLM_OK) { break; }

        // a request was just completed -- find out which one
        while ($done = curl_multi_info_read($queue)) {

            // get the info and content returned on the request
            $info = curl_getinfo($done['handle']);
            $error = curl_error($done['handle']);
            $results = callback(curl_multi_getcontent($done['handle']), $delay, $map[(string) $done['handle']]);
            $responses[$map[(string) $done['handle']]] = compact('info', 'error', 'results');

            // remove the curl handle that just completed
            curl_multi_remove_handle($queue, $done['handle']);
            curl_close($done['handle']);
        }

        // Block for data in / output; error handling is done by curl_multi_exec
        if ($active > 0) {
            curl_multi_select($queue, 0.5);
        }

    } while ($active);

    curl_multi_close($queue);
    return $responses;
}

function callback($data, $delay, $url) {
    //echo $data;
    //echo date("Y-m-d H:i:s", time()) . " --- " . $url . "\n";
    if (!empty($data)) 
    {
        file_put_contents("./html2/".md5($url).".html", $data);
    }
    // usleep模拟现实中比较负责的数据处理逻辑(如提取, 分词, 写入文件或数据库等)
    //usleep(1);
    //return compact('data', 'matches');
}

