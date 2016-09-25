<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */


$spider = new phpspider();
$url = "http://www.epooll.com/archives/806/";
$html = $spider->request_url($url);

$fieldname = "标题";
$selector = "//div[contains(@class,'page-header')]//h1/a";

$result = $spider->get_fields_xpath($html, $selector, $fieldname);
print_r($result);

$spider->on_extract_field = function($fieldname, $data, $page) 
{
    if ($fieldname == 'contents') 
    {
        if (!empty($data))
        {
            $contents = $data;
            $data = "";
            foreach ($contents as $content) 
            {
                $data .= $content['page_content'];
            }
        }
    }
    return $data;
};

