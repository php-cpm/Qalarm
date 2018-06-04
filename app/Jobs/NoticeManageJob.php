<?php
namespace App\Jobs;

use Log;
use Redis;

use App\Components\Util\TimeUtil;
use Carbon\Carbon;

use App\Components\RealtimePush\RealtimePush;
use App\Components\Utils\LogUtil;
use App\Models\Gaea\OpNotice;


class NoticeManageJob extends BaseJob
{
    protected $notice;

    public function __construct($notice) 
    {
        $this->notice = $notice;
        parent::__construct($notice);
    }

    protected function chunk($count = 500)
    {
        $all = array();
        if ($this->notice->notice_way == OpNotice::NOTICE_WAY_PUSH) {
            $data = explode("\n", $this->notice->notice_userids);
        } else {
            $data = explode("\n", $this->notice->notice_mobiles);
        }

        $idx = 0;
        $chunk = array();
        foreach ($data as $mobile) {
            if ($this->notice->notice_way != OpNotice::NOTICE_WAY_PUSH) {
                if (!preg_match("/1[34578]{1}\d{9}$/", $mobile)) {  
                    LogUtil::warning('错误的电话号码', ['mobile' => $mobile]);
                    continue;
                }
            }
            $chunk[] = $mobile;

            if ($idx > $count) {
                $all[] = join(',', $chunk);
                $idx = 0;
                $chunk = array();
            }
            $idx++;
        }
        // last chunk
        $all[] = join(',', $chunk);
        return $all;
    }

    public function doHandle()
    {
        $this->notice->started_at = Carbon::now();
        $chunks = $this->chunk(OpNotice::MAX_NOTICE_COUNT_ONCE);
        foreach ($chunks as $chunk) {
            if ($this->notice->notice_way == OpNotice::NOTICE_WAY_PUSH) {
                $params = [
                    'content' => $this->notice->notice_content,
                ];
                // app内跳转
                if ($this->notice->notice_type == OpNotice::NOTICE_TYPE_JUMP) {
                    $params['target'] = $this->notice->notice_link;
                }
                app('notice')->common_notice($chunk, $params);
            } 

            if ($this->notice->notice_way == OpNotice::NOTICE_WAY_SMS) {
                // 营销渠道需要加退订字样
                $content =  $this->notice->notice_content;
                if ($this->notice->sms_channel == OpNotice::SMS_CHANNEL_MARKET) {
                    $content .= '回T退订';
                }
                app('notice')->sendSms($chunk, $content, 12, $this->notice->sms_channel);
            } 

            // 如果只有一个chunk，则完成时才通知
            if (count($chunks) != 1) {
                #app('realtimePush')->publish(RealtimePush::COUPON_NOTICE_ADD, $this->notice->admin_user_id, ['id'=>'124', 'jump'=>'yes']);
            }

            $this->notice->completed_count += count(explode(',', $chunk));
            $this->notice->save();

        }

        $this->notice->finished_at = Carbon::now();
        $this->notice->completed_count = $this->notice->notice_count;

        $this->notice->save();
        #app('realtimePush')->publish(RealtimePush::COUPON_NOTICE_ADD, $this->notice->admin_user_id, ['id'=>'124', 'jump'=>'yes']);
    }
}
