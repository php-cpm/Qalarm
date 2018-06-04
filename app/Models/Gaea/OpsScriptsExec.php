<?php

namespace App\Models\Gaea;

use DB;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

use App\Models\Gaea\AdminUser;
use App\Models\Gaea\OpsScripts;

use App\Components\JobDone\JobDone;

class OpsScriptsExec extends Gaea
{
     protected $table = 'ops_scripts_exec';  

     const TRY_EXEC    = 1;
     const ALL_EXEC    = 0;

     public function admin()
     {
         return $this->hasOne('App\Models\Gaea\AdminUser', 'id', 'admin_id');
     }

     public function script() 
     {
         return $this->hasOne('App\Models\Gaea\OpsScripts', 'id', 'script_id');
     }

     public function export()
     {
         $data = [];
         $data['adminname']  = $this->admin->username;
         $data['scriptname'] = $this->script->scriptname;
         if (isset($this->status) && !empty($this->status)) {
             $data['statusDesc']    = JobDone::$jobStatusDesc[$this->status];
             // 试执行成功的状态
             if ($this->status != JobDone::JOB_RUNNING && $this->is_try == self::TRY_EXEC) {
                 $data['statusDesc'] = '灰度完成';
             }
         }

         
         // 脚本参数过长就截断
         if (strlen($this->params) > 30) {
             $data['params'] = substr($this->params, 0, 32);
             $data['params'] .= '...';
         }

         // 判断client的操作权限
         $data['can_exec'] = false;
         $data['can_redo'] = false;
         $data['host_number'] = $this->host_number;

         // 如果执行完成，并且只有一台机器，表示整个job完成
         if ($this->status != JobDone::JOB_RUNNING) {
             if ($this->is_try == self::TRY_EXEC) {
                 // 执行完成,可以重做
                 if ($data['host_number'] == 1) {
                     $data['can_redo'] = true;
                 // 继续重新执行剩下的任务
                 } else { 
                     $data['can_exec'] = true;
                 }
             } else {
                 $data['can_redo'] = true;
             }
         }
         $data['id']          = $this->id;
         $data['success']     = $this->success;
         $data['failed']      = $this->failed;
         $data['hostnames']   = $this->hostnames;
         $data['created_at']  = with(new Carbon())->timestamp($this->created_at->timestamp)->format('Y-m-d H:d:s');

         return $data;
     }
}
