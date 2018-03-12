<?php
/**
 * redis 客户端 
 * redis的协议可参考这个文章http://redis.cn/topics/protocol.html
 * 
 * @version 2.7.0
 * @copyright 1997-2018 The PHP Group
 * @author seatle <seatle@foxmail.com> 
 * @created time :2018-01-03
 */
class cls_redis_client 
{
    private $redis_socket = false;
    //private $command = '';

    public function __construct($host='127.0.0.1', $port=6379, $timeout = 3) 
    {
        $this->redis_socket = stream_socket_client("tcp://".$host.":".$port, $errno, $errstr,  $timeout);
        if ( !$this->redis_socket )
        {
            throw new Exception("{$errno} - {$errstr}");
        }
    }

    public function __destruct()
    {
        fclose($this->redis_socket);
    }

    public function __call($name, $args) 
    {
        $crlf = "\r\n";
        array_unshift($args, $name);
        $command = '*' . count($args) . $crlf;
        foreach ($args as $arg) 
        {
            $command .= '$' . strlen($arg) . $crlf . $arg . $crlf;
        }
        //echo $command."\n";
        $fwrite = fwrite($this->redis_socket, $command);
        if ($fwrite === FALSE || $fwrite <= 0)
        {
            throw new Exception('Failed to write entire command to stream');
        }
        return $this->read_response();
    }

    private function read_response() 
    {
        $reply = trim(fgets($this->redis_socket, 1024));
        switch (substr($reply, 0, 1))
        {
        case '-':
            throw new Exception(trim(substr($reply, 1)));
            break;
        case '+':
            $response = substr(trim($reply), 1);
            if ($response === 'OK') 
            {
                $response = TRUE;
            }
            break;
        case '$':
            $response = NULL;
            if ($reply == '$-1') 
            {
                break;
            }
            $read = 0;
            $size = intval(substr($reply, 1));
            if ($size > 0) 
            {
                do 
                {
                    $block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
                    $r = fread($this->redis_socket, $block_size);
                    if ($r === FALSE) 
                    {
                        throw new Exception('Failed to read response from stream');
                    }
                    else 
                    {
                        $read += strlen($r);
                        $response .= $r;
                    }
                }
                while ($read < $size);
            }
            fread($this->redis_socket, 2); /* discard crlf */
            break;
            /* Multi-bulk reply */
        case '*':
            $count = intval(substr($reply, 1));
            if ($count == '-1') 
            {
                return NULL;
            }
            $response = array();
            for ($i = 0; $i < $count; $i++) 
            {
                $response[] = $this->read_response();
            }
            break;
            /* Integer reply */
        case ':':
            $response = intval(substr(trim($reply), 1));
            break;
        default:
            throw new RedisException("Unknown response: {$reply}");
            break;
        }
        return $response;
    }
}


//$redis = new cls_redis_client();
//var_dump($redis->auth("foobared"));
//var_dump($redis->set("name",'abc'));
//var_dump($redis->get("name"));
 
