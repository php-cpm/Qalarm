<?php

namespace App\Models\Qalarm;

use Illuminate\Database\Eloquent\Model;

class Message extends Qalarm
{
    public $timestamps = true;
    protected $table = 'message';

    public function export()
    {
        return $this;
    }
}
