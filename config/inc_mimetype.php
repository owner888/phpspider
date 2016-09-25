<?php
/**
 * 如需添加下载文件识别，这里是Content-type常用对照表
 * http://tool.oschina.net/commons 
 */

$GLOBALS['config']['mimetype'] = array(
    'application/octet-stream'  => 'binary',
    //'text/xml'                  => 'xml',
    //'text/html'                 => 'html',
    //'text/htm'                  => 'htm',
    //'text/plain'                => 'txt',
    'image/png'                 => 'png',
    'image/jpeg'                => 'jpg',
    'image/gif'                 => 'gif',
    'image/tiff'                => 'tiff',
    'image/x-jpg'               => 'jpg',
    'image/x-icon'              => 'icon',
    'image/x-img'               => 'img',
    'application/pdf'           => 'pdf',
    'audio/mp3'                 => 'mp3',
    'video/avi'                 => 'avi',
    'video/mp4'                 => 'mp4',
    'application/x-msdownload'  => 'exe',
    'application/vnd.iphone'    => 'ipa',
    'application/x-bittorrent'  => 'torrent',
    'application/vnd.android.package-archive' => 'apk',
);
