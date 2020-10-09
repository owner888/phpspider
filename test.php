<?php

$str = hash('sha256', 'bc');
echo strlen($str);
exit;
$data = array("url" => 'http://www.test.com');  
$data = http_build_query($data);
// Create a stream
$opts = [
    //"http" => [
        //"method" => "POST",
        //"header" => "Content-Type: multipart/form-data\r\n",
        //"content" => $data,
    //],
    "ssl" => array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
    ),
];

$context = stream_context_create($opts);

// Open the file using the HTTP headers set above
$file = file_get_contents('https://api.potato.im:8443/10100386:Z0dT3Oalvu5IGC71OrvGs3hT/setWebhook', false, $context);

var_dump($file);    
