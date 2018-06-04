<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class AdminUserRole extends Gaea
{
    protected $table = 'admin_user_role';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public function export()
    {
        return $this;
    }
}
