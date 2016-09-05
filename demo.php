<?php
ini_set("memory_limit", "1024M");
include "core/worker.php";

if (util::lock('phpspider'))
{
    $errmsg = "phpspider process has been locked";
    echo $errmsg."\n";
    log::add($errmsg, "Warning");
    exit(0);
}

$count = 8;

$w = new worker();
$w->count = $count;
$w->run_once = true;
$w->log_show = false;

$w->on_start = function($worker) {

}; 

$w->on_worker_start = function($worker) {

};

$w->on_worker_stop = function($worker) {
    $time = microtime(true) - $worker->time_start;
    $memory = util::memory_get_peak_usage();
    echo "Worker[{$worker->worker_id}] Done in $time seconds\t $memory\n";
}; 

$w->on_stop = function($worker) {
    util::unlock('phpspider');

    $time = microtime(true) - $worker->time_start;
    $memory = util::memory_get_peak_usage();
    echo "Done in $time seconds\t $memory\n";
}; 
$w->run();
