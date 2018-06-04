<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;
use App\Models\Gaea\AdminRole;

class AdminUser extends Gaea
{
     protected $table = 'admin_user';  
     
     public function export()
     {
//         $roleNames = DB::select("select t2.role_name from admin_user t1, admin_role t2 where  LOCATE(concat(',',t2.id,','),concat(',',t1.`roleid_set`,','))!=0   and t1.id=?", [$this->id]);
//         $names = array();
//         foreach ($roleNames as $name) {
//             $names[] = $name->role_name;
//         }
//
//         $this->roleid_set = join(',', $names);
//         return $this;

         $collection = DB::connection('gaea')->select("select t1.user_id, t1.role_id, t2.role_name from admin_user_role as t1 join  admin_role as t2 on t1.role_id = t2.id where t1.user_id=?", [$this->id]);
         $names = array();
         $ids = array();
         foreach ($collection as $item) {
             $names[] = $item->role_name;
             $ids[] = $item->role_id;
         }
         $this->role_names = join(',', $names);
         $this->role_ids = join(',', $ids);

         $collection = DB::connection('gaea')->select("select t1.id, t1.cityid, t2.name from admin_user as t1 join  city as t2 on t1.cityid = t2.mapcode where t1.id=?", [$this->id]);
         foreach ($collection as $item) {
             $this->city_name = $item->name;
         }

         return $this;
     }
}
