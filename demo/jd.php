<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = array(
    'name' => 'JD.com',
    'tasknum' => 1,
    'save_running_state' => false,
    'log_show' => true,
    'domains' => array(
        'list.jd.com',
        'item.jd.com',
    ),
    'scan_urls' => array(
        "https://list.jd.com/list.html?cat=1318,1463,12109",
        //"http://list.jd.com/list.html?cat=1318,1463,12109", // JD http 和 https 都支持
    ),
    'list_url_regexes' => array(
        "//list.jd.com/list.html\?cat=\d+,\d+,\d+",
    ),
    'content_url_regexes' => array(
        "//item.jd.com/\d+.html",
    ),
    'export' => array(
        'type' => 'csv',
        'file' => PATH_DATA.'/jd.csv',
    ),
    'fields' => array(
        // 商品名
        array(
            'name' => "good_name",
            'selector' => "//*[contains(@class,'sku-name')]",
            'required' => true,
        ),
        // 商品URL
        array(
            'name' => "good_url",
            'selector' => "//*[contains(@class,'sku-name')]",
            'required' => true,
        ),
        // 商品价格
        array(
            'name' => "price",
            'selector' => "//*[contains(@class,'p-price')]/span",
            'required' => true,
        ),
        // 商品分类
        array(
            'name' => "category",
            'selector' => '//a[@clstag="shangpin|keycount|product|mbNav-4"]/@href',
            'required' => true,
        ),
        // 商品品牌
        array(
            'name' => "brand",
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
    if($fieldname =='good_url'){
        $data = $page['request']['url'];
    }elseif($fieldname =='price'){
        // 默认赋值为-1,表示未获取到商品价格
        $data = '-1';
        // JD 价格是通过AJAX获取的
        preg_match('#//item.jd.com/(\d+).html#i',$page['request']['url'],$sku);
        if(!empty($sku[1])){
            //价格在这里 去http://p.3.cn/prices/mgets?skuIds= SKUID 获取
            $response = requests::get("http://p.3.cn/prices/mgets?skuIds=J_".$sku[1]);
            $priceObj = json_decode($response,1);
            if(!empty($priceObj[0]['p'])){
                $data = $priceObj[0]['p'];
            }
        }
    }elseif($fieldname =='shop_url'){
        // 处理店铺URL
        $data = preg_replace('#^(https?:)?//#i','',$data);
        $parse_url = @parse_url($page['request']['url']);
        $data = $parse_url['scheme']."://".$data;
    }elseif ($fieldname == 'category'){
        if(!empty($data)){
            // 获取到URL,分析其cat 和 brand
            // //list.jd.com/list.html?cat=1318,1463,12109&ev=exbrand_10357
            preg_match('#cat=(\d+),(\d+),(\d+)&#i',$data,$cats);
            if(count($cats) > 1){
                unset($cats[0]);
                $data = implode(',',$cats);
            }
        }
    }elseif ($fieldname == 'brand'){
        if(!empty($data)){
            // 获取到URL,分析其cat 和 brand
            // //list.jd.com/list.html?cat=1318,1463,12109&ev=exbrand_10357
            preg_match('#ev=exbrand_(\d+)#i',$data,$brand);
            if(count($brand) > 1){
                $data = $brand[1];
            }
        }
    }
    return $data;
};

$spider->start();
