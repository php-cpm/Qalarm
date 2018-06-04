<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

class AppClientConf extends Gaea
{
    protected $table = 'app_client_conf';

    protected $primaryKey = 'id';
    public $timestamps = false;
}
