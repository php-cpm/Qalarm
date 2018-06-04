<?php
/**
 * Created by PhpStorm.
 * User: weichen
 * Date: 15/9/25
 * Time: 下午3:21
 */

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;


class QiNiuImgController extends Controller
{


    function __construct(){

    }


    function uploadToQiNiu(Request $request){

        header("Content-type:text/html;charset=utf-8");

        $accessKey = "WIOzjl79UpfOQjZnhhDOW1kK3u9ZYpKqNEf5uDmU";
        $secretKey = "ylIrcrgw7bd1wUH1dVQMkho4os2t-g0WX-vERGQB";

//        $bucket = "你的bucket";
        $bucket = "ttyc-gaea";

//        $host  = "你的七牛访问地址，譬如xxx.u.qiniudn.com";
        $host = "http://7xo3bd.com1.z0.glb.clouddn.com/";

        $time = time()+3600;

//        if(empty($_GET["key"])){
        if(empty($request->input('key'))){
            exit('param error');
        }else{
            $data =  array(
                "scope"=>$bucket.":".$request->input('key'),
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
    }


}
