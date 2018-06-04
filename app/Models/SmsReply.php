<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsReply extends Model
{
    protected $table = 'sms_reply';

    public function smsSend()
    {
        return $this->belongsTo('App\Models\SmsSend', 'msgid', 'msgid');
    }
}
