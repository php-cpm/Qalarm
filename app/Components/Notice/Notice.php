<?php
namespace App\Components\Notice;

use App\Components\Utils\HttpUtil;
use Log;
use Qconf;

class Notice
{
    const FROM = 'gaea_client';
    const SECRET = 'ab37655347122540857b44950fca0ba3';

    const PUSH_APPID_TTYC_SHUNFENTCHE       = '520666';  // 顺风车
    const PUSH_APPID_MAGIC_GUANJIA          = '520668';  // 车管家
    const PUSH_APPID_MAGIC_CHEZHU           = '520670';  // 车主端

    const NOTICE_CHANNEL = 1;
    const MARKET_CHANNEL = 2;

    // 语音电话类型
    const CALL_PAY = 9; // 通知支付
    const CALL_CODE = 10; // 语言验证码

    private $pushParamMap = [
        'passenger_call' => [1, ['one_to_one' => 1, 'recommend_order' => 2, 'driver_remind' => 3]],
        'order_timeout' => [2, ['common' => 1]],
        'order_received' => [3, ['first_remind' => 1, 'second_remind' => 2]],
        'pay_timeout_passenger' => [4, ['common' => 1]],
        'pay_timeout_driver' => [5, ['common' => 1]],
        'order_payed' => [6, ['passenger' => 1, 'driver' => 2]],
        'order_cancel' => [7, ['common' => 1]],
        'pick_up_passenger' => [8, ['common' => 1]],
        'watiing_for_driver' => [9, ['common' => 1]],
        'order_payed_cancel' => [11, ['common' => 1]],
        'order_payed_cancel_deal' => [12, ['agree' => 1, 'reject' => 2]],
        'order_payed_cancel_auto' => [13, ['passenger' => 1, 'driver' => 2]],
        'add_friend' => [33, ['common' => 1]],
        'passenger_comment' => [41, ['common' => 1]],
        'add_coupon' => [53, ['common' => 1]],

        // 'passenger_confirm'=>[10,['common'=>1]],
        'passenger_confirm' => [42, ['passenger' => 1, 'driver' => 2]],
        'common_notice' => [31, ['common' => 1]],
    ];

    // 发送短消息
    public function sendSms($mobiles, $content, $type, $channel = 1, $appid=self::PUSH_APPID_TTYC_SHUNFENTCHE)
    {
        if (is_array($mobiles)) {
            $mobiles = implode(',', $mobiles);
        }

        $res = $this->request('/sms/send', [
            'from'       => self::FROM,
            'mobiles'    => $mobiles,
            'content'    => $content,
            'channel'    => $channel,
            'buss_type'  => $type,
            'appid'      => $appid,
        ]);

        return $res;
    }

    // 发送dindin
    public function sendDindin($mobiles, $content)
    {
        if (is_array($mobiles)) {
            $mobiles = implode(',', $mobiles);
        }

        $res = $this->request('/dindin/send', [
            'from'   => self::FROM,
            'mobiles'=> $mobiles,
            'content'=> $content
        ]);

        return $res;
    }

    // 获取dindin用户
    public function getDindinUsers()
    {
        $res = $this->request('/dindin/users', [
        ]);

        return $res;
    }

    // 发送推送消息
    public function pushByUser($user_id, $content, $params = [], $push_type = 0)
    {
        $res = $this->request('/push/pushbyuser', [
            'from' => self::FROM,
            'userid' => $user_id,
            'alert' => $content,
            'params' => $params,
            'iosinfo' => [],
            'pushtype' => $push_type,
        ]);

        return $res;
    }

    // 发送模版推送消息
    public function pushByTemplete($user_id, $code, $subcode, $params = [])
    {
        $data = [
            'from' => self::FROM,
            'userid' => $user_id,
            'params' => $params,
            'code' => $code,
            'subcode' => $subcode,
        ];

        $res = $this->request('/push/pushbytemplete', $data);
        return $res;
    }

    public function __call($name, $arguments)
    {
        //$arguments[0] user_id,$arguments[1] 推送接口参数,$arguments[2] 可选，当有推送自分类时为子分类的key。
        if (count($arguments) <= 1)
            return;
        //推送的子分类
        $subcodekey = 'common';
        if (count($arguments) == 3)
            $subcodekey = $arguments[2];
        $codepack = $this->pushParamMap[$name];
        $this->pushByTemplete($arguments[0], $codepack[0], $codepack[1][$subcodekey], $arguments[1]);
    }

    // 打电话
    public function call($mobile, $type, $content = '', $sync = 0, $custom = '')
    {
        $res = $this->request('/call/makecall', [
            'from' => self::FROM,
            'mobile' => $mobile,
            'type' => $type,
            'content' => $content,
            'sync' => $sync,
            'customefield' => $custom,
        ]);

        return $res;
    }

    // 通道可用性检查
    public function checkStatus()
    {
        return $this->request('/push/pushcheck');
    }

    // 生成签名验证
    protected function buildSign()
    {
        $timestamp = time();
        $rand = rand(1000, 9999999999);
        $sign = sha1(sprintf("%s%s%s", self::SECRET, $timestamp, $rand));

        return compact('timestamp', 'rand', 'sign');
    }

    // 发送接口请求
    protected function request($action, $data = [])
    {
        // 获取Url
        if (app()->environment('production', 'staging')) {
            if (class_exists('Qconf')) {
                $baseurl = Qconf::getHost('/qconf_root/notice/providers');
            } 

            if (empty($baseurl)) {
                $baseurl = 'http://10.6.4.111:12800';
            }
        } else {
            if (class_exists('Qconf')) {
                $baseurl = Qconf::getHost('/qconf_root/notice/providers_test');
            } 
            
            if (empty($baseurl)) {
                $baseurl = 'http://10.6.12.178:12800';
            }
        }

        $url = $baseurl . $action;
        $data = array_merge($data, $this->buildSign());

        // 针对于异步的对匹配车主推送做了超时时间的延长,curl_timeout = 0表示使用系统默认的超时时间
        $curl_timeout = 0;
        if (isset($data['code']) && $data['code'] == 1 && isset($data['subcode']) && $data['subcode'] == 2) {
            $curl_timeout = 20;
        }

        $raw = HttpUtil::httpRequest($url, $data, [], $curl_timeout);

        $res = json_decode($raw, true);
        if (is_null($res)) {
            return false;
        }

        // 错误返回
        if ($res['errno'] != 0) {
            Log::notice(__class__ . ' api response error', $res);
            return false;
        }

        if (empty($res['data'])) {
            return true;
        }

        return $res['data'];
    }
}
