<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;
use Redis;

use Carbon\Carbon; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\LogUtil;
use App\Components\Push\BasePush;

class RedisModel extends Model
{
    const PUSH_KEY_PREFIX = 'PUSH';
    const CAPTCHA_KEY_PREFIZ = 'CAPTCHA';
    const PUSH_APNS_INVAIL_TOKEN_PREFIX = 'APNS';
    const PUSH_KEY_TTL = 604800; // 86400 * 7
    const GAEA_CAPTCHA_REDIS_KEY_PREFIX = 'gaea_captcha_';

    /**
     * @brief getDate 得到当前天得日期
     * @Return  
     */
    public static function getDate()
    {
        return date('Y-m-d');
    }

    /**
     * @brief getYesterday 得到昨天
     *
     * @Return  
     */
    public static  function getYesterday()
    {
        return date('Y-m-d', strtotime("-1 day"));
    }

    public static function setPush($spType, $taskId, $token, array $value) 
    {
        $data = json_encode($value);
        $pushId = $value['pushid'];
        $key = self::PUSH_KEY_PREFIX.':'.self::getDate().':'.$spType.':'.$taskId.':'.$token;
        Redis::hset($key, 'send', $data);
        Redis::set($pushId, $key);

    }


    /**
     * @brief getPushValue 
     *
     * @Param $key
     *
     * @Return   array('send'=>array(), 'recv'=>array(), 'result'=>'')
     */
    public static function getPushValue($key)
    {
        $send = Redis::hget($key, 'send');
        $recv = Redis::hget($key, 'recv');
        $result = Redis::hget($key, 'result');
        $click = Redis::hget($key, 'click');
        $appReport = Redis::hget($key, 'app_report');

        return array('send'=>json_decode($send, true), 'recv'=>json_decode($recv, true), 'result'=>$result, 'report'=>$appReport, 'click'=>$click);
    }

    /**
     * @brief getDailyPushRecord 得到一天的数据key
     *
     * @Return  
     */
    public static function getDailyPushRecords($date)
    {
        $ixintui = Redis::keys(self::PUSH_KEY_PREFIX.':'.$date.':'.BasePush::PUSH_PROVIDER_IXINTUI.'*');
        $getui = Redis::keys(self::PUSH_KEY_PREFIX.':'.$date.':'.BasePush::PUSH_PROVIDER_GETUI.'*');
        $apns = Redis::keys(self::PUSH_KEY_PREFIX.':'.$date.':'.BasePush::PUSH_PROVIDER_APNS.'*');
        $xiaomi = Redis::keys(self::PUSH_KEY_PREFIX.':'.$date.':'.BasePush::PUSH_PROVIDER_XIAOMITUI.'*');

        if ($ixintui == null) {
            $ixintui = array();
        }

        $tmp = array_merge($ixintui, $getui);
        $tmp = array_merge($tmp, $xiaomi);
        return array_merge($tmp, $apns);
    }


    /**
     * @brief setResult 
     * send的数据格式:{"status":"", "desc":""} status 是规范化的错误码
     * recv的数据格式:{"status":"", "desc":""} status也是规范化的错误码
     * result的数据格式 int , 1表示发送接收正常，其他值表示有问题
     *
     * @Param $spType
     * @Param $taskId
     * @Param $value
     *
     * @Return  
     */
    public static function setResult($spType, $taskId, $token, array $value)
    {
        $key = self::PUSH_KEY_PREFIX.':'.self::getDate().':'.$spType.':'.$taskId.':'.$token;
        $push = Redis::hget($key, 'send');
        // 在前一天中查询
        if ($push == null) {
            $key = self::PUSH_KEY_PREFIX.':'.self::getYesterday().':'.$spType.':'.$taskId.':'.$token;
            $push = Redis::hget($key, 'send');
        }

        if ($push == null) {
            // LogUtil::warning(LogUtil::LOG_PUSH, 'key not in redis', ['key'=>$key, 'value'=>$value]);
            return false;
        }
        
        $send = json_decode($push, true);
        // 如果正常则记录一个结果就行，不用记录revc内容
        if ($send['status'] == ErrorCodes::ERR_SUCCESS && $value['status'] == ErrorCodes::ERR_SUCCESS) {
            Redis::hset($key, 'recv', json_encode($value));
            Redis::hset($key, 'result', ErrorCodes::ERR_SUCCESS);
        } else {
            Redis::hset($key, 'recv', json_encode($value));
            Redis::hset($key, 'result', ErrorCodes::ERR_FAILURE);
        }
    }
    
