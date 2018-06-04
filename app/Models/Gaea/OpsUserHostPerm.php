<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class OpsUserHostPerm extends Gaea
{
     protected $table = 'ops_user_host_perm';
     
     public function host() 
     { 
         return $this->hasOne('App\Models\Gaea\OpsHost', 'id', 'host_id');     
     }

     public function user()
     {
         return $this->hasOne('App\Models\Gaea\AdminUser', 'id', 'user_id');
     }

     public function export()
     {
         return $this;
     }
}
