<?php

namespace App\Models\Gaea;

use DB;

use Carbon\Carbon;

use App\Components\Utils\Constants;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gaea\AdminUser;
use App\Models\Gaea\MarketApp;

use App\Components\Utils\LogUtil;

use App\Components\Workflow\WorkflowHandler;
use App\Components\Workflow\MarketAppHandler;
use App\Components\Workflow\ServerPermHandler;

use App\Models\Gaea\WorkflowParticipator;

class Workflow extends Gaea
{
     protected $table = 'workflow';  

     // 声明常量 /*{{{*/
     const WF_MARKET_APP                       = 1;
     const WF_OP_DAILY_MACHINE_PERMIT          = 10;
     const WF_OP_DAILY_APPLY_VPN               = 11;
     const WF_OP_DAILY_RECOVER_PERMISSION      = 12;  // 一键回收公司权限

     public static $workflow = [
         self::WF_MARKET_APP                 => [
            'name'     => '应用',
            'handler'  => 'App\Components\Workflow\MarketAppHandler',
            'buss'     => 'App\Models\Gaea\MarketApp',
         ],
         self::WF_OP_DAILY_MACHINE_PERMIT    => [
            'name'     => '机器权限',
            'handler'  => 'App\Components\Workflow\ServerPermHandler',
         ],
         self::WF_OP_DAILY_APPLY_VPN         => [
            'name'     => 'VPN',
            'handler'  => 'App\Components\Workflow\VPNHandler',
         ],
         self::WF_OP_DAILY_RECOVER_PERMISSION=> [
            'name'     => '回收权限',
            'handler'  => 'App\Components\Workflow\RecoverPermHandler',
         ],
     ];

     const WF_STATUS_WAIT     = 1;
     const WF_STATUS_DONING   = 2;
     const WF_STATUS_DONE     = 3;
     const WF_STATUS_FAILURE  = 4;
     const WF_STATUS_REBACK   = 5;

     public static $statusDesc = [
         self::WF_STATUS_WAIT       => '待处理',
         self::WF_STATUS_DONING     => '处理中',
         self::WF_STATUS_DONE       => '完成',
         self::WF_STATUS_FAILURE    => '处理失败',
         self::WF_STATUS_REBACK     => '退回',
     ];

     public static $defaultWFOps = [
            'chenfei',
            'liuliwei'
     ];

     public static function getRandomOps($type, $buss_id = 0)
     {/*{{{*/
         $query = WorkflowParticipator::where('workflow_type', $type);
         if ($buss_id != 0)  {
             $query->where('buss_id', $buss_id);
         }

         $ops = $query->first();

         // 如果找不到对应的处理者，则添加一条默认的处理者记录，以便修改处理者
         if (is_null($ops)) {
             $participator = new WorkflowParticipator();
             {
                 $participator->workflow_type        = $type;
                 $participator->workflow_name        = self::$workflow[$type]['name'];
                 $participator->workflow_handler     = self::$workflow[$type]['handler']; 
                 $participator->buss_id              = $buss_id;
                 if ($buss_id != 0) {
                     $class = self::$workflow[$type]['buss'];
                     $buss = $class::where('id', $buss_id)->first();
                     $participator->buss_name        = $buss->name;
                 }
                 $participator->participator         = join(',', Workflow::$defaultWFOps);
             }
             $participator->save();

             $rand = mt_rand(0, count(self::$defaultWFOps)-1);
             $adminName = self::$defaultWFOps[$rand];
         } else {
             $participators = explode(',', $ops->participator);
             $rand = mt_rand(0, count($participators)-1);
             $adminName =$participators[$rand];
         }

         return AdminUser::where('username', $adminName)->first();
     }/*}}}*/

     public function admin()
     {/*{{{*/
         return $this->hasOne('App\Models\Gaea\AdminUser', 'id', 'admin_id');
     }/*}}}*/
     
     public function ops()
     {/*{{{*/
         return $this->hasOne('App\Models\Gaea\AdminUser', 'id', 'alloc_id');
     }/*}}}*/
     
