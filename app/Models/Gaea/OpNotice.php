<?php

namespace App\Models\Gaea;

use Log;

use Illuminate\Database\Eloquent\Model;

use App\Components\Utils\Constants;

class OpNotice extends Gaea
{
     protected $table = 'op_notice';  

     const NOTICE_TYPE_NORMAL = 1;   // 普通通知
     const NOTICE_TYPE_JUMP   = 2;   // APP内跳转通知
     const NOTICE_TYPE_ACTIVE = 3;   // 活动通知

     private static $noticTypeName = [
         self::NOTICE_TYPE_NORMAL => '普通通知',
         self::NOTICE_TYPE_JUMP   => 'APP内跳转通知',
         self::NOTICE_TYPE_ACTIVE => '活动通知',
     ];

     const SMS_CHANNEL_NORMAL = 1;   // 生产渠道
     const SMS_CHANNEL_MARKET = 2;   // 营销渠道

     private static $smsChannelName = [
         self::SMS_CHANNEL_NORMAL => '生产渠道',
         self::SMS_CHANNEL_MARKET => '营销渠道',
     ];

     const NOTICE_WAY_PUSH = 1;
     const NOTICE_WAY_SMS  = 2;

     const MAX_NOTICE_COUNT_ONCE = 500;

     private function getNoticeDatetime()
     {
//         $ret = '指定推送时间:'.$this->notice_time."\n";
//         $ret .= '开始推送时间:'.$this->started_at."\n";
//         $ret .= '完成推送时间:'.$this->finished_at."\n";
//
         $ret = $this->notice_time->timestamp * 1000;
         return $ret;
     }

     private function getNoticeWay()
     {
         $ret = '';

         if ($this->notice_way == self::NOTICE_WAY_PUSH) {
             $ret .= 'PUSH';
         } else {
             $ret = '短信|';
             $ret .= self::$smsChannelName[$this->sms_channel];
         }

         return $ret;
     }

     private function getNoticeCount()
     {
         $ret = '总量:'.$this->notice_count .",";
         $ret .= '完成量:'.$this->completed_count.",";
         $rate = ((int)$this->completed_count / (int)$this->notice_count) * 100;
         $ret .= '成功率:'.$rate."%";

         return $ret;
     }

     // 只返回一条手机号
     private function getNoticeMobiles()
     {
         $ret = "";
         $mobiles = explode("\n", $this->notice_mobiles);
         if (count($mobiles) != 1) {
             $ret = $mobiles[0];
             $ret .= ',...';
         } else {
             $ret = $mobiles[0];
         }

         return $ret;
     }

     protected $dates = ['notice_time', 'started_at', 'finished_at'];

     public function export()
     {
         $data = [
            "id"               =>  $this->id,
            "admin_name"       =>  $this->admin_user_name,
            "notice_type"      =>  self::$noticTypeName[$this->notice_type],
            "notice_way"       =>  $this->getNoticeWay(),
            "notice_time"      =>  $this->getNoticeDatetime(),
            "notice_content"   =>  $this->notice_content,
            "notice_count"     =>  $this->getNoticeCount(),
            "active_id"        =>  $this->active_id,
            "notice_remark"    =>  $this->notice_remark,
            "notice_mobile"    =>  $this->getNoticeMobiles(),
         ];

         return $data;
     }
}
