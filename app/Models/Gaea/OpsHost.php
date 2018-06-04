<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class OpsHost extends Gaea
{
     protected $table = 'ops_host';  

     const HOST_INIT    = 0;
     const HOST_UP      = 1;
     const HOST_DOWN    = 2;
     const HOST_DESTORY = 3;
     const HOST_ALL     = 10;

     public static $hostStatusDesc = [
         self::HOST_INIT        => ['color' => 'text-warning', 'desc' => '未初始化'],
         self::HOST_UP          => ['color' => 'text-success-dker', 'desc' => '已上线'],
         self::HOST_DOWN        => ['color' => 'text-danger-dker', 'desc' => '已下线'],
         self::HOST_DESTORY     => ['color' => 'text-danger-dker', 'desc' => '已销毁'],
         self::HOST_ALL         => ['color' => '', 'desc' => '全部'],
     ];

     public static function getHostStatuses()
     {/*{{{*/
         return [
             self::HOST_INIT,
             self::HOST_UP,
             self::HOST_DOWN,
             self::HOST_DESTORY,
             self::HOST_ALL,
         ];
     }/*}}}*/
     
     public function export()
     {/*{{{*/
         $this->statusDesc  = self::$hostStatusDesc[$this->status]['desc'];
         $this->statusColor = self::$hostStatusDesc[$this->status]['color'];

         $this->can_up      = false;
         $this->can_down    = false;
         $this->can_destory = false;

         if ($this->status == self::HOST_INIT) {
             $this->can_up      = true;
             $this->can_down    = true;
             $this->can_destory = true;
         }

         if ($this->status == self::HOST_UP) {
             $this->can_down    = true;
             $this->can_destory = true;
         }

         if ($this->status == self::HOST_DOWN) {
             $this->can_up      = true;
             $this->can_destory = true;
         }

         return $this;
     }/*}}}*/
}
