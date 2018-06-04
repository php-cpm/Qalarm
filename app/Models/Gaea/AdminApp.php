<?php

namespace App\Models\Gaea;

use DB;
use Cookie;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gaea\AdminRole;
use App\Components\Utils\DomainUserUtil;

class AdminApp extends Gaea
{
     protected $table = 'admin_app';  

     public $timestamps = false;

     private static $userInfo = null;

     public function app()
     {
         return $this->hasOne('App\Models\Gaea\MarketApp', 'id', 'app_id');
     }
     
     public function export()
     {
         $data = [
             'name'     => $this->app->name,
             'url'      => $this->app->url,
             'icon'     => $this->app->icon,
         ];

         if (!empty($this->app->login_query)) {

             if (self::$userInfo == null) {
                 $sid = DomainUserUtil::getInstance()->getSessionId();
                 self::$userInfo = DomainUserUtil::getInstance()->_getUserInfoBySid($sid);
             }

             $suffix = $this->app->login_query;
             $password = base64_decode(self::$userInfo['attachment']);
             $password = str_replace("\x00", '', $password);
             $suffix = sprintf($suffix, self::$userInfo['userName'], $password);
             $data['url'] .= $suffix;
         }

         return $data;
     }
}
