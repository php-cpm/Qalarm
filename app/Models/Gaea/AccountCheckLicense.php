<?php
namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class AccountCheckLicense extends Gaea
{
    protected $table = 'acc_check_license';
    protected $primaryKey = 'id';
    public $timestamps = false;

}