    public static function setClick($spType, $taskId, $token)
    {
        $key = self::PUSH_KEY_PREFIX.':'.self::getDate().':'.$spType.':'.$taskId.':'.$token;
        $push = Redis::hget($key, 'send');
        // 在前一天中查询
        if ($push == null) {
            $key = self::PUSH_KEY_PREFIX.':'.self::getYesterday().':'.$spType.':'.$taskId.':'.$token;
            $push = Redis::hget($key, 'send');
        }

        if ($push == null) {
            LogUtil::warning(LogUtil::LOG_PUSH, 'key not in redis when update click', ['key'=>$key, 'value'=>ErrorCodes::ERR_SUCCESS]);
            return false;
        }
        
        Redis::hset($key, 'click', ErrorCodes::ERR_SUCCESS);
    }
    
    public static function setClickByPushId($pushId)
    {
        $key = Redis::get($pushId);
        if ($key == null) {
            error_log($pushId."\n", 3, '/data/request.log');
        }

        Redis::hset($key, 'click', ErrorCodes::ERR_SUCCESS);
    }

    /**
     * @brief setAppReport 
     * @Param $pushId
     * @Return  
     */
    public static function setAppReport($pushId)
    {
        $key = Redis::get($pushId);
        if ($key == null) {
            error_log($pushId."\n", 3, '/data/request.log');
            // LogUtil::warning(LogUtil::LOG_PUSH, 'pushId not find in redis', ['key'=>$key, 'pushid'=>$pushId]);
        }

        Redis::hset($key, 'app_report', ErrorCodes::ERR_SUCCESS);
    }

    /**
     * @brief setInvildApnsToken 插入非法的apns token
     * example: {..,"996":{"timestamp":1439023129,"tokenLength":32,"deviceToken":"0f81685699261d331515a726d9f503601209b19b42ebf8363849bc680ff90096"},"997":{"timestamp":1439024954,"tokenLength":32,"deviceToken":"0f8181cd3aaf037448070e06ee54e2fec9b2908be14efb75e51ee5fc41c0c335"},"998":{"timestamp":1439023797,"tokenLength":32,"deviceToken":"0f850d886d459750381abce6c40d5db798601fbffa3c21fe10592002cd3f43b2"},"...":"Over 1000 items, aborting normalization"}
     * @Param $tokens
     *
     * @Return  
     */
    public static function setInvildApnsToken($tokens)
    {
        foreach ($tokens as $index => $one) {
            // 去除最后的一个item
            if (!is_numeric($index)) {
                break;
            }

            $key = self::PUSH_APNS_INVAIL_TOKEN_PREFIX.':'.date('Y-m-d', $one['timestamp']);
            $field = Redis::hlen($key) + 1;
            Redis::hset($key, $field, $one['deviceToken']);
        }
    }

    /**
     * @brief checkCaptchaCanUse  检查用户是否可以继续使用验证码功能
     * 一天最多能使用6次验证码功能，如果超过报警
     * @Param $mobile
     * @Param $serviceType
     * @Return  true|false
     */
    public static function checkCaptchaCanUse($mobile, $serviceType = 'call')
    {
        $MAX_USE_COUNT = 6;

        $key = self::CAPTCHA_KEY_PREFIZ.':'.$serviceType.':'.$mobile;
        $oldValue = Redis::get($key);
        if ($oldValue!= null) {
            $tmp = json_decode($oldValue, true);
            $timestamp = $tmp['timestamp'];
            $count = $tmp['count'];
            $carbon = new Carbon();
            $now = $carbon->toDateString();
            $before = Carbon::createFromTimestamp($timestamp)->toDateString();
            if ($now == $before) {
                if ($count < $MAX_USE_COUNT) {
                    $newValue = array('timestamp'=>time(), 'count'=>($count+1));
                    Redis::set($key, json_encode($newValue));
                    return true;
                } else {
                    return false;
                }
            } else {
                $newValue = array('timestamp'=>time(), 'count'=>1);
                Redis::set($key, json_encode($newValue));
                return true;
            }
        } else {
            $newValue = array('timestamp'=>time(), 'count'=>1);
            Redis::set($key, json_encode($newValue));
            return true;
        }
    }
}
