<?php
namespace App\Jobs;

use Log;
use Redis;


use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Util\TimeUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Notice\Notice;

class DindinJob extends BaseJob
{
    protected $object;

    public function __construct($obj) 
    {
        $this->object = $obj;
        parent::__construct($obj);
    }

    public function doHandle()
    {
        $dindin = $this->object;

        $content = $dindin['content'];
        $mobiles = $dindin['mobiles'];

        $result = app('notice')->sendDindin($mobiles, $content);

        if ($result == false) {
            LogUtil::warning('Send Dindin error', [$dindin], LogUtil::LOG_JOB);
            // 抛出异常 FIXME
        }

        return true;
    }
}

