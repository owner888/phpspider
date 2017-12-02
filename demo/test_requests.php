<?php
ini_set("memory_limit", "10240M");
require_once __DIR__ . '/../autoloader.php';
use phpspider\core\phpspider;
use phpspider\core\requests;
use phpspider\core\selector;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$xml =<<<STR
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:comm3="http://www.csapi.org/schema/parlayx/common/v3_1" xmlns:sms7="http://www.csapi.org/schema/parlayx/sms/notification/v3_1/local"><SOAP-ENV:Header><comm3:NotifySOAPHeader><spId>67</spId><SAN>95122272</SAN></comm3:NotifySOAPHeader></SOAP-ENV:Header><SOAP-ENV:Body SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"><sms7:notifySmsReception><sms7:correlator></sms7:correlator><sms7:message><message>Y</message><senderAddress>959340080617</senderAddress><smsServiceActivationNumber>95122272</smsServiceActivationNumber><dateTime>2017-09-14T17:05:24+06:30</dateTime></sms7:message></sms7:notifySmsReception></SOAP-ENV:Body></SOAP-ENV:Envelope>
STR;
$url = "http://119.28.70.97:80/notify/mectel.php";
echo requests::post($url, $xml);
exit;

//$html =<<<STR
    //<td data-value="3.80">3.80</td>    
    //<td data-value="3.80">3.80</td>    
    //<td data-value="3.80">3.80</td>    
    //<td data-value="3.80">3.80</td>    
//STR;

//$data = selector::select($html, "//td@data-value");
//print_r($data);
//exit;

$html =<<<STR
    <div id="demo1">
        demo1
    </div>
    <div id="demo2">
        demo2
    </div>
STR;

// 这里能获取demo1和demo2的内容
$data = selector::select($html, "//div[contains(@id,'demo')][last()]");

print_r($data);
exit;
$html =<<<STR
    <div rel="demo">
        <span class="tt">bbb</span>
        <span>ccc</span>
        <p>ddd</p>
    </div>
STR;

// 获取id为demo的div内容
$data = selector::select($html, "//div[@rel='demo']");
//$data = selector::select($html, "//div[contains(@id,'demo')]");
//$data = selector::select($html, "#demo", "css");
print_r($data);
exit;

$html = requests::get('http://www.qiushibaike.com/article/118914171');
//echo $html;
//exit;
$data = selector::select($html, "div.author", "css");
echo $data;
