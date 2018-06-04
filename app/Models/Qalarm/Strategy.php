<?php

namespace App\Models\Qalarm;

use Illuminate\Database\Eloquent\Model;

class Strategy extends Qalarm
{
    public $timestamps = true;
    protected $table = 'strategy';

    public  function toArray()
    {
        return [
            'limit'  => [$this->param1, $this->param2, $this->param3],
            'period' => [$this->valid_start, $this->valid_end],
            'sms'    => $this->is_sms,
            'email'  => $this->is_email
        ];
    }
}
