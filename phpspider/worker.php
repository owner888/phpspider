<?php
//设置时区
date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', 1);
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

class worker
{
    public $count = 0;
    public $worker_id = 0;
    public $worker_pid = 0;
    public $user = '';
    public $title = '';
    public $is_once = false;
    public $log_show = true;
    public $log_file = '';
    public $on_worker_start = false;
    public $on_worker_stop = false;
    protected static $_master_pid = 0;
    protected static $_worker_pids = array();

    public function __construct()
    {
        // 注册进程退出回调，用来检查是否有错误
        register_shutdown_function(array($this, 'check_errors'));
        self::$_master_pid = posix_getpid();
        // 产生时钟云，添加后父进程才可以收到信号
        declare(ticks = 1);
        $this->install_signal();
    }

    /**
     * 安装信号处理函数
     * @return void
     */
    protected function install_signal()
    {
        // stop
        pcntl_signal(SIGINT,  array($this, 'signal_handler'), false);
        // reload
        pcntl_signal(SIGUSR1, array($this, 'signal_handler'), false);
        // status
        pcntl_signal(SIGUSR2, array($this, 'signal_handler'), false);
        // ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);
        // install signal handler for dead kids
        // pcntl_signal(SIGCHLD, array($this, 'signal_handler'));
    }

    /**
     * 卸载信号处理函数
     * @return void
     */
    protected function uninstall_signal()
    {
        // uninstall stop signal handler
        pcntl_signal(SIGINT,  SIG_IGN, false);
        // uninstall reload signal handler
        pcntl_signal(SIGUSR1, SIG_IGN, false);
        // uninstall  status signal handler
        pcntl_signal(SIGUSR2, SIG_IGN, false);
    }

    /**
     * 信号处理函数，会被其他类调用到，所以要设置为public
     * @param int $signal
     */
    public function signal_handler($signal) {
        switch ($signal) {
            // stop 2
            case SIGINT:
                $this->stop_all();
                break;
            // reload 30
            case SIGUSR1:
                echo "sreloadtop\n";
                break;
            // show status 31
            case SIGUSR2:   
                echo "status\n";
                break;
        }
    }

    /**
     * 运行worker实例
     */
    public function run()
    {
        $this->set_process_title($this->title);

        for ($i = 0; $i < $this->count; $i++) 
        {
            $this->fork_one_worker($i);
        }
        $this->monitor_workers();

        //$size = memory_get_usage() - $this->memory_start;
        //$unit = array('b','kb','mb','gb','tb','pb'); 
        //$memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]; 
        //$time = microtime(true) - $this->time_start;
        //echo "Done in $time seconds\t $memory\n";

    }

    /**
     * 创建一个子进程
     * @param Worker $worker
     * @throws Exception
     */
    public function fork_one_worker($worker_id)
    {
        //$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $pid = pcntl_fork();

        // 父进程，$pid 是子进程的id
        if($pid > 0)
        {
            self::$_worker_pids[$pid] = $pid;
        }
        // 子进程
        elseif(0 === $pid)
        {
            $this->worker_id = $worker_id;
            $this->worker_pid = posix_getpid();
            $this->set_process_title($this->title);
            $this->set_process_user($this->user);
            self::$_worker_pids = array();
            //$this->uninstall_signal();
            if ($this->on_worker_start) 
            {
                call_user_func($this->on_worker_start, $this);
            }
            // 这里用0表示正常退出
            exit(0);
        }
        else
        {
            exit("fork one worker fail");
        }
    }

    /**
     * 尝试设置运行当前进程的用户
     *
     * @param $user_name
     */
    protected static function set_process_user($user_name)
    {
        // 用户名为空 或者 当前用户不是root用户
        if(empty($user_name) || posix_getuid() !== 0)
        {
            return;
        }
        $user_info = posix_getpwnam($user_name);
        if($user_info['uid'] != posix_getuid() || $user_info['gid'] != posix_getgid())
        {
            if(!posix_setgid($user_info['gid']) || !posix_setuid($user_info['uid']))
            {
                echo 'Notice : Can not run woker as '.$user_name." , You shuld be root\n";
            }
        }
    }

    /**
     * 设置当前进程的名称，在ps aux命令中有用
     * 注意 需要php>=5.5或者安装了protitle扩展
     * @param string $title
     * @return void
     */
    protected function set_process_title($title)
    {
        if (!empty($title)) 
        {
            // 需要扩展
            if(extension_loaded('proctitle') && function_exists('setproctitle'))
            {
                @setproctitle($title);
            }
            // >=php 5.5
            elseif (function_exists('cli_set_process_title'))
            {
                cli_set_process_title($title);
            }
        }
    }

