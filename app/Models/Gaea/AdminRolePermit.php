<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class AdminRolePermit extends Gaea
{
    protected $table = 'admin_Role_permit';

    protected $primaryKey = 'id';
    public $timestamps = false;
    public function export()
    {
        return $this;
    }
}
