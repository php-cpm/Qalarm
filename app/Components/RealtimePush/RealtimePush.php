<?php
namespace App\Components\RealtimePush;

use Log;
use Redis;

use App\Components\Utils\HttpUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\Constants;

class RealtimePush 
{
    const STORE_CHANNEL  = 'TTYC_GEAE_NODE_SERVER_STORE';
    const PUBSUB_ChANNEL = 'TTYC_GEAE_NODE_SERVER_PUBSUB';

    const COUPON_NOTICE_ADD = 'realtime.opertor.notice.add';

    // 指定延缓提供者加载
    protected $defer = true;

    /**
     * @brief publish 
     * @Param $moduleName
     * @Param $data
     * @Param $adminId
     * @Return  true|false
     */
    public function publish($moduleName, $adminId='', $data = '')
    {
        if ($adminId == '') {
             $adminId = 'all';
        }
        $message = array();
        $message['message_id']  = MethodUtil::getUniqueId();
        $message['admin_id']    = $adminId;
        $message['module_name'] = $moduleName;
        $message['data']        = $data;

        $result = Redis::hset(self::STORE_CHANNEL, $message['message_id'], json_encode($message));
        if ($result) {
            Redis::publish(self::PUBSUB_ChANNEL, $message['message_id']);
        } else {
            // error log
        }
    }
}
