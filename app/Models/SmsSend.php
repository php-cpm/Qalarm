<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSend extends Model
{
    protected $table = 'sms_send';

    public function smsReply()
    {
        return $this->hasMany('App\Models\SmsReply', 'msgid', 'msgid');
    }
}
