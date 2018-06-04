<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class OpContent extends Gaea
{
     protected $table = 'op_content';  

     // 文章状态
     const CONTENT_DRAFT        = 0;
     const CONTENT_AUDITING     = 1;
     const CONTENT_AUDIT_OK     = 2;
     const CONTENT_AUDIT_NOK    = 3;
     const CONTENT_PUBLISH      = 10;

     // 是否置顶
     const CONTENT_NOT_TOP      = 0;
     const CONTENT_TOP          = 1;

     private static $ContentStatusDesc = [
         self::CONTENT_DRAFT    => '草稿',
         self::CONTENT_AUDITING => '审核中',
         self::CONTENT_AUDIT_OK => '审核通过',
         self::CONTENT_AUDIT_NOK=> '审核不通过',
         self::CONTENT_PUBLISH  => '已发布',
     ];


     public function export()
     {
         $this->statusDesc = self::$ContentStatusDesc[$this->status];
         if ($this->is_top == self::CONTENT_TOP) {
            $this->statusDesc .= '|已置顶';
         }

         $this->preview_url  = env('TTYC_HOME_URL', 'http://test.ttyongche.com').'/detail?id='.$this->id;
         $this->content_length = mb_strlen($this->content);
         return $this;
     }
}