     public function export()
     {/*{{{*/
         $this->type_name = self::$workflow[$this->type]['name'];
         $this->username  = $this->admin->nickname;
         $attachment = json_decode($this->attachment, true);
         // 用于显示详情
         $this->attachmentFull = $attachment;
         if (strlen($this->attachment) > 32) {
             $this->attachment = substr($this->attachment, 0, 32);
             $this->attachment .= '...';
         }

         $this->buss_name = isset($attachment['wf_desc'])?$attachment['wf_desc']:'';
         if (strlen($this->buss_name) > 16) {
             $this->buss_name= substr($this->buss_name, 0, 16);
             $this->buss_name.= '...';
         }

         $this->statusDesc= self::$statusDesc[$this->status];
         return $this;
     }/*}}}*/

     /**
      * @brief generateWorkflow 创建一个工作流，并通知用户
      * @param $type 工作流类型
      * @param $wfDesc 此条工作流的描述，通知时用于组装信息
      * @param $attachment 附件信息
      *
      * @return workflow
      */
     public static function generateWorkflow($type, $wfDesc = '', $attachment = [], $buss_id = 0)
     {/*{{{*/
        $workflow = new Workflow;
        {
            $workflow->type = $type;
            $workflow->buss_id = $buss_id;
            $workflow->admin_id = Constants::getAdminId();
            $workflow->status = Workflow::WF_STATUS_WAIT;

            $alloc = Workflow::getRandomOps($type, $buss_id);
            $workflow->alloc_id = $alloc->id;
            $workflow->alloc_name = $alloc->nickname;

            $attachment['wf_desc'] = $wfDesc;
            $workflow->attachment = json_encode($attachment);
        }
        $workflow->save();

        // 申请通知
        $handler = new Workflow::$workflow[$workflow->type]['handler'];
        $callable = array($handler, 'apply');
        call_user_func($callable, $workflow);

        return $workflow;
     }/*}}}*/
     
     public function statusThransfer($opertor, &$output = [])
     {/*{{{*/
         switch($opertor) {
         case 'pass':
             if ($this->status == self::WF_STATUS_WAIT) {

                 $handler = new self::$workflow[$this->type]['handler'];
                 $output = call_user_func(array($handler, 'doing'), $this);

                 if ($output['errno'] != 0) {
                     return false;
                 }
                 
                 $this->status = self::WF_STATUS_DONING;
                 $this->save();
                 return true;

             } else {
                 return false;
             }

             break;
         case 'fail':
             if ($this->status == self::WF_STATUS_DONING) {
                 $handler = new self::$workflow[$this->type]['handler'];
                 $output = call_user_func(array($handler, 'failed'), $this);
                 
                 if ($output['errno'] != 0) {
                     return false;
                 }
                 
                 $this->status = self::WF_STATUS_FAILURE;
                 $this->handled_at = Carbon::now();
                 $this->save();
                 return true;

             } else {
                 return false;
             }
             break;
         case 'done':
             if ($this->status == self::WF_STATUS_DONING) {
                 $handler = new self::$workflow[$this->type]['handler'];
                 $output = call_user_func(array($handler, 'done'), $this);

                 if ($output['errno'] != 0) {
                     return false;
                 }

                 $this->status = self::WF_STATUS_DONE;
                 $this->opt_id = Constants::getAdminId();
                 $this->opt_name = Constants::getAdminName();
                 $this->handled_at = Carbon::now();
                 $this->save();

                 return true;

             } else {
                 return false;
             }
             break;
         case 'reback':
             if ($this->status == self::WF_STATUS_WAIT) {
                 $handler = new self::$workflow[$this->type]['handler'];
                 $output = call_user_func(array($handler, 'reback'), $this);
                 
                 if ($output['errno'] != 0) {
                     return false;
                 }

                 $this->status = self::WF_STATUS_REBACK;
                 $this->save();

                 return true;

             } else {
                 return false;
             }
             break;
         case 'retry':
             if ($this->status == self::WF_STATUS_FAILURE) {
                 $this->status = self::WF_STATUS_DONING;

                 $this->save();
                 return true;

             } else {
                 return false;
             }
             break;
         }
     }/*}}}*/
}
