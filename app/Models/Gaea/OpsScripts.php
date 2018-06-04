<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Gaea\AdminUser;

class OpsScripts extends Gaea
{
     use SoftDeletes;

     protected $table = 'ops_scripts';  

     protected $dates = ['deleted_at'];

     const OWNER_GAEA  =  1;
     const OWNER_OPS   =  2;
     const OWNER_DBA   =  3;

     public static $scriptStorageDir  = [
         self::OWNER_GAEA       => ['rsyncname' => 'GAEA_CLIENT_SCRIPT', 'desc' => 'Gaea'],
         self::OWNER_OPS        => ['rsyncname' => 'JOBDONE_OPS', 'desc' => 'Ops'],
         self::OWNER_DBA        => ['rsyncname' => 'JOBDONE_DBA', 'desc' => 'DBA']
     ];

     public static $scriptType = [
         1    => ['dir' => 'sysinit',  'name' => '系统初始化'],
         2    => ['dir' => 'soft',     'name' => '软件安装'],
         3    => ['dir' => 'dba',      'name' => 'DBA'],
         4    => ['dir' => 'confmgr',  'name' => '配置管理'],
         5    => ['dir' => 'tmp',      'name' => '临时脚本'],
     ];

     public function admin()
     {
         return $this->hasOne('App\Models\Gaea\AdminUser', 'id', 'admin_id');
     }

     public function export()
     {
         $this->ownername  = self::$scriptStorageDir[$this->owner]['desc'];
         $this->typename   = self::$scriptType[$this->type]['name'];
         $this->adminname  = $this->admin->username;
         
         // 脚本参数过长就截断
         if (strlen($this->params) > 30) {
             $this->params = substr($this->params, 0, 32);
             $this->params .= '...';
         }
         return $this;
     }
}
