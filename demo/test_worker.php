<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

cls_redis::set("name", "yzt");
echo cls_redis::get("name")."\n";

cls_redis::set_connect("redis_test", $GLOBALS['config']['redis_test']);
cls_redis::set("name", "owner");
echo cls_redis::get("name")."\n";

$sql = "Select * From `test`";
$row = db::get_one($sql);
print_r($row);

db::set_connect("test", $GLOBALS['config']['test']);
$sql = "Select * From `test`";
$row = db::get_one($sql);
print_r($row);

$w = new worker();
$w->count = 1;

$w->on_worker_start = function($worker) {

    $sql = "Select * From `test`";
    $row = db::get_one($sql);
    print_r($row);


    //cls_redis::set_connect_default();
    echo cls_redis::get("name")."\n";
    exit;

    echo $worker->worker_id."\n";
    if ($worker->worker_id == 1) 
    {
    }
    else 
    {
        sleep(3);
    }
    //while(true) {
        //pcntl_signal_dispatch();
        
        //for($j = 0; $j < 2; $j++) {
            //echo '.';
            //sleep(5);
        //}
        //echo "\n";
    //}
    //for ($i = 0; $i < 10; $i++) 
    //{
        //pcntl_signal_dispatch();
        //echo $i." --- ".$worker->worker_id."\n";
        //sleep(3);
    //}
}; 
$w->on_worker_stop = function($worker) {
    //var_dump($worker->worker_id);
};
$w->run();
