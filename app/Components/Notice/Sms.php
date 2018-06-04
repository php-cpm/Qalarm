<?php

namespace App\Components\Notice;

use App\Components\Utils\LogUtil;
use App\Components\Utils\HttpUtil;              


class Sms
{
    protected static $gatewaySit = 'http://api.sit.ffan.com/msgcenter/v1/smsOutboxes';
    protected static $gateway    = 'http://api.ffan.com/msgcenter/v1/smsOutboxes';

    const SMS_TEMPLATE_ALARM_ID = 284;
    

    /**
        * @brief send 万达的短信通道只支持单发
        *
        * @param $mobiles
        * @param $params
        * @param $templateId
        *
        * @return 
     */
    public static function send($mobile, $params = [], $templateId = self::SMS_TEMPLATE_ALARM_ID)
    {
        $res = self::request([
            'templateId'    =>  $templateId,
            'deviceList'    =>  json_encode([$mobile]),
            'deviceType'    => 0,
            'contentType'   => 0,
            'argsList'      => json_encode($params, JSON_UNESCAPED_UNICODE),
            'validTime'     => time() + 120,
            ]);

        return $res;
    }

    // 发送接口请求
    protected static function request($data = [])
    {
        if (app()->environment('local')) {
            $baseurl = static::$gatewaySit;
        } else {
            $baseurl = static::$gateway;
        }

        $url = $baseurl;


        $curl_timeout = 0;
        if (isset($data['code']) && $data['code'] == 1 && isset($data['subcode']) && $data['subcode'] == 2) {
            $curl_timeout = 20;
        }

        $raw = HttpUtil::httpRequest($url, $data, [], $curl_timeout);

        $res = json_decode($raw, true);

        if (is_null($res)) {
            return false;
        }

        // string(70) "{"status":200,"message":"OK","data":{"smsOutboxId":20160616190807563}}"

        // 错误返回
        if ($res['status'] != 200) {
            LogUtil::notice('Send sms failed', $res);
            return false;
        }

        if (empty($res['data'])) {
            return true;
        }

        return $res['data'];
    }

}
