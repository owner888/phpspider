<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$spider = new phpspider();

$spider->add_cookies('ucplatform=%7C%7C; bdshare_firstime=1474271750347; sid=839acdc7-d05d-4369-80c4-3d54c654e28a; newuser_time=1474469552250; brand=unknown; SnsAutoPageType=pc; ch=JY_3; broswerGameIds_v2=629864; myspacetask_1300638943=%2Ctasklogin; game9cpadvbundefined=game9cpadvbundefined; Hm_lvt_e6e890e978a935949b61a6f4a5752fb7=1474352939; Hm_lpvt_e6e890e978a935949b61a6f4a5752fb7=1476291911; uaInfos=--|----|----|----|--1--|--; from=bbs_top; JWS_SESSION="4794335fd428935bfe0fe5e552f43c4ca42e94b6-___ID=839acdc7-d05d-4369-80c4-3d54c654e28a"; _pk_ref.01fdc7be6b1a.747d=%5B%22%22%2C%22%22%2C1476325790%2C%22http%3A%2F%2Fi.9game.cn%2Ffknsg2%2F%22%5D; Hm_lvt_40d8360b348e0383d901f6fd54a7c494=1474438562,1474447003,1476291948,1476297606; Hm_lpvt_40d8360b348e0383d901f6fd54a7c494=1476325832; _pk_id.01fdc7be6b1a.747d=b4642a6c-2832-4d20-8ba8-8cae9d94184a.1474271750.8.1476325832.1476298112.; _pk_ses.01fdc7be6b1a.747d=*; _pk_ref.498.747d=%5B%22%22%2C%22%22%2C1476325842%2C%22http%3A%2F%2Fapi.open.uc.cn%2Fcas%2Flogin%3Fclient_id%3D191%26v%3D1.1%26redirect_uri%3Dhttp%253A%252F%252Fbbs.9game.cn%252Fplugin.php%253Fid%253Dcas%253AuserPlatformCallback%2526act%253D2%2526requestid%253D8a77d223-47ed-480e-a362-915815f1dd8d%26display%3Dmobile%26browser_type%3Dhtml5%22%5D; 5s5t_2132_saltkey=ZdufZIAQ; 5s5t_2132_lastvisit=1476322637; 5s5t_2132_iosclient=android; 5s5t_2132_visitedfid=343; SessionId=7f9df54e0cad7ee767313cdb22cc29db; 5s5t_2132_ulastactivity=1476326252%7C0; 5s5t_2132_auth=cc1c5yOVpp0C3x1ruN6PUAbSAUsPr6M3yverpFb%2BO2621v1vvfHclsES1O4%2FS2dsP%2BR%2F4HyPpUSakjOfjApgiOxB6evGWA; 5s5t_2132_lastcheckfeed=62570021%7C1476326252; 5s5t_2132_checkfollow=1; 5s5t_2132_viewid=tid_20714877; SnsPageVersion=touch; 5s5t_2132_uaMark=1; _pk_id.498.747d=47e49181-4fe6-4493-b1a7-ae08a3ded7b5.1474441446.6.1476326254.1476298198.; _pk_ses.498.747d=*; 5s5t_2132_sdkreferer=http%3A//bbs.9game.cn/thread-20714877-1-1.html%3Fmobile%3Dyes; 5s5t_2132_lastact=1476326263%09extstatis.php%09statis; statis=del; tid=15076c5f99570a33d9e0468b73f1f905');

//$spider->add_header("Referer", "http://bbs.9game.cn/thread-20731443-1-1.html");

$url = "http://bbs.9game.cn/forum.php?mod=post&action=reply&fid=343&tid=20762756&extra=Array&replysubmit=yes&infloat=yes&mobile=yes&ajxa=1";
$params = array(
    'formhash'=>'e8eef43c',
    'usesig'=>1,
    'message'   => "活动求关注哦哦",
    'Filedata'=>'',
);

$options = array(
    'method' => 'post',
    'params' => $params,
);

$html = $spider->request_url($url, $options);
//$url = "http://myspace.9game.cn/message/index";
//$html = $spider->request_url($url);
var_dump($html);
//$cookies = $spider->get_cookies();
//print_r($cookies);
exit;


$fields = array(
    'client_id'=>'191',
    'redirect_uri'=>'http://bbs.9game.cn/plugin.php?id=cas:userPlatformCallback&act=2&requestid=8a77d223-47ed-480e-a362-915815f1dd8d',
    'target_client_id'=>'',
    'target_redirect_uri'=>'',
    'display'=>'mobile',
    'change_uid'=>0,
    'loginNameNew'=>'',
    'riskassessment_token'=>'',
    'scene'=>'',
    'u'=>'',
    'o'=>'',
    'ua_token'=>'',
    'loginName'=>'1300638943',
    'password'=>'123456',
    'captchaVal'=>'c3gx',
    'captchaId'=>'st269964-a0f8d288547e696wb41f9a0682478dc217217682',
);
$options = array(
    'method' => 'post',
    'fields' => $fields,
);

$url = "https://api.open.uc.cn/cas/login/commit?uc_param_str=einisimemsnnutvelafrpfmibiupds";

//// 登录请求url
//$url = "http://www.waduanzi.com/login?url=http%3A%2F%2Fwww.waduanzi.com%2F";
//// 提交的参数
//$options = array(
    //'method' => 'post',
    //'fields' => array(
        //"LoginForm[returnUrl]" => "http%3A%2F%2Fwww.waduanzi.com%2F",
        //"LoginForm[username]" => "13712899314",
        //"LoginForm[password]" => "854230",
        //"yt0" => "登录",
    //)
//);
$spider->add_cookies('sid=839acdc7-d05d-4369-80c4-3d54c654e28a; newuser_time=1474469552250; brand=unknown; SnsAutoPageType=pc; ch=JY_3; broswerGameIds_v2=629864; myspacetask_1300638943=%2Ctasklogin; game9cpadvbundefined=game9cpadvbundefined; JWS_SESSION="4794335fd428935bfe0fe5e552f43c4ca42e94b6-___ID=839acdc7-d05d-4369-80c4-3d54c654e28a"; Hm_lvt_e6e890e978a935949b61a6f4a5752fb7=1474352939; Hm_lpvt_e6e890e978a935949b61a6f4a5752fb7=1476291911; uaInfos=--|----|----|----|--1--|--; SessionId=7bf89f6a76ac2730ac4df77da91d03c4; SnsPageVersion=touch; from=bbs_top; tid=f9153f93b51e7f83774a0751e374fdb4');
//$html = $spider->request_url($url, $options);
$url = "http://myspace.9game.cn/message/index";
$html = $spider->request_url($url);
//var_dump($html);
$cookies = $spider->get_cookies();
print_r($cookies);

//var_dump($html);
