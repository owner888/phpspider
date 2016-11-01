<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */


// 测试页面元素抽取
//$html = requests::get("http://item.jd.com/10393080524.html");
//$data = selector::select($html, "//div[contains(@class,'crumb-wrap')]//div[contains(@class,'contact')]//div[contains(@class,'name')]//a/@href");
//print_r($data);
//echo "\n";
//exit;

$configs = array(
    'name' => 'JD.com',
    'tasknum' => 1,
    'save_running_state' => false,
    //'log_show' => true,
    'domains' => array(
        'list.jd.com',
        'item.jd.com',
    ),
    'scan_urls' => array(
        "http://list.jd.com/list.html?cat=9847,9849,9870",
        //"http://list.jd.com/list.html?cat=1318,1463,12109", // JD http 和 https 都支持
    ),
    'list_url_regexes' => array(
        "http://list.jd.com/list.html\?cat=\d+,\d+,\d+",
    ),
    'content_url_regexes' => array(
        "http://item.jd.com/\d+.html",
    ),
    'export' => array(
        'type' => 'db',
        'table' => 'jd_goods',
        //'type' => 'csv',
        //'file' => PATH_DATA.'/jd_goods.csv',
    ),
    'fields' => array(
        // 商品ID
        array(
            'name' => "goods_id",
            'selector' => '//a[@clstag="shangpin|keycount|product|guanzhushangpin_2"]/@data-id',
            'required' => true,
        ),
        // 商品名
        array(
            'name' => "goods_name",
            'selector' => "//*[contains(@class,'sku-name')]",
            'required' => true,
        ),
        // 商品URL
        array(
            'name' => "goods_url",
            'selector' => "//*[contains(@class,'sku-name')]",
            'required' => true,
        ),
        // 商品价格
        array(
            'name' => "goods_price",
            'selector' => "//*[contains(@class,'p-price')]/span",
            'required' => true,
        ),
        // 商品优惠信息
        //array(
            //'name' => "goods_quan",
            //'selector' => "//*[contains(@class,'quan-item')]/span",
            //'required' => true,
        //),
        // 商品评价总数
        //array(
            //'name' => "comment-count",
            //'selector' => '//*[@id="comment-count"]/a',
            //'required' => true,
        //),
        // 商品评价好评率
        //array(
            //'name' => "goods_rate",
            //'selector' => '//*[@class="rate"]/strong',
            //'required' => true,
        //),
        // 商品分类名称
        array(
            'name' => "category",
            'selector' => '//a[@clstag="shangpin|keycount|product|mbNav-3"]',
            'required' => true,
        ),
        // 商品分类URL
        array(
            'name' => "category_url",
            'selector' => '//a[@clstag="shangpin|keycount|product|mbNav-4"]/@href',
            'required' => true,
        ),
        // 商品品牌名称
        array(
            'name' => "brand",
            'selector' => '//a[@clstag="shangpin|keycount|product|mbNav-4"]',
            'required' => true,
        ),
        // 商品品牌url
        array(
            'name' => "brand_url",
            'selector' => '//a[@clstag="shangpin|keycount|product|mbNav-4"]/@href',
            'required' => true,
        ),
        // 店铺名
        array(
            'name' => "shop_name",
            'selector' => "//div[contains(@class,'crumb-wrap')]//div[contains(@class,'contact')]//div[contains(@class,'name')]//a",
            'required' => true,
        ),
        // 店铺URL
        array(
            'name' => "shop_url",
            'selector' => "//div[contains(@class,'crumb-wrap')]//div[contains(@class,'contact')]//div[contains(@class,'name')]//a/@href",
            'required' => true,
        ),
    ),
);

$spider = new phpspider($configs);
$spider->on_extract_field = function($fieldname, $data, $page)
{
    if($fieldname =='good_url')
    {
        $data = $page['request']['url'];
    }
    elseif($fieldname =='goods_price')
    {
        // 默认赋值为-1,表示未获取到商品价格
        $data = '-1';
        // JD 价格是通过AJAX获取的
        preg_match('#//item.jd.com/(\d+).html#i', $page['request']['url'], $sku);
        if(!empty($sku[1]))
        {
            //价格在这里 去http://p.3.cn/prices/mgets?skuIds= SKUID 获取
            $response = requests::get("http://p.3.cn/prices/mgets?skuIds=J_".$sku[1]);
            $price_data = json_decode($response, true);
            if(!empty($price_data[0]['p']))
            {
                $data = $price_data[0]['p'];
            }
        }
    }
    elseif($fieldname =='shop_url')
    {
        // 处理店铺URL
        $data = preg_replace('#^(https?:)?//#i','',$data);
        $parse_url = @parse_url($page['request']['url']);
        $data = $parse_url['scheme']."://".$data;
    }
    elseif ($fieldname == 'category')
    {
        if(!empty($data))
        {
            // 获取到URL,分析其cat 和 brand
            // //list.jd.com/list.html?cat=1318,1463,12109&ev=exbrand_10357
            preg_match('#cat=(\d+),(\d+),(\d+)&#i',$data,$cats);
            if(count($cats) > 1)
            {
                unset($cats[0]);
                $data = implode(',',$cats);
            }
        }
    }
    elseif ($fieldname == 'brand')
    {
        if(!empty($data))
        {
            // 获取到URL,分析其cat 和 brand
            // //list.jd.com/list.html?cat=1318,1463,12109&ev=exbrand_10357
            preg_match('#ev=exbrand_(\d+)#i',$data,$brand);
            if(count($brand) > 1)
            {
                $data = $brand[1];
            }
        }
    }
    return $data;
};

$spider->start();
/*
CREATE TABLE `jd_goods` (
  `goods_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '货品ID',
  `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '货品名称',
  `goods_url` varchar(255) NOT NULL DEFAULT '' COMMENT '货品的url',
  `goods_price` decimal(10,2) NOT NULL COMMENT '货品的价格',
  `goods_quan` varchar(255) NOT NULL DEFAULT '' COMMENT '货品的优惠信息',
  `comment-count` varchar(10) NOT NULL DEFAULT '' COMMENT '货品的评价总数',
  `goods_rate` varchar(10) NOT NULL DEFAULT '' COMMENT '货品的好评率',
  `category` varchar(255) NOT NULL DEFAULT '' COMMENT '商品分类url',
  `category_url` varchar(255) NOT NULL DEFAULT '' COMMENT '商品分类名称',
  `brand` varchar(255) NOT NULL DEFAULT '' COMMENT '商品品牌url',
  `brand_url` varchar(255) NOT NULL DEFAULT '' COMMENT '商品品牌名称',
  `shop_url` varchar(255) NOT NULL DEFAULT '' COMMENT '商品店铺url',
  `shop_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品店铺名称',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '抓取时间',
  `update_user` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
  PRIMARY KEY (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='京东商品表'i
*/
