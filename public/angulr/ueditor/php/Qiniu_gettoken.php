<?php

require('Qiniu_conf.php');

header("Content-type:text/html;charset=utf-8");

$accessKey = QiniuConf::QINIU_ACCESS_KEY;
$secretKey = QiniuConf::QINIU_SECRET_KEY;
$bucket = QiniuConf::QINIU_BUCKET;
//注意后面的'/'符号
$host = QiniuConf::QINIU_HOST.'/';

$time = time()+3600;

if(empty($_GET["key"])){
    exit('param error');
}else{
    $data =  array(
        "scope"=>$bucket.":".$_GET["key"],
        "deadline"=>$time,
        "returnBody"=>"{\"url\":\"{$host}$(key)\", \"state\": \"SUCCESS\", \"name\": $(fname),\"size\": \"$(fsize)\",\"w\": \"$(imageInfo.width)\",\"h\": \"$(imageInfo.height)\"}"
    );
}

$data = json_encode($data);
$find = array('+', '/');
$replace = array('-', '_');
$data = str_replace($find, $replace, base64_encode($data));
$sign = hash_hmac('sha1', $data, $secretKey, true);
$result = $accessKey . ':' . str_replace($find, $replace, base64_encode($sign)).':'.$data ;
echo $result;