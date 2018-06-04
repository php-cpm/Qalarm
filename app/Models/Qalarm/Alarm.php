<?php

namespace App\Models\Qalarm;

use Illuminate\Database\Eloquent\Model;

class Alarm extends Qalarm
{
    public $timestamps = false;
    protected $table = 'alarm';



    public function export()
    {
        return $this;
    }
}
