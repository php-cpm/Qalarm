<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class AdminMenuPermit extends Gaea
{
    protected $table = 'admin_menu_permit';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public function export()
    {
        return $this;
    }
}
