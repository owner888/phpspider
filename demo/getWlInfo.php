<?php
/* Do NOT delete this comment */
/* 不要删除这段注释 */
require dirname(__FILE__).'/../core/init.php';
$configs = array(
		'name' => '物流帮帮',		//爬虫名称
		'log_show' => true, 	//是否显示日志
		'input_encoding' => null, //设置输入页面编码 为null 自动识别
		'output_encoding' => null, //设置输出页面编码 为null null则为utf-8
		'tasknum' => 1,				//同时工作的爬虫任务数 需要配合redis保存采集任务数据，供进程间共享使用TODO redis
		'interval' => 1000,			//设置爬虫爬取每个网页的时间间隔 单位毫秒
		'timeout' => 5,				//爬虫爬取每个网页的超时时间单位：秒
		'max_try' => 2,				//当爬虫爬页面失败后 重复爬取2次
		'max_depth' => 0,			//超过深度的页面不再采集 对于抓取最新内容的增量更新，抓取好友的好友的好友这类型特别有用 0 不限制
		'max_fields' => 0,		//爬虫爬取内容网页最大条数 默认值为0，即不限制
		//定义爬虫爬取哪些域名下的网页, 非域名下的url会被忽略以提高爬取速度
		'domains' => array(
			'www.56bb.net',
			'56bb.net',
		),
		//定义爬虫的入口链接, 爬虫从这些链接开始爬取,同时这些链接也是监控爬虫所要监控的链接
		'scan_urls' => array(
			"http://www.56bb.net/user_detail_line?lineId=55468",
			//'http://www.100056.cn/seacher?types=LineSearch&address=11087'
		),
		'content_url_regexes' => array(
			"http://www.56bb.net/user_detail_line?lineId=55468",
			//'http://www.100056.cn/company/\d+'
		),
		'fields' => array(
				array(
					// 抽取内容页的文章内容
					'name' => "gsmc",
					//'selector' => "/html/body/div[@class='main divc']/div[@class='main_left']/div[2]/ul[@id='uinfo']/li[2]",
					'selector' => "//div[@class='show_con_l_c wuliushow']/div[@class='con']/p[1]/span[@class='cname']",
					'required' => true
				),
		),
);
$spider = new phpspider($configs);
$spider->on_start = function($phpspider)
{
    requests::set_header("Host", "www.56bb.net");
    requests::set_header("Connection", "keep-alive");
    requests::set_header("Upgrade-Insecure-Requests", "1");    
    requests::set_header("User-Agent", "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36
    		");
    requests::set_header("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8");
    requests::set_header("Accept-Encoding", "gzip, deflate, sdch");
    requests::set_header("Accept-Language", "zh-CN,zh;q=0.8,en;q=0.6");
    requests::set_header("Cookie", "JSESSIONID=A277098377CE0224D71639825DF27A98; line.cookie='55468,55444'");
	requests::set_cookies("JSESSIONID=A277098377CE0224D71639825DF27A98; line.cookie='55468,55444'");
	// 把Cookie设置到 www.phpspider.org 域名下
	requests::set_cookies("NAME", "www.56bb.net");
};
$spider->start();
?>
