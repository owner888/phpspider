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
class worker
{
    // worker进程数
    public $count = 0;
    // worker id，worker进程从1开始，0被master进程所使用
    public $worker_id = 0;
    // worker 进程ID
    public $worker_pid = 0;
    // 进程用户
    public $user = '';
    // 进程名
    public $title = '';
    // 每个进程是否只运行一次
    public $run_once = true;
    // 是否输出日志
    public $log_show = false;
    // master进程启动回调
    public $on_start = false;
    // master进程停止回调
    public $on_stop = false;
    // worker进程启动回调
    public $on_worker_start = false;
    // worker进程停止回调
    public $on_worker_stop = false;
    // master进程ID
    protected static $_master_pid = 0;
    // worker进程ID
    protected static $_worker_pids = array();
    // master、worker进程启动时间
    public $time_start = 0;
    // master、worker进程运行状态 [starting|running|shutdown|reload]
    protected static $_status = "starting";


    public function __construct()
    {
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
                // master进程和worker进程都会调用
                $this->stop_all();
                break;
            // reload 30
            case SIGUSR1:
                echo "reload\n";
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
        $this->time_start = microtime(true); 
        $this->worker_id = 0;
        $this->worker_pid = posix_getpid();
        $this->set_process_title($this->title);

        // 这里赋值，worker进程也会克隆到
        if ($this->log_show) 
        {
            log::$log_show = true;
        }

        if ($this->on_start) 
        {
            call_user_func($this->on_start, $this);
        }

        // worker进程从1开始，0被master进程所使用
        for ($i = 1; $i <= $this->count; $i++) 
        {
            $this->fork_one_worker($i);
        }
        $this->monitor_workers();
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

        // 主进程记录子进程pid
        if($pid > 0)
        {
            self::$_worker_pids[$worker_id] = $pid;
        }
        // 子进程运行
        elseif(0 === $pid)
        {
            $this->time_start = microtime(true);
            $this->worker_id = $worker_id;
            $this->worker_pid = posix_getpid();
            $this->set_process_title($this->title);
            $this->set_process_user($this->user);
            // 清空master进程克隆过来的worker进程ID
            self::$_worker_pids = array();
            //$this->uninstall_signal();

            // 设置worker进程的运行状态为运行中
            self::$_status = "running";

            // 注册进程退出回调，用来检查是否有错误(子进程里面注册)
            register_shutdown_function(array($this, 'check_errors'));

            // 如果设置了worker进程启动回调函数
            if ($this->on_worker_start) 
            {
                call_user_func($this->on_worker_start, $this);
            }

            // 停止当前worker实例
            $this->stop();
            // 这里用0表示正常退出
            exit(0);
        }
        else
        {
            log::add("fork one worker fail", "Error");
            exit;
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
                log::add('Can not run woker as '.$user_name." , You shuld be root", "Error");
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
        // 设置master进程的运行状态为运行中
        self::$_status = "running";
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
                //$this->stop();

                // 如果不是正常退出，是被kill等杀掉的
                if($status !== 0)
                {
                    log::add("worker {$pid} exit with status $status", "Warning");
                }

                // key 和 value 互换
                $worker_pids = array_flip(self::$_worker_pids);
                // 通过 pid 得到 worker_id
                $worker_id = $worker_pids[$pid];
                // 这里不unset掉，是为了进程重启
                self::$_worker_pids[$worker_id] = 0;
                //unset(self::$_worker_pids[$pid]);

                // 再生成一个worker
                if (!$this->run_once) 
                {
                    $this->fork_one_worker($worker_id);
                }

                // 如果所有子进程都退出了，触发主进程退出函数
                $all_worker_stop = true;
                foreach (self::$_worker_pids as $_worker_pid) 
                {
                    // 只要有一个worker进程还存在进程ID，就不算退出
                    if ($_worker_pid != 0) 
                    {
                        $all_worker_stop = false;
                    }
                }
                if ($all_worker_stop) 
                {
                    if ($this->on_stop) 
                    {
                        call_user_func($this->on_stop, $this);
                    }
                    exit(0);
                }
            }
            // 其他信号
            else 
            {
                // worker进程接受到master进行信号退出的，会到这里来
                if ($this->on_stop) 
                {
                    call_user_func($this->on_stop, $this);
                }
                exit(0);
            }
        }
    }

    /**
     * 执行关闭流程(所有进程)
     * 事件触发，非正常程序执行完毕
     * @return void
     */
    public function stop_all()
    {
        // 设置master、worker进程的运行状态为关闭状态
        self::$_status = "shutdown";
        // master进程
        if(self::$_master_pid === posix_getpid())
        {
            // 循环给worker进程发送关闭信号
            foreach (self::$_worker_pids as $worker_pid) 
            {
                posix_kill($worker_pid, SIGINT);
            }
        }
        // worker进程
        else 
        {
            // 接收到master进程发送的关闭信号之后退出，这里应该考虑业务的完整性，不能强行exit
            $this->stop();
            exit(0);
        }
    }

    /**
     * 停止当前worker实例
     * 正常运行结束和接受信号退出，都会调用这个方法
     * @return void
     */
    public function stop()
    {
        if ($this->on_worker_stop) 
        {
            call_user_func($this->on_worker_stop, $this);
        }
        // 设置worker进程的运行状态为关闭
        self::$_status = "shutdown";
    }

    /**
     * 检查错误，PHP exit之前会执行
     * @return void
     */
    public function check_errors()
    {
        // 如果当前worker进程不是正常退出
        if(self::$_status != "shutdown")
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
            log::add($error_msg, 'Error');
        }
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
}
