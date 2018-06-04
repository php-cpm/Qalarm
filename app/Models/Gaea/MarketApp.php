<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gaea\AdminApp;
use App\Components\Utils\DomainUserUtil;

class MarketApp extends Gaea
{
     protected $table = 'market_app';  
     public $timestamps = false;
     
     private static $userInfo = null;
     
     public function export()
     {
         $this->urlDisabled = true;
         if ($this->status == '已开') {
             if (self::$userInfo == null) {
                 $sid = DomainUserUtil::getInstance()->getSessionId();
                 self::$userInfo = DomainUserUtil::getInstance()->_getUserInfoBySid($sid);
             }

             $suffix = $this->login_query;
             $password = base64_decode(self::$userInfo['attachment']);
             $password = str_replace("\x00", '', $password);
             $suffix = sprintf($suffix, self::$userInfo['userName'], $password);
             $this->url .= $suffix;

         } else {
             $this->url = '';
             $this->urlDisabled = false;
         }
         return $this;
     }
}
