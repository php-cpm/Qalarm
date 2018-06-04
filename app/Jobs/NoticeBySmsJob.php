<?php
namespace App\Jobs;

use Log;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Notice\Sms;

class NoticeBySmsJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $message = $this->object;

        $mobiles    = $message['mobiles'];
        $params     = $message['params'];
        $templateId = $message['template_id'];

        foreach ($mobiles as $mobile) {
            $result = Sms::send($mobile, $params, $templateId);
            LogUtil::info('send sms', ['params' => $message, 'result' => $result]);
        }

        return true;
    }
}