    /**
     * 监控所有子进程的退出事件及退出码
     * @return void
     */
    public function monitor_workers()
    {
        while(1)
        {
            // pcntl_signal_dispatch 子进程无法接受到信号
            // 如果有信号到来，尝试触发信号处理函数
            //pcntl_signal_dispatch();
            // 挂起进程，直到有子进程退出或者被信号打断
            $status = 0;
            $pid = pcntl_wait($status, WUNTRACED);
            // 如果有信号到来，尝试触发信号处理函数
            //pcntl_signal_dispatch();

            // 子进程退出信号
            if($pid > 0)
            {
                //echo "worker[".$pid."] stop\n";
                $this->stop();

                // 如果不是正常退出，是被kill等杀掉的
                if($status !== 0)
                {
                    echo "worker {$pid} exit with status $status\n";
                }

                unset(self::$_worker_pids[$pid]);

                // 再生成一个worker
                if (!$this->is_once) 
                {
                    $this->fork_one_worker();
                }

                if (!self::$_worker_pids) 
                {
                    exit("主进程退出\n");
                }
            }
            // 其他信号
            else 
            {
                exit("主进程 异常 退出\n");
            }
        }
    }

    /**
     * 执行关闭流程
     * @return void
     */
    public function stop_all()
    {
        if(self::$_master_pid === posix_getpid())
        {
            foreach (self::$_worker_pids as $worker_pid) 
            {
                posix_kill($worker_pid, SIGINT);
            }
            //sleep(2);
            echo "主进程\n";
        }
        else 
        {
            $this->stop();
            exit(0);
        }
    }

    /**
     * 停止当前worker实例
     * @return void
     */
    public function stop()
    {
        if ($this->on_worker_stop) 
        {
            call_user_func($this->on_worker_stop, $this);
        }
    }

    /**
     * 检查错误，PHP exit之前会执行
     * @return void
     */
    public function check_errors()
    {
        $error_msg = "WORKER EXIT UNEXPECTED ";
        $errors = error_get_last();
        if($errors && ($errors['type'] === E_ERROR ||
            $errors['type'] === E_PARSE ||
            $errors['type'] === E_CORE_ERROR ||
            $errors['type'] === E_COMPILE_ERROR || 
            $errors['type'] === E_RECOVERABLE_ERROR ))
        {
            $error_msg .= $this->get_error_type($errors['type']) . " {$errors['message']} in {$errors['file']} on line {$errors['line']}";
        }
        $this->log($error_msg);
    }

    /**
     * 获取错误类型对应的意义
     * @param integer $type
     * @return string
     */
    protected function get_error_type($type)
    {
        switch($type)
        {
        case E_ERROR: // 1 //
            return 'E_ERROR';
        case E_WARNING: // 2 //
            return 'E_WARNING';
        case E_PARSE: // 4 //
            return 'E_PARSE';
        case E_NOTICE: // 8 //
            return 'E_NOTICE';
        case E_CORE_ERROR: // 16 //
            return 'E_CORE_ERROR';
        case E_CORE_WARNING: // 32 //
            return 'E_CORE_WARNING';
        case E_COMPILE_ERROR: // 64 //
            return 'E_COMPILE_ERROR';
        case E_COMPILE_WARNING: // 128 //
            return 'E_COMPILE_WARNING';
        case E_USER_ERROR: // 256 //
            return 'E_USER_ERROR';
        case E_USER_WARNING: // 512 //
            return 'E_USER_WARNING';
        case E_USER_NOTICE: // 1024 //
            return 'E_USER_NOTICE';
        case E_STRICT: // 2048 //
            return 'E_STRICT';
        case E_RECOVERABLE_ERROR: // 4096 //
            return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED: // 8192 //
            return 'E_DEPRECATED';
        case E_USER_DEPRECATED: // 16384 //
            return 'E_USER_DEPRECATED';
        }
        return "";
    }

    /**
     * 记录日志
     * @param string $msg
     * @return void
     */
    public function log($msg)
    {
        $msg = "[".date("Y-m-d H:i:s")."] " . $msg . "\n";
        if($this->log_show)
        {
            echo $msg;
        }
        if (!empty($this->log_file)) 
        {
            file_put_contents($this->log_file, $msg, FILE_APPEND | LOCK_EX);
        }
    }

}


