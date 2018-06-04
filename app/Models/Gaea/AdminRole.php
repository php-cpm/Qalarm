<?php

namespace App\Models\Gaea;
use DB;

use Illuminate\Database\Eloquent\Model;

use App\Models\Gaea\AdminAuth;

class AdminRole extends Gaea
{
     protected $table = 'admin_role';  
     
     public function export()
     {
//         $authNames = DB::select("select t2.auth_name, t2.sid from admin_role t1, admin_auth t2 where  LOCATE(concat(',',t2.id,','),concat(',',t1.`authid_set`,','))!=0   and t1.id=?", [$this->id]);
//         $auths = array();
//         foreach ($authNames as $auth) {
//             if ($auth->sid == 0) continue;
//             $auths[] = $auth->auth_name;
//         }
//         $this->authid_set = join(',', $auths);
//         return $this;

         $str = <<<EOD
select t1.role_id,t1.permit_id,t2.menu_id,t2.sub_page_code,t2.sub_page_name,t2.permit_code,t2.permit_name,
t3.auth_name, t3.mid,t3.sid
 from admin_role_permit as t1
join admin_menu_permit as t2 ON t1.permit_id = t2.id
join admin_auth as t3 ON t2.menu_id = t3.id
where t1.role_id = ?
EOD;

         $authNames = DB::connection('gaea')->select($str, [$this->id]);
         $auths = array();
         $role_permits = array();
         foreach ($authNames as $auth) {
             if ($auth->sid == 0) continue;
//             $auths[] = $auth->auth_name.'->'.$auth->sub_page_name.'->'.$auth->permit_name;
             $auths[] = $auth->sub_page_name.'-'.$auth->permit_name;
             $role_permits[] = $auth->permit_id;
         }

        // dd($role_permts);

         $this->authid_set = join('|', $auths);
         $this->role_permits = join(',', $role_permits);
         return $this;
     }
}
